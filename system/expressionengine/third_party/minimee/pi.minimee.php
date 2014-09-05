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
	 * Reference to our cache
	 */
	private $cache 					= NULL;


	/**
	 * Our magical config class
	 */
	private $config 				= NULL;


	/**
	 * EE, obviously
	 */
	private $EE 					= NULL;


	/**
	 * Our Minimee_lib
	 */
	private $MEE 					= NULL;


	/**
	 * An array of attributes to use when wrapping cache contents in a tag
	 */
	public $attributes				= '';


	/**
	 * Delimiter when exploding files from string
	 */
	public $delimiter				= ',';


	/**
	 * Type of format/content to return (contents, url or tag)
	 */
	public $display 				= '';


	/**
	 * When combine="no", what to separate each cache return value with
	 */
	public $display_delimiter		= array('contents' => "\n", 'url' => ',', 'tag' => '');


	/**
	 * Our local property of filenames to cache
	 */
	public $files					= array();


	/**
	 * What to return if error
	 */
	public $on_error				= '';


	/**
	 * Name of our queue, if running
	 */
	public $queue					= '';


	/**
	 * Template with which to render css link or js script tags
	 */
	public $template				= '{minimee}';


	/**
	 * What type of asset to process
	 */
	public $type					= '';


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

		// instantiate our Minimee_lib, pass our static config
		$this->MEE = new Minimee_lib($this->config);

		// magic: run as our "api"
		// Tagparts would have a length of 1 if calling minimee like {exp:minimee}...{/exp:minimee}
		// $str would contain custom field content if used as a field modifier (e.g. {ft_stylesheet:minimee})
		// Note that this is entirely untested and undocumented, and would require passing a type=""
		// parameter so that Minimee knows what sort of asset it's operating on. But in theory it's possible.
		if(count($this->EE->TMPL->tagparts) == 1 || $str)
		{
			$this->return_data = $this->api($str);
		}

		Minimee_helper::log('Minimee instantiated', 3);
	}
	// ------------------------------------------------------


	/**
	 * API-like interface
	 *
	 * @param String 	Filename(s) of asset(s) IFF being called as field modifier
	 * @return void
	 */
	public function api($assets = '')
	{
		// a custom field modifier needs a type
		if ($assets != '' && ! $this->EE->TMPL->fetch_param('type'))
		{
			$this->on_error = $assets;
			return $this->_abort('You must specify a type (css or js) when using custom field modifier.');
		}

		// can't specify css and js at same time
		if ($assets == '' && $this->EE->TMPL->fetch_param('css') && $this->EE->TMPL->fetch_param('js'))
		{
			// this will be horribly wrong, but it's at least something
			$this->on_error = $this->EE->TMPL->fetch_param('css') . "\n" . $this->EE->TMPL->fetch_param('js');
			return $this->_abort('You may not specify css="" and js="" in the same API call.');
		}

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

		// set our display format
		$this->_set_display();

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
			$filenames = $this->MEE->run($this->type, $this->files);

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
	 * @return mixed string or empty
	 */
	public function contents()
	{
		return $this->display('contents');
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

		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:display
	 * 
	 * @param string type of display to return
	 * @param bool true or false whether calling from template_post_parse hook
	 * @return mixed string or empty
	 */
	public function display($method = '', $calling_from_hook = FALSE)
	{
		// abort error if no queue was provided		
		if ( ! $this->EE->TMPL->fetch_param('js') && ! $this->EE->TMPL->fetch_param('css'))
		{
			return $this->_abort('You must specify a queue name.');
		}

		// see if calling via exp:minimee:display:method syntax
		$this->_set_display($method);

		// try to postpone until template_post_parse
		if ( ! $calling_from_hook && $out = $this->_postpone($this->display))
		{
			return $out;
		}

		// walk through both types
		$return = '';

		// now determine what asset type, and fetch our queue
		if ($this->EE->TMPL->fetch_param('js'))
		{
			$this->queue = $this->EE->TMPL->fetch_param('js');
			$this->type = 'js';

			$return .= $this->_display();
		}

		if ($this->EE->TMPL->fetch_param('css'))
		{
			$this->queue = $this->EE->TMPL->fetch_param('css');
			$this->type = 'css';

			$return .= $this->_display();
		}

		return $return;
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:embed
	 * 
	 * Alias of exp:minimee:contents
	 * 
	 * @return mixed string or empty
	 */
	public function embed()
	{
		return $this->display('contents');
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

		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:link
	 * 
	 * Alias to exp:minimee:url
	 * 
	 * @return mixed string or empty
	 */
	public function link()
	{
		return $this->display('url');
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:tag
	 *
	 * Return the tags for cache
	 * 
	 * @return mixed string or empty
	 */
	public function tag()
	{
		return $this->display('tag');
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:url
	 * 
	 * Rather than returning the tags or cache contents, simply return URL to cache(s)
	 * 
	 * @return mixed string or empty
	 */
	public function url()
	{
		return $this->display('url');
	}
	// ------------------------------------------------------

	
	/**
	 * Display usage notes in EE control panel
	 *
	 * @return string Usage notes
	 */	
	public static function usage()
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
	 * @param mixed The caught exception or string
	 * @return string The value of our Minimee::on_error property
	 */	
	protected function _abort($e = FALSE)
	{
		if ($e && is_string($e))
		{
			$log = $e;
		}
		elseif ($e)
		{
			$log = $e->getMessage();
		}
		else
		{
			$log = 'Aborted without a specific error.';
		}

		// log our error message
		Minimee_helper::log($log, 1);

		// return our on_error content
		return $this->on_error;
	}
	// ------------------------------------------------------


	/** 
	 * Internal function to return contents of cache file
	 * 
	 * @return	Contents of cache (css or js)
	 */
	protected function _cache_contents($filename)
	{
		$open = $close = '';

		// If attributes have been supplied, it is inferred we should wrap
		// our output in tags
		if($this->attributes)
		{
			switch($this->type) :
				case 'css' :
					$open = '<style' . $this->attributes . '>';
					$close = '</style>';
				break;

				case 'js' :
					$open = '<script' . $this->attributes . '>';
					$close = '</script>';
				break;
			endswitch;
		}

		// silently get and return cache contents, wrapped in open and close
		return $open . @file_get_contents($this->_cache_path($filename)) . $close;
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
		$tmpl = $this->template;
		if($tmpl == '' || $tmpl == '{minimee}' || $this->attributes)
		{
			switch($this->type) :
				case 'css' :
					$tmpl = '<link href="{minimee}"' . $this->attributes . ' />';
				break;

				case 'js' :
					$tmpl = '<script src="{minimee}"' . $this->attributes . '></script>';
				break;
			endswitch;
		}

		// inject our cache url into template and return
		return str_replace('{minimee}', $this->_cache_url($filename), $tmpl);
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
	 * Internal function used by exp:minimee:display
	 * 
	 * @return mixed string or empty
	 */
	protected function _display()
	{
		// fetch our parameters
		$this->_fetch_params();

		// fetch from our queue
		$this->_fetch_queue();

		// let's do this
		try
		{
			$filenames = $this->MEE->run($this->type, $this->files);

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

			// set our files array
			$this->files = $matches[1];
		}
		// no matches; assume entire haystack is our asset
		// this should only happen when using API interface
		else
		{
			$this->files = explode($this->delimiter, $haystack);
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
		 * Part 1: Parameters which may override defaults
		 */
		// set type
		$this->type = $this->EE->TMPL->fetch_param('type', $this->type);

		// override display format
		$this->display = $this->EE->TMPL->fetch_param('display', $this->display);

		// override delimiters?
		if( $this->EE->TMPL->fetch_param('delimiter'))
		{
			$this->delimiter = $this->EE->TMPL->fetch_param('delimiter');
			$this->display_delimiter[$this->display] = $this->EE->TMPL->fetch_param('delimiter');
		}

		// display delimiter may also be specified
		$this->display_delimiter[$this->display] = $this->EE->TMPL->fetch_param('display_delimiter', $this->display_delimiter[$this->display]);

		// tag attributes for returning cache contents
		if(is_array($this->EE->TMPL->tagparams))
		{
			foreach($this->EE->TMPL->tagparams as $key => $val)
			{
				if(strpos($key, 'attribute:') === 0)
				{
					$this->attributes .= ' ' . substr($key, 10) . '="' . $val . '"';
				}
			}
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
			// set our tag template
			$this->template = $this->cache[$this->type][$this->queue]['template'];

			// TODO: re-set other runtime properties

			// files: order by priority
			ksort($this->cache[$this->type][$this->queue]['files']);

			// build our files property
			foreach($this->cache[$this->type][$this->queue]['files'] as $file)
			{
				$this->files = array_merge($this->files, $file);
			}

			// on_error: order by priority
			ksort($this->cache[$this->type][$this->queue]['on_error']);

			// build our on_error property
			foreach($this->cache[$this->type][$this->queue]['on_error'] as $error)
			{
				$this->on_error .= implode("\n", $error) . "\n";
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


	protected function _flightcheck()
	{
		// Manually disabled?
		if ($this->config->is_yes('disable'))
		{
			// we can actually figure out if it's a runtime setting or default
			$runtime = $this->config->get_runtime();
			
			if (isset($runtime['disable']) && $runtime['disable'] == 'yes')
			{

				throw new Exception('Disabled via tag parameter.');
			}
			else
			{
				throw new Exception('Disabled via config.');
			}
		}
	}	
	
	/**
	 * Postpone processing our method until template_post_parse hook?
	 * 
	 * @param String	Method name
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
			
			Minimee_helper::log('Postponing process of Minimee::display(`' . $method . '`) until template_post_parse hook.', 3);
			
			// return needle so we can find it later
			return LD.$needle.RD;
		}
	}
	// ------------------------------------------------------


	/**
	 * Reset class properties to their defaults
	 * 
	 * @return mixed string or empty
	 */
	public function reset()
	{
		$defaults = Minimee_helper::minimee_class_vars();

		foreach ($defaults as $name => $default)
		{
			$this->$name = $default;
		}

		Minimee_helper::log('Public properties have been reset to their defaults.', 3);

		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Return contents as determined by $this->display
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
			switch($this->display) :
				case 'contents' :
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
		return implode($this->display_delimiter[$this->display], $return);
	}
	// ------------------------------------------------------


	/**
	 * Called by Minimee:css and Minimee:js, performs basic run command
	 * 
	 * @return mixed string or empty
	 */
	protected function _run()
	{
		// set our return format
		$this->_set_display();

		// fetch our parameters
		$this->_fetch_params();

		// fetch our files
		$this->_fetch_files();

		// quick flightcheck
		try
		{
			$this->_flightcheck();
		}
		catch (Exception $e)
		{
			return $this->_abort($e);
		}

		// should we set our files to queue for later?
		if($this->queue)
		{
			return $this->_set_queue();
		}

		// let's do this
		try
		{
			$filenames = $this->MEE->run($this->type, $this->files);

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
	 * Set our display property
	 * 
	 * @return void
	 */
	protected function _set_display($format = '')
	{
		// if not passed, fetch last tagpart
		if( ! $format)
		{
			$format = $this->EE->TMPL->tagparts[count($this->EE->TMPL->tagparts) - 1];
		}

		// consolidate our aliases into allowed methods
		switch($format) :
			case 'minimee' :
			case 'url' :
			case 'link' :
				$this->display = 'url';
			break;

			case 'contents' :
			case 'embed' :
				$this->display = 'contents';
			break;

			case 'css' :
			case 'js' :
			case 'tag' :
			case 'display' :
			default :
				$this->display = 'tag';
			break;
		endswitch;
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
				'on_error' => array(),
				'files' => array()
			);
		}
		
		// be sure we have a priority key in place
		$priority = (int) $this->EE->TMPL->fetch_param('priority', 0);
		if ( ! isset($this->cache[$this->type][$this->queue]['files'][$priority]))
		{
			$this->cache[$this->type][$this->queue]['files'][$priority] = array();
		}
		
		// Add $on_error
		if ( ! isset($this->cache[$this->type][$this->queue]['on_error'][$priority]))
		{
			$this->cache[$this->type][$this->queue]['on_error'][$priority] = array();
		}
		$this->cache[$this->type][$this->queue]['on_error'][$priority][] = $this->on_error;

		// TODO: save other runtime properties

		// Add all files to the queue cache
		foreach($this->files as $file)
		{
			$this->cache[$this->type][$this->queue]['files'][$priority][] = $file;
		}
	}
	// ------------------------------------------------------

}
// END
	
/* End of file pi.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/pi.minimee.php */