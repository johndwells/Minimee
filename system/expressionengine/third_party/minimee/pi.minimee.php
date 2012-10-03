<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our Minimee_lib
require_once PATH_THIRD . 'minimee/classes/Minimee_lib.php';

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
	private $EE 					= NULL;


	/**
	 * Our Minimee_lib
	 */
	private $MEE 					= NULL;


	/**
	 * Reference to our cache
	 */
	public $cache 					= NULL;


	/**
	 * Our magical config class
	 */
	public $config 					= NULL;


	/**
	 * Boolean whether we are calling from template_post_parse
	 */
	public $calling_from_hook		= FALSE;


	/**
	 * Our local property of filenames to cache
	 */
	public $files					= array();


	/**
	 * Delimiter when fetching files from string
	 */
	public $files_delimiter			= ',';


	/**
	 * What to return if error
	 */
	public $on_error				= '';


	/**
	 * When combine="no", what to separate each cache return value with
	 */
	public $return_delimiter		= array('embed' => "\n", 'url' => ',', 'tag' => '');


	/**
	 * Type of format/content to return (embed, url or tag)
	 */
	public $return_format 			= '';


	/**
	 * Template with which to render css link or js script tags
	 */
	public $template				= '{minimee}';


	/**
	 * What type of asset to process
	 */
	public $type					= '';


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
	public function __construct($str = '')
	{
		// got EE?
		$this->EE =& get_instance();
		
		// grab reference to our cache
		$this->cache =& Minimee_helper::cache();

		// grab instance of our config object
		$this->config = Minimee_helper::config();

		// instantiate our Minimee_lib
		// pass our static config
		$this->MEE = new Minimee_lib($this->config);

		// magic: run as our "api"
		// $str would contain custom field content if used as a field modifier (e.g. {ft_stylesheet:minimee})
		// tagparts would have a length of 1 if calling minimee like {exp:minimee}...{/exp:minimee}
		if($str || count($this->EE->TMPL->tagparts) == 1)
		{
			$this->return_data = $this->api($str);
		}

	}
	// ------------------------------------------------------


	/**
	 * API-like interface
	 *
	 * @param String 	Filename of asset if being called as field modifier
	 * @return void
	 */
	public function api($asset = '')
	{
		if ($this->EE->TMPL->fetch_param('js'))
		{
			$assets = $this->EE->TMPL->fetch_param('js');
			$this->type = 'js';
		}

		if ($this->EE->TMPL->fetch_param('css'))
		{
			$assets = $this->EE->TMPL->fetch_param('css');
			$this->type = 'css';
		}

		$this->on_error = $assets;
		$this->return_format = 'url';

		// set parameters that affect config
		$this->_fetch_params();

		$this->_fetch_files($assets);

		// should we set our files to queue for later?
		if($this->queue)
		{
			return $this->_set_queue();
		}
		try
		{
			$filenames = $this->MEE->set_type($this->type)
								   ->set_filesdata($this->files)
								   ->flightcheck()
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
	 * Plugin function: exp:minimee:contents
	 * 
	 * Alias to exp:minimee:embed
	 */
	public function contents()
	{
		// alias of Minimee::embed()
		return $this->embed();
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
		$this->type = 'css';

		// type of output
		$this->return_format = 'tag';

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
		$this->return_format = 'embed';

		die('Add parameter for output tag parameters.');

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
		$this->type = 'js';

		// type of output
		$this->return_format = 'tag';

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
	 * Plugin function: exp:minimee:display
	 *
	 * Return the tags for cache
	 */
	public function tag()
	{
		// set output type
		$this->return_format = 'tag';

		// let's go
		return $this->_run('tag', TRUE);
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
		$this->return_format = 'url';

		// let's go
		return $this->_run('url', TRUE);
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
		if ($this->queue && isset($this->cache[$this->type][$this->queue]))
		{
			return $this->cache[$this->type][$this->queue]['on_error'];
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
	 * @return bool TRUE on success of fetching files; FALSE on failure
	 */
	protected function _fetch_files($haystack = FALSE)
	{
		if($haystack === FALSE)
		{
			$haystack = $this->EE->TMPL->tagdata;
		}

		// first up substitute stylesheet= for minimee=, because we handle these special
		$haystack = preg_replace("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", '[minimee=$1]', $haystack);

		// parse globals if we find any EE syntax tags
		if (preg_match("/".LD."(.*?)".RD."/", $haystack) === 1)
		{
			$haystack = $this->EE->TMPL->parse_globals($haystack);
		}

		// put {stylesheet=} back
		$haystack = preg_replace("/\[minimee=(.*?)\]/", LD . 'stylesheet=$1' . RD, $haystack);

		// try to match any pattern of css or js tag
		if ($matches = Minimee_helper::preg_match_by_type($haystack, $this->type))
		{
			// set our tag template
			$this->template = str_replace($matches[1][0], '{minimee}', $matches[0][0]);

			// set our files & filesdata arrays
			$this->files = $matches[1];
		}
		// no matches; assume entire haystack is our asset
		// no guarantees what will happen next...
		else
		{
			$this->files = explode($this->files_delimiter, $haystack);
		}

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
		/*
		 * Part 1: Parameters
		 */
		// set type
		$this->type = $this->EE->TMPL->fetch_param('type', $this->type);

		// set return format
		$this->return_format = $this->EE->TMPL->fetch_param('return_format', $this->return_format);

		// set/override return delimiter
		if($this->return_format)
		{
			$this->return_delimiter[$this->return_format] = $this->EE->TMPL->fetch_param('return_delimiter', $this->return_delimiter[$this->return_format]);
		}

		/*
		 * Part 2: config
		 */
		$tagparams = $this->EE->TMPL->tagparams;
		
		// we do need to account for the fact that minify="no" is assumed to be pertaining to the tag
		if (isset($tagparams['combine']))
		{
			$tagparams['combine_' . $this->type] = $tagparams['combine'];
		}
		
		if (isset($tagparams['minify']))
		{
			$tagparams['minify_' . $this->type] = $tagparams['minify'];
		}
		
		// pass all params through our config, will magically pick up what's needed
		$this->MEE->config->reset()->extend($tagparams);

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
		if ( ! isset($this->cache[$this->type][$this->queue]))
		{
			Minimee_helper::log('Could not find a queue of files by the name of \'' . $this->queue . '\'.', 3);
		}

		else
		{
			// re-set our tag template
			$this->template = $this->cache[$this->type][$this->queue]['template'];

			// re-set what to return on error
			$this->on_error = $this->cache[$this->type][$this->queue]['on_error'];

			// TODO: re-set other runtime properties
			
			// order by priority
			ksort($this->cache[$this->type][$this->queue]['filesdata']);

			// flatten to one array
			$this->files = array();
			foreach($this->cache[$this->type][$this->queue]['filesdata'] as $file)
			{
				$this->files = array_merge($this->files, $file);
			}

			// No files found?
			if ( ! is_array($this->files) OR count($this->files) == 0)
			{
				Minimee_helper::log('No files found in the queue named \'' . $this->type . '\'.', 3);
			}
		}
		
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
	 * Return contents as determined by $this->return_format
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
			switch($this->return_format) :
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
		switch($this->return_format) :
			case 'embed' :
				return implode($this->return_delimiter[$this->return_format], $return);
			break;

			case 'url' :
				return implode($this->return_delimiter[$this->return_format], $return);
			break;

			case 'tag' :
			default :
				return implode($this->return_delimiter[$this->return_format], $return);
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

		// should we be operating off file(s) stored in queue?
		if($from_queue)
		{
			if ($this->EE->TMPL->fetch_param('js'))
			{
				$this->queue = $this->EE->TMPL->fetch_param('js');
				$this->type = 'js';
			}

			if ($this->EE->TMPL->fetch_param('css'))
			{
				$this->queue = $this->EE->TMPL->fetch_param('css');
				$this->type = 'css';
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

		try
		{
			$filenames = $this->MEE->set_type($this->type)
								   ->set_filesdata($this->files)
								   ->flightcheck()
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
		if ( ! isset($this->cache[$this->type]))
		{
			$this->cache[$this->type] = array();
		}

		// create new session array for this queue
		if ( ! isset($this->cache[$this->type][$this->queue]))
		{
			$this->cache[$this->type][$this->queue] = array(
				'template' => $this->template,
				'on_error' => '',
				'filesdata' => array()
			);
		}
		
		// be sure we have a priority key in place
		$priority = (int) $this->EE->TMPL->fetch_param('priority', 0);
		if ( ! isset($this->cache[$this->type][$this->queue]['filesdata'][$priority]))
		{
			$this->cache[$this->type][$this->queue]['filesdata'][$priority] = array();
		}
		
		// Append $on_error
		$this->cache[$this->type][$this->queue]['on_error'] .= $this->on_error;

		// TODO: save other runtime properties

		// Add all files to the queue cache
		foreach($this->files as $file)
		{
			$this->cache[$this->type][$this->queue]['filesdata'][$priority][] = $file;
		}
	}
	// ------------------------------------------------------

}
// END
	
/* End of file pi.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/pi.minimee.php */