<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our API 
require_once PATH_THIRD . 'minimee/classes/Minimee_api.php';

$plugin_info = array(
	'pi_name'			=> MINIMEE_NAME,
	'pi_version'		=> MINIMEE_VER,
	'pi_author'			=> MINIMEE_AUTHOR,
	'pi_author_url'		=> MINIMEE_DOCS,
	'pi_description'	=> MINIMEE_DESC,
	'pi_usage'			=> Minimee::usage()
);

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Minimee: minimize & combine your CSS and JS files. Minify your HTML. For EE2 only.
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee {

	/**
	 * EE, obviously
	 */
	private $EE;


	/**
	 * Boolean whether we are calling from template_post_parse
	 */
	public $calling_from_hook		= FALSE;


	/**
	 * Name of our queue, if running
	 */
	public $queue					= '';


	/**
	 * Our API
	 */
	public $API;


	/**
	 * Reference to our cache
	 */
	public $cache;


	// ------------------------------------------------------


	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		// got EE?
		$this->EE =& get_instance();
		
		// grab reference to our cache
		$this->cache =& Minimee_helper::cache();

		// instantiate our API
		$this->API = new Minimee_api();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:css
	 * 
	 * @return mixed string or empty
	 */
	public function css()
	{
		// set local version of tagdata
		$this->API->on_error = $this->EE->TMPL->tagdata;

		// our asset type
		$this->API->type = 'css';

		// type of output
		$this->API->out_type = 'tag';

		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:display
	 * 
	 * @return mixed string or empty
	 */
	public function display()
	{
		// alias of tag
		return $this->tag();
	}
	// ------------------------------------------------------
	
	
	/**
	 * Plugin function: exp:minimee:embed
	 * 
	 * This fetches files from our queue and embeds the cache
	 * contents inline. It has no on_error value, and
	 * the asset type is determined by the queue 
	 * 
	 * @return mixed string or empty
	 */
	public function embed()
	{
		// type of output
		$this->API->out_type = 'embed';

		// let's go
		return $this->_run('embed', TRUE);
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:html
	 * 
	 * @return void
	 */
	public function html()
	{
		// we do not need to actually do anything. Simply being called is enough.
		return;
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:js
	 * 
	 * @return mixed string or empty
	 */
	public function js()
	{
		// set local version of tagdata
		$this->API->on_error = $this->EE->TMPL->tagdata;

		// our asset type
		$this->API->type = 'js';

		// type of output
		$this->API->out_type = 'tag';

		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:link
	 * 
	 * Alias to exp:minimee:url
	 */
	public function link()
	{
		// alias of Minimee::url()
		return $this->url();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:url
	 * 
	 * Rather than returning the tags or cache contents, simply return URL to cache(s)
	 */
	public function url()
	{
		// set our output type		
		$this->API->out_type = 'url';

		// let's go
		return $this->_run('url', TRUE);
	}
	// ------------------------------------------------------
	

	/**
	 * Plugin function: exp:minimee:display
	 *
	 * Return the tags for cache
	 */
	public function tag()
	{
		// set output type
		$this->API->out_type = 'tag';

		// let's go
		return $this->_run('tag', TRUE);
	}
	// ------------------------------------------------------

	
	/**
	 * Display usage notes in EE control panel
	 *
	 * @return string Usage notes
	 */	
	public function usage()
	{
		// just return basic usage
		return <<<HEREDOC

Complete and up-to-date documentation: http://johndwells.com/software/minimee

=====================================================
Basic Usage
=====================================================

CSS:
{exp:minimee:css}
	<link type="text/css" rel="stylesheet" href="/css/reset.css" />
	<link type="text/css" rel="stylesheet" href="/css/fonts.css" />
	<link type="text/css" rel="stylesheet" href="/css/screen.css" />
{/exp:minimee:css}

JS:
{exp:minimee:js}
	<script type="text/javascript" src="/scripts/jquery.form.js"></script>
	<script type="text/javascript" src="/scripts/jquery.easing.1.3.js"></script>
{/exp:minimee:js}

HTML (for EE2.4+):
See documentation for details.
HEREDOC;
	}
	// ------------------------------------------------------
	

	/**
	 * Abort and return original tagdata.
	 * Logs the error message.
	 *
	 * @param mixed The caught exception or empty string
	 * @return string The un-Minimeed tagdata
	 */	
	protected function _abort($e = FALSE)
	{
		if ($e)
		{
			$log = $e->getMessage();
		}
		else
		{
			$log = 'Aborted without a specific error.';
		}

		// log our error message
		Minimee_helper::log($log, 1);

		// Return our on_error content, whether from queue or current run
		if ($this->queue && isset($this->API->cache[$this->API->type][$this->queue]))
		{
			return $this->API->cache[$this->API->type][$this->queue]['on_error'];
		}
		else
		{
			return $this->API->on_error;
		}
	}
	// ------------------------------------------------------


	/**
	 * Parse tagdata for <link> and <script> tags,
	 * pulling out href & src attributes respectively.
	 * [Adapted from SL Combinator]
	 * 
	 * @param string tagdata
	 * @param string either css or js
	 * @return bool TRUE on success of fetching files; FALSE on failure
	 */
	protected function _fetch_files()
	{
		$haystack = $this->EE->TMPL->tagdata;

		// first up, let's substitute stylesheet= for minimee=, because we handle these special
		if($this->API->type == 'css')
		{
			$haystack = preg_replace("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", '[minimee=$1]', $haystack);
		}

		// parse globals if we find any EE syntax tags
		if (preg_match("/".LD."(.*?)".RD."/", $haystack) === 1)
		{
			$haystack = $this->EE->TMPL->parse_globals($haystack);
		}

		// try to match any pattern of css or js tag
		$matches = Minimee_helper::preg_match_by_type($haystack, $this->API->type);
		if ( ! $matches)
		{
			throw new Exception('No ' . $this->API->type . ' files found to return.');
		}

		// set our tag template
		$this->API->template = str_replace($matches[1][0], '{minimee}', $matches[0][0]);
		
		// set our files & filesdata arrays
		$this->API->set_filesdata($matches[1]);

		// free memory where possible
		unset($pat, $matches);
		
		// chaining
		return $this;
	}
	// ------------------------------------------------------

	
	/**
	 * Fetch parameters from $this->EE->TMPL
	 * 
	 * @return void
	 */
	protected function _fetch_params()
	{
		$tagparams = $this->EE->TMPL->tagparams;
		
		// we do need to account for the fact that minify="no" is assumed to be pertaining to the tag
		if (isset($tagparams['combine']))
		{
			$tagparams['combine_' . $this->API->type] = $tagparams['combine'];
		}
		
		if (isset($tagparams['minify']))
		{
			$tagparams['minify_' . $this->API->type] = $tagparams['minify'];
		}
		
		// pass all params through our config, will magically pick up what's needed
		$this->API->config->reset()->extend($tagparams);

		// fetch queue if it hasn't already been set via Minimee::_display()
		if ( ! $this->queue)
		{
			$this->queue = strtolower($this->EE->TMPL->fetch_param('queue', NULL));
		}
		
		unset($tagparams);
		
		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Retrieve files from cache
	 *
	 * @return void
	 */	
	protected function _fetch_queue()
	{
		if ( ! isset($this->API->cache[$this->API->type][$this->queue]))
		{
			throw new Exception('Could not find a queue of files by the name of \'' . $this->queue . '\'.');
		}

		// set our tag template
		$this->API->template = $this->API->cache[$this->API->type][$this->queue]['template'];
		
		// order by priority
		ksort($this->API->cache[$this->API->type][$this->queue]['filesdata']);

		// now reduce down to one array
		$filesdata = array();
		foreach($this->API->cache[$this->API->type][$this->queue]['filesdata'] as $fdata)
		{
			$filesdata = array_merge($filesdata, $fdata);
		}
		
		// clear filesdata just in case
		$this->API->filesdata = array();

		// set our Minimee::filesdata array
		$this->API->set_filesdata($filesdata, TRUE);

		// No files found?
		if ( ! is_array($this->API->filesdata) OR count($this->API->filesdata) == 0)
		{
			throw new Exception('No files found in the queue named \'' . $this->API->type . '\'.');
		}
		
		// cleanup
		unset($filesdata);
		
		// chaining
		return $this;
	}
	// ------------------------------------------------------
	
	
	/**
	 * Postpone processing our method until template_post_parse hook?
	 * 
	 * @param String	Method name (e.g. display, link or embed)
	 * @return Mixed	TRUE if delay, FALSE if not
	 */
	protected function _postpone($method)
	{
		// definitely do not postpone if EE is less than 2.4
		if (version_compare(APP_VER, '2.4', '<'))
		{
			return FALSE;
		}
		
		else
		{
			// if calling from our hook return FALSE
			if ($this->calling_from_hook)
			{
				return FALSE;
			}
			
			// store TMPL settings and return our $needle to find later
			else
			{
				// base our needle off the calling tag
				$needle = sha1($this->EE->TMPL->tagproper);
				
				// save our tagparams to re-instate during calling of hook
				$tagparams = $this->EE->TMPL->tagparams;
				
				if ( ! isset($this->API->cache['template_post_parse']))
				{
					$this->API->cache['template_post_parse'] = array();
				}
				
				$this->API->cache['template_post_parse'][$needle] = array(
					'method' => $method,
					'tagparams' => $tagparams
				);
				
				Minimee_helper::log('Postponing process of Minimee::' . $method . '() until template_post_parse hook.', 3);
				
				// return needle so we can find it later
				return LD.$needle.RD;
			}
		}
	}
	// ------------------------------------------------------


	/**
	 * Called by Minimee:css and Minimee:js, performs basic run command
	 * 
	 * @param mixed  Name of method to pass to postpone
	 * @param bool   Whether we should check for a queue or not
	 * @return mixed string or empty
	 */
	protected function _run($method = FALSE, $from_queue = FALSE)
	{
		// try to postpone until template_post_parse
		if ($method && $out = $this->_postpone($method))
		{
			return $out;
		}

		try
		{
			// should we be operating off file(s) stored in queue?
			if($from_queue)
			{
				if ($this->EE->TMPL->fetch_param('js'))
				{
					$this->queue = $this->EE->TMPL->fetch_param('js');
					$this->API->type = 'js';
				}

				if ($this->EE->TMPL->fetch_param('css'))
				{
					$this->queue = $this->EE->TMPL->fetch_param('css');
					$this->API->type = 'css';
				}

				// abort error if no queue was provided		
				if ( ! $this->queue)
				{
					return $this->_abort('You must specify a queue name.');
				}

				// fetch our parameters
				$this->_fetch_params();

				// OK, let's fetch from our queue
				$this->_fetch_queue();
			}

			// fetch our files
			else
			{
				// fetch our parameters
				$this->_fetch_params();

				// fetch our files
				$this->_fetch_files();

				// should we set our files to queue for later?
				if($this->queue)
				{
					return $this->_set_queue();
				}
			}

			// the rest is easy
			return $this->API->flightcheck()
							 ->check_headers()
							 ->cache();
		}
		catch (Exception $e)
		{
			return $this->_abort($e);
		}
	}
	// ------------------------------------------------------


	/** 
	 * Adds the files to be queued into session
	 * 
	 * @param string either 'js' or 'css'
	 * @return void
	 */
	protected function _set_queue()
	{
		// be sure we have a cache set up
		if ( ! isset($this->API->cache[$this->API->type]))
		{
			$this->API->cache[$this->API->type] = array();
		}

		// create new session array for this queue
		if ( ! isset($this->API->cache[$this->API->type][$this->queue]))
		{
			$this->API->cache[$this->API->type][$this->queue] = array(
				'template' => $this->API->template,
				'on_error' => '',
				'filesdata' => array()
			);
		}
		
		// be sure we have a priority key in place
		$priority = (int) $this->EE->TMPL->fetch_param('priority', 0);
		if ( ! isset($this->API->cache[$this->API->type][$this->queue]['filesdata'][$priority]))
		{
			$this->API->cache[$this->API->type][$this->queue]['filesdata'][$priority] = array();
		}
		
		// Append $on_error
		$this->API->cache[$this->API->type][$this->queue]['on_error'] .= $this->API->on_error;

		// Add all files to the queue cache
		foreach($this->API->filesdata as $filesdata)
		{
			$this->API->cache[$this->API->type][$this->queue]['filesdata'][$priority][] = $filesdata;
		}
	}
	// ------------------------------------------------------

}
// END
	
/* End of file pi.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/pi.minimee.php */