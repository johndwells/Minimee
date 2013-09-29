<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'minimee/classes/Minimee_helper.php';

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
 * Minimee Library
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_lib {

	/**
	 * EE, obviously
	 */
	private $EE;


	/**
	 * runtime variables
	 */
	public $cache_lastmodified		= '';		// lastmodified value for cache
	public $cache_filename_hash		= '';		// a hash of settings & filenames
	public $cache_filename			= '';		// eventual filename of cache
	public $filesdata				= array();	// array of assets to process
	public $remote_mode				= '';		// 'fgc' or 'curl'
	public $stylesheet_query		= FALSE;	// Boolean of whether to fetch stylesheets from DB
	public $type					= '';		// 'css' or 'js'


	/**
	 * keep track of how many bytes saved during minification
	 */
	protected $diff_total			= 0;

	/**
	 * Minimee_config
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
	 * @param Mixed 	Instance of Minimee_config, or Array to be passed to Minimee_config
	 * @return void
	 */
	public function __construct($config = array())
	{
		// got EE?
		$this->EE =& get_instance();
		
		// grab reference to our cache
		$this->cache =& Minimee_helper::cache();

		// set instance of our config object
		if($config instanceof Minimee_config)
		{
			$this->config = $config;
		}
		else
		{
			$this->config = new Minimee_config($config);
		}
	}
	// ------------------------------------------------------


	/**
	 * Convenience wrapper for running CSS minification on a batch of files
	 *
	 * @param mixed 	String or array of files to cache
	 * @return mixed 	String or array of cache filename(s)
	 */
	public function css($files)
	{
		return $this->run('css', $files);
	}
	// ------------------------------------------------------


	/**
	 * Convenience wrapper for running JS minification on a batch of files
	 *
	 * @param mixed 	String or array of files to cache
	 * @return mixed 	String or array of cache filename(s)
	 */
	public function js($files)
	{
		return $this->run('js', $files);
	}
	// ------------------------------------------------------


	/**
	 * Get or greate our cache
	 *
	 * Here handles the rare combine="no" circumstances.
	 * @return mixed 	String or array of cache filename(s)
	 */
	public function cache()
	{
		// Be sure we have a valid type
		if ( ! $this->type)
		{
			throw new Exception('Must specify a valid asset type.');
		}

		// combining files?
		if ($this->config->is_yes('combine_' . $this->type))
		{
			// what to eventually return
			$return = '';

			// first try to fetch from cache			
			if ($this->_get_cache() === FALSE)
			{
				// write new cache
				$this->_create_cache();
			}

			$return = $this->cache_filename;
		}

		// manual work to combine each file in turn
		else
		{
			$filesdata = $this->filesdata;
			$this->filesdata = array();
			$out = '';
			$return = array();
			foreach($filesdata as $file)
			{
				$this->filesdata = array($file);

				// first try to fetch from cache
				if ($this->_get_cache() === FALSE)
				{
					// write new cache
					$this->_create_cache();
				}
	
				$return[] = $this->cache_filename;
			}

			unset($out);
		}
		
		// return string or array
		return $return;
	}
	// ------------------------------------------------------


	/**
	 * Find out more info about each file
	 * Attempts to get file modification times, determine what files exist, etc
	 * 
	 * @return bool TRUE if all are found; FALSE if at least one is not found
	 */
	public function check_headers()
	{
		// let's be sure we have files
		if ( ! $this->filesdata)
		{
			throw new Exception('Must specify at least one file to minify.');
		}

		// query for any stylesheets	
		$stylesheet_versions = $this->_fetch_stylesheet_versions();
		
		// temporarily store runtime settings
		$runtime = $this->config->get_runtime();

		// now, loop through our filesdata and set all headers	
		foreach ($this->filesdata as $key => $file) :
		
			// file runtime settings can be overridden by tag runtime settings
			$this->config->reset()->extend($runtime)->extend($this->filesdata[$key]['runtime']);

			switch ($this->filesdata[$key]['source']) :
			
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
					$local = Minimee_helper::replace_url_with($this->config->base_url, '', $file['name']);
	
					// the filename needs to be without any cache-busting or otherwise $_GETs
					if ($position = strpos($local, '?'))
					{
						$local = substr($local, 0, $position);
					}
					
					$realpath = realpath(Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $local));
	
					// if the $local file exists, let's alter the file source & name, and calculate lastmodified
					if (file_exists($realpath))
					{
						$this->filesdata[$key]['name'] = $local;
						$this->filesdata[$key]['source'] = 'local';
						
						$this->filesdata[$key]['lastmodified'] = filemtime($realpath);
	
						Minimee_helper::log('Treating `' . $file['name'] . '` as a local file: `' . $this->filesdata[$key]['name'] . '`', 3);
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
	 * Add to total diff; returns new total
	 *
	 * @return Integer Total bytes saved after minification
	 */
	public function diff_total($diff = 0)
	{
		$this->diff_total = $diff + $this->diff_total;

		return $this->diff_total;
	}
	// ------------------------------------------------------


	/**
	 * Flightcheck - make some basic config checks before proceeding
	 *
	 * @return void
	 */
	public function flightcheck()
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

		// If our cache_path doesn't appear to exist, try appending it to our base_url and check again.
		if ( ! file_exists($this->config->cache_path))
		{
			Minimee_helper::log('Cache Path `' . $this->config->cache_path . '` is being appended to Base Path `' . $this->config->base_path . '`.', 3);

			$this->config->cache_path = Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $this->config->cache_path);

			Minimee_helper::log('Cache Path is now `' . $this->config->cache_path . '`.', 3);

			if ( ! file_exists($this->config->cache_path))
			{
				throw new Exception('Not configured correctly: your cache folder `' . $this->config->cache_path . '` does not exist.');
			}
		}

		// Be sure our cache path is also writable
		if ( ! is_really_writable($this->config->cache_path))
		{
			throw new Exception('Not configured correctly: your cache folder `' . $this->config->cache_path . '` is not writable.');
		}

		// If our cache_url doesn't appear a valid url, append it to our base_url
		if ( ! Minimee_helper::is_url($this->config->cache_url))
		{
			Minimee_helper::log('Cache URL `' . $this->config->cache_url . '` is being appended to Base URL `' . $this->config->base_url . '`.', 3);

			$this->config->cache_url = Minimee_helper::remove_double_slashes($this->config->base_url . '/' . $this->config->cache_url, TRUE);

			Minimee_helper::log('Cache URL is now `' . $this->config->cache_url . '`.', 3);
		}

		// Determine our runtime remote_mode setting
		$this->_set_remote_mode();

		// Passed flightcheck!
		Minimee_helper::log('Passed flightcheck.', 3);


		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Set up our Minimee_lib::filesdata arrays to prepare for processing
	 * 
	 * @param array array of files
	 * @return void
	 */
	public function set_files($files)
	{
		$dups = array();

		// cast to array to be safe
		if( ! is_array($files))
		{
			$files = array($files);
		}

		foreach ($files as $key => $file)
		{
			// try to avoid duplicates and emptyness
			if (in_array($file, $dups) || ! $file) continue;
		
			$dups[] = $file;

			$this->filesdata[$key] = array(
				'name' => $file,
				'source' => NULL,
				'runtime' => $this->config->get_runtime(),
				'lastmodified' => '0000000000',
				'stylesheet' => NULL
			);

			if (Minimee_helper::is_url($this->filesdata[$key]['name']))
			{
				$this->filesdata[$key]['source'] = 'remote';
			}
			elseif (preg_match("/".LD."\s*stylesheet=[\042\047]?(.*?)[\042\047]?".RD."/", $this->filesdata[$key]['name'], $matches))
			{
				$this->filesdata[$key]['source'] = 'stylesheet';
				$this->filesdata[$key]['stylesheet'] = $matches[1];
			}
			else
			{
				$this->filesdata[$key]['source'] = 'local';
			}

			// flag to see if we need to run SQL query later
			if ($this->filesdata[$key]['source'] == 'stylesheet')
			{
				$this->stylesheet_query = TRUE;
			}
		}

		// free memory where possible
		unset($dups);

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Set up our Minimee_lib::type flag
	 * 
	 * @param String 		css or js
	 * @return void
	 */
	public function set_type($type)
	{

		if (preg_match('/css|js/i', $type))
		{
			$this->type = strtolower($type);
		}
		else
		{
			throw new Exception('`' . $type . '` is not a valid type of asset.');
		}

		// chaining
		return $this;

	}
	// ------------------------------------------------------


	/**
	 * Reset all internal props
	 *
	 * @return object 	Self
	 */
	public function reset()
	{
		$this->cache_lastmodified		= '';
		$this->cache_filename_hash		= '';
		$this->cache_filename			= '';
		$this->filesdata				= array();
		$this->remote_mode				= '';
		$this->stylesheet_query		= FALSE;
		$this->type					= '';

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Our basic run
	 *
	 * @param String 	Type of cache (css or js)
	 * @param mixed 	String or array of files to cache
	 * @return mixed 	String or array of cache filename(s)
	 */
	public function run($type, $files)
	{
		return $this->reset()
					->set_type($type)
					->set_files($files)
					->flightcheck()
					->check_headers()
					->cache();
	}
	// ------------------------------------------------------


	/**
	 * Performs heavy lifting of creating our cache
	 * 
	 * @return string The final tag to be returned to template
	 */	
	protected function _create_cache()
	{
		// zero our diff total
		$this->diff_total = 0;

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
			$this->config->reset()->extend($runtime)->extend($file['runtime']);

			// determine our initial prepend url
			$css_prepend_url = ($this->config->css_prepend_url) ? $this->config->css_prepend_url : $this->config->base_url;
		
			switch ($file['source']) :
	
				case ('remote') :

					// overwrite the prepend url based off the location of remote asset
					$css_prepend_url = $file['name'];

					// get directory level URL of the asset
					$css_prepend_url = dirname($css_prepend_url);

				// notice we are NOT breaking, because we also want to do everything in stylesheet...

				case ('stylesheet');
					
					// fgc & curl both need http(s): on front
					// so if ommitted, prepend it manually, based on requesting protocol
					if (strpos($file['name'], '//') === 0)
					{
						$prefix = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https:' : 'http:';
						Minimee_helper::log('Manually prepending protocol `' . $prefix . '` to front of file `' . $file['name'] . '`', 3);
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
					$contents = file_get_contents(realpath(Minimee_helper::remove_double_slashes($this->config->base_path . '/' . $file['name'])));
					
					// base the prepend url off the location of asset
					$css_prepend_url = Minimee_helper::remove_double_slashes($css_prepend_url . '/' . $file['name'], TRUE);

					// get directory level URL of the asset
					$css_prepend_url = dirname($css_prepend_url);
				break;
	
			endswitch;

			// Let's log a warning message if the contents of file are empty
			if ( ! $contents)
			{
				Minimee_helper::log('The contents from `' . $file['name'] . '` were empty.', 2);
			}
			
			// log & minify contents
			else
			{
				Minimee_helper::log('Fetched contents of `' . $file['name'] . '`.', 3);
	
				// minify contents
				$minified = $this->_minify($this->type, $contents, $file['name'], $css_prepend_url);

				// tack on a semicolon at end of JS?
				if($this->type == 'js' && substr($minified, -1) != ';')
				{
					$minified .= ';';
				}
				
				//  and append to $cache
				$cache .= $minified . "\n";
			}

		endforeach;

		// return our settings to our runtime
		$this->config->reset()->extend($runtime);

		// Log total bytes saved, if we saved any, and if there was more than one file to minify (otherwise we're reporting something we've already mentioned in a previous log)
		if($this->diff_total > 0 && count($this->filesdata) > 1)
		{
			$diff_formatted = ($this->diff_total < 100) ? $this->diff_total . 'b' : round($this->diff_total / 1000, 2) . 'kb';
			Minimee_helper::log('Total savings: ' . $diff_formatted . ' across ' . count($this->filesdata) . ' files.', 3);
		}

		// write our cache file
		$this->_write_cache($cache);

		// free memory where possible
		unset($cache, $contents, $css_prepend_url, $runtime);

		// return true
		return TRUE;
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
		$name = Minimee_helper::replace_url_with($this->config->base_url, '', $name);

		Minimee_helper::log('Creating cache name from `' . $name . '`.', 3);

		// create our cache filename by selected hash
		switch ($this->config->hash_method) :

			case 'sanitise' :
			case 'sanitize' :

				// pattern to match any stylesheet= queries
				$s_key = ($this->EE->config->item('index_page')) ? '/' . $this->EE->config->item('index_page') . '\?css=/' : '/\?css=/';

				// what to find & replace
				$find_replace = array(
					// stylesheet= $_GET query
					$s_key => '',
					// type extension
					'/\.' . $this->type . '/i' => '',
					// leading slashes
					'/^\/+/'	=> '',
					// other slashes
					'/\//'	=> '.'
				);
				
				// first, remove leading slashes and replace the rest with periods
				$name = preg_replace(array_keys($find_replace), array_values($find_replace), $name);

				// now sanitise
				$this->cache_filename_hash = $this->EE->security->sanitize_filename($name);

				// reduce length to be safe?
				if(strlen($this->cache_filename_hash) > 200)
				{
					$this->cache_filename_hash = substr($this->cache_filename_hash, 0, 200);
				}

			break;

			case 'md5' :
				$this->cache_filename_hash = md5($name);
			break;

			default :
			case 'sha1' :
				$this->cache_filename_hash = sha1($name);
			break;

		endswitch;

		// include cachebust if provided
		$cachebust = ($this->config->cachebust) ? '.' . $this->EE->security->sanitize_filename($this->config->cachebust) : '';

		// put it all together
		return $this->cache_filename_hash . '.' . $this->cache_lastmodified . $cachebust . '.' . $this->type;
	}
	// ------------------------------------------------------
	
	/**
	 * Query DB for any stylesheets
	 * Borrowed from $EE->TMPL->parse_globals(): ./system/expressionengine/libraries/Template.php
	 *
	 * @return mixed array or FALSE
	 */
	protected function _fetch_stylesheet_versions() {
	
		// nothing to do if Minimee_lib::stylesheet_query is FALSE
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
	 * Internal function to look for cache file(s)
	 * 
	 * @return mixed String of final tag output or FALSE if cache needs to be refreshed
	 */
	protected function _get_cache()
	{
		// (re)set our usage vars
		$this->cache_filename = '';
		$this->cache_filename_hash = '';
		$this->cache_lastmodified = '';

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
			Minimee_helper::log('Cache file found: `' . $this->cache_filename . '`', 3);
			return TRUE;
		}
		
		// No cache file found
		else
		{
			Minimee_helper::log('Cache file not found: `' . $this->cache_filename . '`', 3);
			return FALSE;
		}
	}
	// ------------------------------------------------------


	/** 
	 * Internal function for (maybe) minifying assets
	 * 
	 * @param	Type of asset to minify (css/js)
	 * @param	Contents to be minified
	 * @param	The name of the file being minified (used for logging)
	 * @param	mixed A relative path to use, if provided (for css minification)
	 * @return	String (maybe) minified contents of file
	 */
	protected function _minify($type, $contents, $filename, $rel = FALSE)
	{
		// used in case we need to return orig
		$contents_orig = $contents;
	
		switch ($type) :
			
			case 'js':
			
				/**
				 * JS pre-minify hook
				 */
				if ($this->EE->extensions->active_hook('minimee_pre_minify_js'))
				{
					Minimee_helper::log('Hook `minimee_pre_minify_js` has been activated.', 3);
		
					// pass contents to be minified, and instance of self
					$contents = $this->EE->extensions->call('minimee_pre_minify_js', $contents, $filename, $this);
					
					if ($this->EE->extensions->end_script === TRUE)
					{
						return $contents;
					}

					// re-set $contents_orig in case we need to return
					$contents_orig = $contents;
				}
				// HOOK END


				// be sure we want to minify
				if ($this->config->is_yes('minify_js'))
				{

					// See if JSMinPlus was explicitly requested
					if ($this->config->js_library == 'jsminplus')
					{
						Minimee_helper::log('Running minification with JSMinPlus.', 3);

						Minimee_helper::library('jsminplus');
	
						$contents = JSMinPlus::minify($contents);
					}

					// Running JSMin is default
					else if ($this->config->js_library == 'jsmin')
					{
						Minimee_helper::log('Running minification with JSMin.', 3);

						Minimee_helper::library('jsmin');
	
						$contents = JSMin::minify($contents);
					}
				}

			break;
			
			case 'css':
				
				/**
				 * CSS pre-minify hook
				 */
				if ($this->EE->extensions->active_hook('minimee_pre_minify_css'))
				{
					Minimee_helper::log('Hook `minimee_pre_minify_css` has been activated.', 3);
		
					// pass contents to be minified, relative path, and instance of self
					$contents = $this->EE->extensions->call('minimee_pre_minify_css', $contents, $filename, $rel, $this);
					
					if ($this->EE->extensions->end_script === TRUE)
					{
						return $contents;
					}

					// copy to $contents_orig in case we need to return
					$contents_orig = $contents;
				}
				// HOOK END

				// prepend URL if relative path exists & configured to do so
				if($rel !== FALSE && $this->config->is_yes('css_prepend_mode'))
				{
					Minimee_helper::library('css_urirewriter');
					$contents = Minify_CSS_UriRewriter::prepend($contents, $rel . '/');

					// copy to $contents_orig in case we need to return
					$contents_orig = $contents;
				}

				// minify if configured to do so
				if ($this->config->is_yes('minify_css'))
				{
					// See if CSSMin was explicitly requested
					if ($this->config->css_library == 'cssmin')
					{
						Minimee_helper::log('Running minification with CSSMin.', 3);

						Minimee_helper::library('cssmin');

						$cssmin = new CSSmin(FALSE);
						
						$contents = $cssmin->run($contents);
						
						unset($cssmin);

					}

					// the default is to run Minify_CSS
					else if ($this->config->css_library == 'minify')
					{
						Minimee_helper::log('Running minification with Minify_CSS.', 3);
					
						Minimee_helper::library('minify');
	
						$contents = Minify_CSS::minify($contents);
					}
				}

			break;

		endswitch;

		// calculate weight loss
		$before = strlen($contents_orig);
		$after = strlen($contents);
		$diff = $before - $after;

		// quick check that contents are not empty
		if($after == 0)
		{
			Minimee_helper::log('Minification has returned an empty string for `' . $filename . '`.', 2);
		}

		// did we actually reduce our file size? It's possible an already minified asset
		// uses a more aggressive algorithm than Minify; in that case, keep original contents
		if($diff > 0)
		{
			$diff_formatted = ($diff < 100) ? $diff . 'b' : round($diff / 1000, 2) . 'kb';
			$change = round(($diff / $before) * 100, 2);

			Minimee_helper::log('Minification has reduced ' . $filename . ' by ' . $diff_formatted . ' (' . $change . '%).', 3);

			// add to our running total
			$this->diff_total($diff);
		}
		else
		{
			Minimee_helper::log('Minification unable to reduce ' . $filename . ', so using original content.', 3);
			$contents = $contents_orig;
		}

		// cleanup (leave some smaller variables because they may or may not have ever been set)
		unset($contents_orig);
		
		// return our (maybe) minified contents
		return $contents;
	}
	// ------------------------------------------------------
	

	/** 
	 * Determine our remote mode for this call
	 * 
	 * @param string either 'js' or 'css'
	 * @return void
	 */
	protected function _set_remote_mode()
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

		// creating the compressed file
		if($this->config->is_yes('save_gz'))
		{
			$z_file = gzopen ($filepath.'.gz', 'w9');
			gzwrite ($z_file, $file_data);
			gzclose($z_file);
			@chmod($filepath.'.gz', FILE_READ_MODE);
			Minimee_helper::log('Gzipped file `' . $this->cache_filename . '.gz` was written to ' . $this->config->cache_path, 3);
		}
		
		// Do we need to clean up expired caches?
		if ($this->config->is_yes('cleanup'))
		{
			if ($handle = opendir($this->config->cache_path))
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file == '.' || $file == '..' || $file === $this->cache_filename) continue;

					// matches should be deleted
					if (strpos($file, $this->cache_filename_hash) === 0)
					{
						@unlink($this->config->cache_path . '/' . $file);
						Minimee_helper::log('Cache file `' . $this->cache_filename . '` has expired. File deleted.', 3);
					}
				}
				closedir($handle);
			}
		}

		// free memory where possible
		unset($filepath, $z_file, $success);
	}
	// ------------------------------------------------------
}
// END
	
/* End of file Minimee_lib.php */ 
/* Location: ./system/expressionengine/third_party/minimee/classes/Minimee_lib.php */