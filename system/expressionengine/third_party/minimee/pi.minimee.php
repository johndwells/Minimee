<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'minimee/classes/Minimee_helper.php';

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
	 * runtime variables
	 */
	public $cache_lastmodified		= '';		// lastmodified value for cache
	public $cache_filename			= '';		// eventual filename of cache
	public $calling_from_hook		= FALSE;	// Boolean of whether calling from template_post_parse
	public $filesdata				= array();	// array of assets to process
	public $queue					= '';		// name of queue, if running
	public $remote_mode				= '';		// 'fgc' or 'curl'
	public $stylesheet_query		= FALSE;	// Boolean of whether to fetch stylesheets from DB
	public $template				= '';		// the template with which to render css link or js script tags
	public $type					= '';		// 'css' or 'js'


	/**
	 * Our magical config class
	 */
	public $config;


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

		// grab instance of our config object
		$this->config = Minimee_helper::config();
	}
	// ------------------------------------------------------


	/**
	 * Plugin function: exp:minimee:css
	 * 
	 * @return mixed string or empty
	 */
	public function css()
	{
		$this->type = 'css';
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
		// try to postpone until template_post_parse
		if ($out = $this->_postpone('display'))
		{
			return $out;
		}
	
		// see which to display
		$js = strtolower($this->EE->TMPL->fetch_param('js'));
		$css = strtolower($this->EE->TMPL->fetch_param('css'));
		$out = '';

		if ($js)
		{
			$this->queue = $js;
			$this->type = 'js';
			$out .= $this->_display();
		}

		if ($css)
		{
			$this->queue = $css;
			$this->type = 'css';
			$out .= $this->_display();
		}

		// free memory where possible
		unset($js, $css);
		
		return $out;
	}
	// ------------------------------------------------------
	
	
	/**
	 * Plugin function: exp:minimee:embed
	 * 
	 * @return mixed string or empty
	 */
	public function embed()
	{
		// try to postpone until template_post_parse
		if ($out = $this->_postpone('embed'))
		{
			return $out;
		}
	
		// make sure only one is being specified
		if ($this->EE->TMPL->fetch_param('js') && $this->EE->TMPL->fetch_param('css'))
		{
			return $this->_abort('When using exp:minimee:embed, you may not specify JS and CSS file types together.');
		}

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
			return $this->_abort('When using exp:minimee:embed, you must specify a queue name.');
		}
		
		/**
		 * Processes things like normal, but at the last minute
		 * we grab the contents of the cached file, and return directly to our template.
		 */
		try
		{
			// this is what we'd normally return to a template
			$out = $this->_fetch_params()
						->_fetch_queue()
						->_flightcheck()
						->_check_headers()
						->_process();
	
			// now find links to cached assets
			$matches = Minimee_helper::preg_match_by_type($out, $this->type);
			if ( ! $matches)
			{
				throw new Exception('No files found to return.');
			}
			
			// replace the url with path
			$paths = Minimee_helper::replace_url_with_path($M->config->cache_url, $M->config->cache_path, $matches[1]);
	
			// clear $out so we can replace with code to embed
			$out = '';
	
			// fetch contents of each file
			foreach ($paths as $path)
			{
				// strip timestamp
				if (strpos($path, '?') !== FALSE)
				{
					$name = substr($path, 0, strpos($path, '?'));
				}
				
				// there's no way this doesn't exist... right?
				$out .= file_get_contents($name) . "\n";
			}
	
			// free memory where possible
			unset($pat, $haystack, $matches, $paths);
	
			return $out;
		}
		catch (Exception $e)
		{
			return $this->_abort($e);
		}
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
		$this->type = 'js';
		return $this->_run();
	}
	// ------------------------------------------------------


	/**
	 * Rather than returning the tags or cache contents, simply return a link to cache(s)
	 */
	public function link()
	{
		// try to postpone until template_post_parse
		if ($out = $this->_postpone('link'))
		{
			return $out;
		}
	
		// make sure only one is being specified
		if ($this->EE->TMPL->fetch_param('js') && $this->EE->TMPL->fetch_param('css'))
		{
			return $this->_abort('When using exp:minimee:link, you may not specify JS and CSS file types together.');
		}

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

		// abort early if no queue was provided		
		if ( ! $this->queue)
		{
			return $this->_abort('When using exp:minimee:link, you must specify a queue name.');
		}
		
		/**
		 * Processes things like normal, but only return the links to cache file(s).
		 */
		try
		{
			// this is what we'd normally return to a template
			$out = $this->_fetch_params()
						->_fetch_queue()
						->_flightcheck()
						->_check_headers()
						->_process();

			// now find links to cached assets
			$matches = Minimee_helper::preg_match_by_type($out, $this->type);
	
			if ( ! $matches)
			{
				throw new Exception('No files found to return.');
			}

			// return a pipe-delimited string
			return implode('|', $matches[1]);
		}
		catch (Exception $e)
		{
			return $this->_abort($e);
		}
	}
	// ------------------------------------------------------

	
	/**
	 * Display usage notes in EE control panel
	 *
	 * @return string Usage notes
	 */	
	public function usage()
	{
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
	<script type="text/javascript" src="scripts/jquery.form.js"></script>
	<script type="text/javascript" src="scripts/jquery.easing.1.3.js"></script>
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

		// Let's return the original tagdata, wherever it came from
		if ($this->queue && array_key_exists($this->queue, $this->cache[$this->type]))
		{
			return $this->cache[$this->type][$this->queue]['tagdata'];
		}
		else
		{
			return $this->EE->TMPL->tagdata;
		}
	}
	// ------------------------------------------------------
	
	
	/** 
	 * Internal function for making tag strings
	 * [Adapted from CodeIgniter Carabiner library]
	 * 
	 * @return	String containing an HTML tag reference to given reference
	 */
	protected function _cache_tag()
	{
		// for clarity, use manual cachebust if provided
		if ($this->config->cachebust)
		{
			$cachebust = '?m=' . $this->config->cachebust;
		}
		
		// create cachebust from lastmodified
		else
		{
			// if $lastmodified is zero, there's no point in using it right?
			$cachebust = ($this->cache_lastmodified > 0) ? '?m=' . $this->cache_lastmodified : '';
		}

		// construct url
		$url = Minimee_helper::remove_double_slashes($this->config->cache_url . '/' . $this->cache_filename . $cachebust, TRUE);

		return str_replace('{minimee}', $url, $this->template);
	}
	// ------------------------------------------------------


	/**
	 * Find out more info about each file
	 * Attempts to get file modification times, determine what files exist, etc
	 * 
	 * @return bool TRUE if all are found; FALSE if at least one is not found
	 */
	protected function _check_headers()
	{
		// query for any stylesheets	
		$stylesheet_versions = $this->_fetch_stylesheet_versions();
		
		// temporarily store runtime settings
		$runtime = $this->config->get_runtime();

		// now, loop through our filesdata and set all headers	
		foreach ($this->filesdata as $key => $file) :
		
			// file runtime settings can be overridden by tag runtime settings
			$this->config->reset()->extend($this->filesdata[$key]['runtime'])->extend($runtime);

			switch ($this->filesdata[$key]['type']) :
			
				/**
				 * Stylesheets (e.g. {stylesheet='template/file'}
				 */
				case('stylesheet') :

					// the stylesheet matches one we've found in db
					if ($stylesheet_versions && array_key_exists($this->filesdata[$key]['stylesheet'], $stylesheet_versions))
					{
						// transform name out of super global and into valid URL
						$this->filesdata[$key]['name'] = $this->EE->functions->fetch_site_index().QUERY_MARKER.'css='.$this->filesdata[$key]['stylesheet'].(($this->EE->config->item('send_headers') == 'y') && isset($stylesheet_versions[$this->filesdata[$key]['stylesheet']]) ? '.v.'.$stylesheet_versions[$this->filesdata[$key]['stylesheet']] : '');
						$this->filesdata[$key]['lastmodified'] = $stylesheet_versions[$this->filesdata[$key]['stylesheet']];
	
						Minimee_helper::log('Headers OK for stylesheet template: `' . $this->filesdata[$key]['stylesheet'] . '`.', 3);
					}
	
					// couldn't find stylesheet in db
					else
					{
						throw new Exception('Missing stylesheet template: ' . $this->filesdata[$key]['stylesheet']);
					}
					
				break;

				/**
				 * Remote files
				 * All we can do for these is test if the file is in fact local
				 */
				case('remote') :

					// let's strip out all variants of our base url
					$local = Minimee_helper::replace_url_with_path($M->config->base_url, $M->config->base_path, $file['name']);
	
					// the filename needs to be without any cache-busting or otherwise $_GETs
					if ($position = strpos($local, '?'))
					{
						$local = substr($local, 0, $position);
					}
					
					$realpath = realpath(Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $local));
	
					// if the $local file exists, let's alter the file type & name, and calculate lastmodified
					if (file_exists($realpath))
					{
						$this->filesdata[$key]['name'] = $local;
						$this->filesdata[$key]['type'] = 'local';
						
						$this->filesdata[$key]['lastmodified'] = filemtime($realpath);
	
						Minimee_helper::log('Treating `' . $file['name'] . '` as a local file: `' . $this->filesdata[$key]['name'] . '`', 2);
					}
					
					// nope; keep as remote
					else
					{
						Minimee_helper::log('Processing remote file: `' . $file['name'] . '`.', 3);
					}
	
				break;
				
				/**
				 * Local files
				 */
				default:

					// the filename needs to be without any cache-busting or otherwise $_GETs
					if ($position = strpos($this->filesdata[$key]['name'], '?'))
					{
						$this->filesdata[$key]['name'] = substr($this->filesdata[$key]['name'], 0, $position);
					}
					
					$realpath = realpath(Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $this->filesdata[$key]['name']));

					if (file_exists($realpath))
					{
						$this->filesdata[$key]['lastmodified'] = filemtime($realpath);
		
						Minimee_helper::log('Headers OK for file: `' . $this->filesdata[$key]['name'] . '`.', 3);
					}
					else
					{
						throw new Exception('Missing local file: ' . Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $this->filesdata[$key]['name']));
					}
				break;
			endswitch;

		endforeach;

		// return our settings to our runtime
		$this->config->reset()->extend($runtime);

		// free memory where possible
		unset($runtime, $stylesheet_versions);
		
		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Performs heavy lifting of creating our cache
	 * 
	 * @return string The final tag to be returned to template
	 */	
	protected function _create_cache()
	{
		// set to empty string
		$out = '';

		// the eventual contents of our cache
		$cache = '';
		
		// the contents of each file
		$contents = '';
		
		// the relative path for each file
		$css_prepend_url = '';
		
		// save our runtime settings temporarily
		$runtime = $this->config->get_runtime();
		
		foreach ($this->filesdata as $key => $file) :
		
			// file runtime settings can be overridden by tag runtime settings
			$this->config->reset()->extend($file['runtime'])->extend($runtime);
		
			switch ($file['type']) :
	
				case ('stylesheet');
				case ('remote') :
				
					// no relative paths for either types
					$css_prepend_url = FALSE;
					
					// fgc & curl both need http(s): on front
					// so if ommitted, prepend it manually, based on requesting protocol
					if (strpos($file['name'], '//') === 0)
					{
						$prefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https:' : 'http:';
						Minimee_helper::log('Manually prepending protocol `' . $prefix . '` to front of file `' . $file['name'] . '`', 2);
						$file['name'] = $prefix . $file['name'];
					}
					
					// determine how to fetch contents
					switch ($this->remote_mode)
					{
						case ('fgc') :
							// I hate to suppress errors, but it's only way to avoid one from a 404 response
							$response = @file_get_contents($file['name']);
							if ($response && isset($http_response_header) && (substr($http_response_header[0], 9, 3) < 400))
							{
								$contents = $response;
							}
							else
							{
								throw new Exception('A problem occurred while fetching the following over file_get_contents(): ' . $file['name']);
							}
							
						break;
						
						case ('curl') :

							if ( ! isset($epicurl))
							{
								Minimee_helper::library('curl');
								$epicurl = EpiCurl::getInstance();
							}
							
							$ch = FALSE;
							$ch = curl_init($file['name']);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
							$curls[$key] = $epicurl->addCurl($ch);

							if ($curls[$key]->code >= 400)
							{
								throw new Exception('Error encountered while fetching `' . $file['name'] . '` over cURL.');
							}
							
							if ( ! $curls[$key]->data)
							{
								throw new Exception('An unknown error encountered while fetching `' . $file['name'] . '` over cURL.');
							}
							
							$contents = $curls[$key]->data;
							
						break;
						
						default :
							throw new Exception('Could not fetch file `' . $file['name'] . '` because neither cURL or file_get_contents() appears available.');
						break;
					}
					
				break;
				
				case ('local') :
				default :
				
					// grab contents of file
					$contents = file_get_contents(realpath(Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $file['name']))) . "\n";
					
					// determine css prepend url
					$css_prepend_url = ($this->config->css_prepend_url) ? $this->config->css_prepend_url : $this->config->base_url;
					$css_prepend_url = dirname(Minimee_helper::remove_double_slashes($css_prepend_url . '/' . $file['name'], TRUE));
				break;
	
			endswitch;

			// Let's log a warning message if the contents of file are empty
			if ( ! $contents)
			{
				Minimee_helper::log('The contents from `' . $file['name'] . '` were empty.', 2);
			}
			
			else
			{
				Minimee_helper::log('Fetched contents of `' . $file['name'] . '`.', 3);
			}
			
			// minify contents and append to $cache
			$cache .= $this->_minify($contents, $file['name'], $css_prepend_url);
			
		endforeach;

		// return our settings to our runtime
		$this->config->reset()->extend($runtime);

		// write our cache file
		$this->_write_cache($cache);

		// create our output tag
		$out = $this->_cache_tag();

		// free memory where possible
		unset($cache, $contents, $css_prepend_url, $runtime);

		// return output tag
		return $out;
	}
	// ------------------------------------------------------
	
	
	/** 
	 * Utility method
	 * 
	 * @param string file name
	 * @return string
	 */
	protected function _create_cache_name($name)
	{
		// remove any cache-busting strings so the cache name doesn't change with every edit.
		// format: .v.1330213450
		$name = preg_replace('/\.v\.(\d+)/i', '', $name);

		// remove any variations of our base url
		$base_url = substr($this->config->base_url, strpos($this->config->base_url, '//') + 2, strlen($this->config->base_url));
		$name = preg_replace('@(https?:)?\/\/' . $base_url . '@', '', $name);


		Minimee_helper::log('Creating cache name from `' . $name . '`', 3);

		// base cache name on config settings, so that changing config will create new cache!
		return md5($name . serialize($this->config->to_array())) . '.' . $this->type;
	}
	// ------------------------------------------------------
	

	/**
	 * Processes and displays queue
	 *
	 * @return string The final output from Minimee::out()
	 */	
	protected function _display()
	{
		try
		{
			return $this->_fetch_params()
						->_fetch_queue()
						->_flightcheck()
						->_check_headers()
						->_process();

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
	 * @param string tagdata
	 * @param string either css or js
	 * @return bool TRUE on success of fetching files; FALSE on failure
	 */
	protected function _fetch_files($haystack)
	{
		// first up, let's substitute stylesheet= for minimee=, because we handle these special
		$haystack = preg_replace("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", '[minimee=$1]', $haystack);

		// parse globals if we find any EE syntax tags
		if (preg_match("/".LD."(.*?)".RD."/", $haystack) === 1)
		{
			$haystack = $this->EE->TMPL->parse_globals($haystack);
		}

		// try to match any pattern of css or js tag
		$matches = Minimee_helper::preg_match_by_type($haystack, $this->type);
		if ( ! $matches)
		{
			throw new Exception('No ' . $this->type . ' files found to return.');
		}

		// set our tag template
		$this->template = str_replace($matches[1][0], '{minimee}', $matches[0][0]);
		
		// set our files & filesdata arrays
		$this->_set_filesdata($matches[1]);

		// free memory where possible
		unset($haystack, $pat, $matches);
		
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
			$tagparams['combine_' . $this->type] = $tagparams['combine'];
		}
		
		if (isset($tagparams['minify']))
		{
			$tagparams['minify_' . $this->type] = $tagparams['minify'];
		}
		
		// pass all params through our config, will magically pick up what's needed
		$this->config->reset()->extend($tagparams);

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
			throw new Exception('Could not find a queue of files by the name of \'' . $this->queue . '\'.');
		}

		// clear filesdata just in case
		$this->filesdata = array();

		// set our tag template
		$this->template = $this->cache[$this->type][$this->queue]['template'];
		
		// order by priority
		ksort($this->cache[$this->type][$this->queue]['filesdata']);
		
		// now reduce down to one array
		$filesdata = array();
		foreach($this->cache[$this->type][$this->queue]['filesdata'] as $fdata)
		{
			$filesdata = array_merge($filesdata, $fdata);
		}
		
		// set our Minimee::filesdata array
		$this->_set_filesdata($filesdata, TRUE);

		// No files found?
		if ( ! is_array($this->filesdata) OR count($this->filesdata) == 0)
		{
			throw new Exception('No files found in the queue named \'' . $this->type . '\'.');
		}
		
		// cleanup
		unset($filesdata);
		
		// chaining
		return $this;
	}
	// ------------------------------------------------------
	

	/**
	 * Query DB for any stylesheets
	 * Borrowed from $EE->TMPL->parse_globals(): ./system/expressionengine/libraries/Template.php
	 *
	 * @return mixed array or FALSE
	 */
	protected function _fetch_stylesheet_versions() {
	
		// nothing to do if Minimee::stylesheet_query is FALSE
		if ( ! $this->stylesheet_query) return FALSE;

		// let's only do this once per session
		if ( ! isset($this->cache['stylesheet_versions']))
		{
			$versions = array();
			
			$sql = "SELECT t.template_name, tg.group_name, t.edit_date, t.save_template_file FROM exp_templates t, exp_template_groups tg
					WHERE  t.group_id = tg.group_id
					AND    t.template_type = 'css'
					AND    t.site_id = '".$this->EE->db->escape_str($this->EE->config->item('site_id'))."'";
		
			$css_query = $this->EE->db->query($sql);
			
			if ($css_query->num_rows() > 0)
			{
				foreach ($css_query->result_array() as $row)
				{
					$versions[$row['group_name'].'/'.$row['template_name']] = $row['edit_date'];
	
					if ($this->EE->config->item('save_tmpl_files') == 'y' AND $this->EE->config->item('tmpl_file_basepath') != '' AND $row['save_template_file'] == 'y')
					{
						$basepath = $this->EE->config->slash_item('tmpl_file_basepath').$this->EE->config->item('site_short_name').'/';
						$basepath .= $row['group_name'].'.group/'.$row['template_name'].'.css';
						
						if (is_file($basepath))
						{
							$versions[$row['group_name'].'/'.$row['template_name']] = filemtime($basepath);
						}
					}
				}
				
				// now save our versions info to cache
				$this->cache['stylesheet_versions'] = $versions;

				Minimee_helper::log('Stylesheet templates found in DB, and saved to cache.', 3);
			}
			else
			{
				// record fact that no stylesheets were found
				$this->cache['stylesheet_versions'] = FALSE;

				Minimee_helper::log('No stylesheet templates were found in DB.', 2);
			}
			
			// free memory where possible
			$css_query->free_result();			
			unset($sql, $versions);
		}
		
		// return whatever we've saved in cache
		return $this->cache['stylesheet_versions'];
	}
	// ------------------------------------------------------


	/**
	 * Flightcheck - make some basic config checks before proceeding
	 *
	 * @return void
	 */
	protected function _flightcheck()
	{
		/**
		 * If our cache path appears relative, append it to our base path
		 */
		if (strpos($this->config->cache_path, '/') !== 0)
		{
			$this->config->cache_path = $this->config->base_path . '/' . $this->config->cache_path;
		}

		/**
		 * If our cache url appears relative, append it to our base url
		 */
		if (strpos($this->config->cache_url, '//') !== 0 && ! preg_match("#https?://#", $this->config->cache_url))
		{
			$this->config->cache_url = $this->config->base_url . '/' . $this->config->cache_url;
		}

		/**
		 * Determine our runtime remote_mode setting
		 */
		$this->_set_remote_mode();

	
		// Flightcheck: determine if we can continue or disable permanently
		switch ('flightcheck') :

			case ($this->config->is_yes('disable')) :
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
				
			break;

			case ( ! file_exists($this->config->cache_path)) :
			case ( ! is_writable($this->config->cache_path)) :
				throw new Exception('Not configured correctly: your cache folder `' . $this->config->cache_path . '` does not exist or is not writable.');
			break;

			default :
				Minimee_helper::log('Passed flightcheck.', 3);
			break;

		endswitch;
		
		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Internal function to look for cache file(s)
	 * 
	 * @return mixed String of final tag output or FALSE if cache needs to be refreshed
	 */
	protected function _get_cache()
	{
		// our return variable
		$out = '';

		// loop through our files once
		foreach ($this->filesdata as $key => $file)
		{
			// max to determine most recently modified
			$this->cache_lastmodified = max($this->cache_lastmodified, $file['lastmodified'] );
			
			// prepend for combined cache name
			$this->cache_filename .= $file['name'];
		}

		$this->cache_lastmodified = ($this->cache_lastmodified == 0) ? '0000000000' : $this->cache_lastmodified;
		$this->cache_filename = $this->_create_cache_name($this->cache_filename);


		// check for cache file
		if (file_exists(Minimee_helper::remove_double_slashes($this->config->cache_path . '/' . $this->cache_filename)))
		{

			$lastmodified = filemtime(Minimee_helper::remove_double_slashes($this->config->cache_path . '/' . $this->cache_filename));

			// Is cache old?
			if ($lastmodified < $this->cache_lastmodified)
			{
				Minimee_helper::log('Cache file found but it is too old: ' . Minimee_helper::remove_double_slashes($this->config->cache_path . '/' . $this->cache_filename), 3);
				$out = FALSE;
			}
			
			// the cache is valid!
			else
			{
				Minimee_helper::log('Cache file found: ' . Minimee_helper::remove_double_slashes($this->config->cache_path . '/' . $this->cache_filename), 3);
				$out = $this->_cache_tag();
			}
		}
		
		// No cache file found
		else
		{
			Minimee_helper::log('Cache file not found.', 3);
			$out = FALSE;
		}

		// if we've made it this far...		
		return $out;
	}
	// ------------------------------------------------------


	/** 
	 * Internal function for (maybe) minifying assets
	 * 
	 * @param	Contents to be minified
	 * @param	mixed A relative path to use, if provided
	 * @return	String (maybe) minified contents of file
	 */
	protected function _minify($contents, $filename, $rel = FALSE)
	{
		switch ($this->type) :
			
			case 'js':
			
				if ($this->EE->extensions->active_hook('minimee_pre_minify_js'))
				{
					Minimee_helper::log('Hook `minimee_pre_minify_js` has been activated.', 3);
		
					// pass contents to be minified, and instance of self
					$contents = $this->EE->extensions->call('minimee_pre_minify_js', $contents, $filename, $this);
					
					if ($this->EE->extensions->end_script === TRUE)
					{
						return $contents;
					}
				}

				// be sure we want to minify
				if ($this->config->is_yes('minify') && $this->config->is_yes('minify_js'))
				{
					Minimee_helper::library('js');
					$contents = JSMin::minify($contents);
				}

			break;
			
			case 'css':
				
				if ($this->EE->extensions->active_hook('minimee_pre_minify_css'))
				{
					Minimee_helper::log('Hook `minimee_pre_minify_css` has been activated.', 3);
		
					// pass contents to be minified, relative path, and instance of self
					$contents = $this->EE->extensions->call('minimee_pre_minify_css', $contents, $filename, $rel, $this);
					
					if ($this->EE->extensions->end_script === TRUE)
					{
						return $contents;
					}
				}

				// set a relative path if exists
				$relativePath = ($rel !== FALSE && $this->config->is_yes('css_prepend_mode')) ? $rel . '/' : NULL;

				// options for CSS Minify				
				$options = array('prependRelativePath' => $relativePath);

				// be sure we want to minify
				if ($this->config->is_yes('minify') && $this->config->is_yes('minify_css'))
				{
					Minimee_helper::library('css');

					$contents = Minify_CSS::minify($contents, $options);
				}

				// un-minified, but (maybe) uri-rewritten contents
				else
				{
					if ($relativePath !== NULL)
					{
						Minimee_helper::library('css_urirewriter');
	
						$contents = Minify_CSS_UriRewriter::prepend($contents, $options['prependRelativePath']);
					}
				}

			break;

		endswitch;
		
		// return our (maybe) minified contents
		return $contents . "\n";
	}
	// ------------------------------------------------------
	
	
	/**
	 * Postpone processing our method until template_post_parse hook?
	 * 
	 * @param String	Method name (e.g. display, link or embed)
	 * @return Mixed	TRUE if delay, FALSE if not
	 */
	public function _postpone($method)
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
				$needle = md5($this->EE->TMPL->tagproper);
				
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
	 * Called by Minimee:css and Minimee:js, performs basic run command
	 * 
	 * @return mixed string or empty
	 */
	protected function _run()
	{
		try
		{
			$this->_fetch_params()
				 ->_fetch_files($this->EE->TMPL->tagdata);

			// Are we queueing for later? If so, just save in session
			if ($this->queue)
			{
				return $this->_set_queue();
			}

			return $this->_flightcheck()
						->_check_headers()
						->_process();
			
		}
		catch (Exception $e)
		{
			return $this->_abort($e);
		}
	}
	// ------------------------------------------------------


	/**
	 * Process our filesdata
	 *
	 * The main purpose of this method is to handle combine="no" circumstances.
	 * @return String	Final tag output
	 */
	protected function _process()
	{
		// what to eventually return
		$return ='';

		// combining files?
		if ($this->config->is_yes('combine') && $this->config->is_yes('combine_' . $this->type))
		{
			// first try to fetch from cache
			$return = $this->_get_cache();
			
			if ($return === FALSE)
			{
				// write new cache
				$return = $this->_create_cache();
			}
		}

		// manual work to combine each file in turn
		else
		{
			$filesdata = $this->filesdata;
			$this->filesdata = array();
			$out = '';
			foreach($filesdata as $file)
			{
				$this->filesdata = array($file);

				// first try to fetch from cache
				$out = $this->_get_cache();
				
				if ($out === FALSE)
				{
					// write new cache
					$out = $this->_create_cache();
				}
	
				$return .= $out;
			}
			
			unset($out);
		}
		
		return $return;

	}
	// ------------------------------------------------------


	/**
	 * Set up our Minimee::filesdata arrays to prepare for processing
	 * 
	 * @param array array of files
	 * @return void
	 */
	protected function _set_filesdata($files, $from_queue = FALSE) {
	
		$dups = array();

		foreach ($files as $key => $file)
		{
			// if we are receiving these from the queue, no need to calculate ALL of the below
			if ($from_queue === TRUE)
			{
				$this->filesdata[$key] = $file;
			}
			
			else
			{
				// try to avoid duplicates
				if (in_array($file, $dups)) continue;
			
				$dups[] = $file['name'];
			
				$this->filesdata[$key] = array(
					'name' => $file,
					'type' => NULL,
					'runtime' => $this->config->get_runtime(),
					'lastmodified' => '0000000000',
					'stylesheet' => NULL
				);
	
				if (Minimee_helper::is_url($this->filesdata[$key]['name']))
				{
					$this->filesdata[$key]['type'] = 'remote';
				}
				elseif (strpos($this->filesdata[$key]['name'], 'minimee=') !== FALSE && preg_match("/\[minimee=[\042\047]?(.*?)[\042\047]?\]/", $this->filesdata[$key]['name'], $matches))
				{
				
					$this->filesdata[$key]['type'] = 'stylesheet';
					$this->filesdata[$key]['stylesheet'] = $matches[1];
		
				}
				else
				{
					$this->filesdata[$key]['type'] = 'local';
				}
			}

			// flag to see if we need to run SQL query later
			if ($this->filesdata[$key]['type'] == 'stylesheet')
			{
				$this->stylesheet_query = TRUE;
			}

		}

		// free memory where possible
		unset($dups);
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
		if ( ! array_key_exists($this->queue, $this->cache[$this->type]))
		{
			$this->cache[$this->type][$this->queue] = array(
				'template' => $this->template,
				'tagdata' => '',
				'filesdata' => array()
			);
		}
		
		// be sure we have a priority key in place
		$priority = (int) $this->EE->TMPL->fetch_param('priority', 0);
		if ( ! array_key_exists($priority, $this->cache[$this->type][$this->queue]['filesdata']))
		{
			$this->cache[$this->type][$this->queue]['filesdata'][$priority] = array();
		}
		
		// Append tagdata - used if queueing ever aborts from an error
		$this->cache[$this->type][$this->queue]['tagdata'] .= $this->EE->TMPL->tagdata;

		// Add all files to the queue cache
		foreach($this->filesdata as $filesdata)
		{
			$this->cache[$this->type][$this->queue]['filesdata'][$priority][] = $filesdata;
		}
	}
	// ------------------------------------------------------
	

	/** 
	 * Determine our remote mode for this call
	 * 
	 * @param string either 'js' or 'css'
	 * @return void
	 */
	public function _set_remote_mode()
	{

		// let's only do this once per session
		if ( ! isset($this->cache['remote_mode']))
		{
		
			// empty to start, then attempt to update it
			$this->cache['remote_mode'] = '';		

			// if 'auto', then we try curl first
			if (preg_match('/auto|curl/i', $this->config->remote_mode) && in_array('curl', get_loaded_extensions()))
			{
				Minimee_helper::log('Using CURL for remote files.', 3);
				$this->cache['remote_mode'] = 'curl';
			}
	
			// file_get_contents() is auto mode fallback
			elseif (preg_match('/auto|fgc/i', $this->config->remote_mode) && ini_get('allow_url_fopen'))
			{
				Minimee_helper::log('Using file_get_contents() for remote files.', 3);
	
				if ( ! defined('OPENSSL_VERSION_NUMBER'))
				{
					Minimee_helper::log('Your PHP compile does not appear to support file_get_contents() over SSL.', 2);
				}

				$this->cache['remote_mode'] = 'fgc';
			}
			
			// if we're here, then we cannot fetch remote files
			else
			{
				Minimee_helper::log('Remote files cannot be fetched.', 2);
			}
		}
		
		$this->remote_mode = $this->cache['remote_mode'];
	}
	// ------------------------------------------------------


	/** 
	 * Internal function for writing cache files
	 * [Adapted from CodeIgniter Carabiner library]
	 * 
	 * @param	String of contents of the new file
	 * @return	boolean	Returns true on successful cache, false on failure
	 */
	protected function _write_cache($file_data)
	{
		if ($this->EE->extensions->active_hook('minimee_pre_write_cache'))
		{
			Minimee_helper::log('Hook `minimee_pre_write_cache` has been activated.', 3);

			// pass contents of file, and instance of self
			$file_data = $this->EE->extensions->call('minimee_pre_write_cache', $file_data, $this);
			
			if ($this->EE->extensions->end_script === TRUE)
			{
				return;
			}
		}

		$filepath = Minimee_helper::remove_double_slashes($this->config->cache_path . '/' . $this->cache_filename);
		$success = file_put_contents($filepath, $file_data);
		
		if ($success === FALSE)
		{ 
			throw new Exception('There was an error writing cache file ' . $this->cache_filename . ' to ' . $this->config->cache_path);
		}
		
		if ($success === 0)
		{
			Minimee_helper::log('The new cache file is empty.', 2);
		}

		// borrowed from /system/expressionengine/libraries/Template.php
		// FILE_READ_MODE is set in /system/expressionengine/config/constants.php
		@chmod($filepath, FILE_READ_MODE);

		Minimee_helper::log('Cache file `' . $this->cache_filename . '` was written to ' . $this->config->cache_path, 3);

		// free memory where possible
		unset($filepath, $success);
	}
	// ------------------------------------------------------
}
// END
	
/* End of file pi.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/pi.minimee.php */