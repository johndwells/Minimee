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
	 * Our API
	 */
	private $API 					= NULL;


	/**
	 * EE, obviously
	 */
	private $EE 					= NULL;


	/**
	 * Reference to our cache
	 */
	public $cache 					= NULL;


	/**
	 * Our magical config class
	 */
	public $config;


	/**
	 * Boolean whether we are calling from template_post_parse
	 */
	public $calling_from_hook		= FALSE;


	/**
	 * What to return if error
	 */
	public $on_error				= '';


	/**
	 * Type of format/content to return
	 */
	public $out_delimiter			= array('embed' => "\n", 'url' => ',', 'tag' => '');


	/**
	 * Type of format/content to return
	 */
	public $out_type 				= '';


	/**
	 * Template with which to render css link or js script tags
	 */
	public $template				= '';


	/**
	 * Name of our queue, if running
	 */
	public $queue					= '';


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

		// grab instance of our config object
		$this->config = Minimee_helper::config();

		// instantiate our API
		$this->API = new Minimee_api($this->config);
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
		$this->on_error = $this->EE->TMPL->tagdata;

		// our asset type
		$this->API->type = 'css';

		// type of output
		$this->out_type = 'tag';

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
		$this->out_type = 'embed';

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
		$this->on_error = $this->EE->TMPL->tagdata;

		// our asset type
		$this->API->type = 'js';

		// type of output
		$this->out_type = 'tag';

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
		$this->out_type = 'url';

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
		$this->out_type = 'tag';

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
		if ($this->queue && isset($this->cache[$this->API->type][$this->queue]))
		{
			return $this->cache[$this->API->type][$this->queue]['on_error'];
		}
		else
		{
			return $this->on_error;
		}
	}
	// ------------------------------------------------------


	/** 
	 * Internal function to return contents of cache file
	 * 
	 * @return	Contents of cache (css or js)
	 */
	protected function _cache_contents($filename)
	{
		// silently get and return cache contents
		return @file_get_contents($this->_cache_path($filename));
	}
	// ------------------------------------------------------


	/** 
	 * Internal function for making link to cache
	 * 
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_path($filename)
	{
		// build link from cache url + cache filename
		return Minimee_helper::remove_double_slashes($this->config->cache_path . '/' . $filename, TRUE);
	}
	// ------------------------------------------------------


	/** 
	 * Internal function for making tag strings
	 * 
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_tag($filename)
	{
		// inject our cache url into template and return
		return str_replace('{minimee}', $this->_cache_url($filename), $this->template);
	}
	// ------------------------------------------------------

	
	/** 
	 * Internal function for making link to cache
	 * 
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_url($filename)
	{
		// build link from cache url + cache filename
		return Minimee_helper::remove_double_slashes($this->config->cache_url . '/' . $filename, TRUE);
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
		$this->template = str_replace($matches[1][0], '{minimee}', $matches[0][0]);
		
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
		if ( ! isset($this->cache[$this->API->type][$this->queue]))
		{
			throw new Exception('Could not find a queue of files by the name of \'' . $this->queue . '\'.');
		}

		// set our tag template
		$this->template = $this->cache[$this->API->type][$this->queue]['template'];
		
		// order by priority
		ksort($this->cache[$this->API->type][$this->queue]['filesdata']);

		$queue = array();
		// first reduce down to one array
		foreach($this->cache[$this->API->type][$this->queue]['filesdata'] as $fdata)
		{
			$queue = array_merge($queue, $fdata);
		}

		// now extract just the filenames to pass to API->set_filesdata
		$filesdata = array();
		foreach($queue as $fdata)
		{
			$filesdata[] = $fdata['name'];
		}

		// clear filesdata just in case
		$this->API->filesdata = array();

		// set our Minimee::filesdata array
		$this->API->set_filesdata($filesdata);

		// No files found?
		if ( ! is_array($this->API->filesdata) OR count($this->API->filesdata) == 0)
		{
			throw new Exception('No files found in the queue named \'' . $this->API->type . '\'.');
		}
		
		// cleanup
		unset($queue, $filesdata);
		
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
				
				if ( ! isset($this->cache['template_post_parse']))
				{
					$this->cache['template_post_parse'] = array();
				}
				
				$this->cache['template_post_parse'][$needle] = array(
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
	 * Return contents as determined by $this->out_type
	 * 
	 * @return mixed string or empty
	 */
	protected function _return($filenames)
	{
		// what we will eventually return
		$return = array();

		// cast to array for ease
		if( ! is_array($filenames))
		{
			$filenames = array($filenames);
		}

		foreach($filenames as $filename)
		{
			switch($this->out_type) :
				case 'embed' :
					$return[] = $this->_cache_contents($filename);
				break;

				case 'url' :
					$return[] = $this->_cache_url($filename);
				break;

				case 'tag' :
				default :
					$return[] = $this->_cache_tag($filename);
				break;

			endswitch;
		}

		// glue output based on type
		switch($this->out_type) :
			case 'embed' :
				return implode($this->out_delimiter[$this->out_type], $return);
			break;

			case 'url' :
				return implode($this->out_delimiter[$this->out_type], $return);
			break;

			case 'tag' :
			default :
				return implode($this->out_delimiter[$this->out_type], $return);
			break;
		endswitch;

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

			$filenames = $this->API->flightcheck()
							 ->check_headers()
							 ->cache();

			// format and return
			return $this->_return($filenames);

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
		if ( ! isset($this->cache[$this->API->type]))
		{
			$this->cache[$this->API->type] = array();
		}

		// create new session array for this queue
		if ( ! isset($this->cache[$this->API->type][$this->queue]))
		{
			$this->cache[$this->API->type][$this->queue] = array(
				'template' => $this->template,
				'on_error' => '',
				'filesdata' => array()
			);
		}
		
		// be sure we have a priority key in place
		$priority = (int) $this->EE->TMPL->fetch_param('priority', 0);
		if ( ! isset($this->cache[$this->API->type][$this->queue]['filesdata'][$priority]))
		{
			$this->cache[$this->API->type][$this->queue]['filesdata'][$priority] = array();
		}
		
		// Append $on_error
		$this->cache[$this->API->type][$this->queue]['on_error'] .= $this->on_error;

		// Add all files to the queue cache
		foreach($this->API->filesdata as $filesdata)
		{
			$this->cache[$this->API->type][$this->queue]['filesdata'][$priority][] = $filesdata;
		}
	}
	// ------------------------------------------------------

}
// END
	
/* End of file pi.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/pi.minimee.php */