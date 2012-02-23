<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Minimee Config settings
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_config
{

	/**
	 * Allowed settings - the master list
	 * 
	 */
	public $allowed = array(
		'base_path'				=> '',
		'base_url'				=> '',
		'cache_path'			=> '',
		'cache_url'				=> '',
		'css_relative_path'		=> '',
		'combine'				=> '',
		'disable'				=> '',
		'minify_html'			=> '',
		'refresh_after'			=> '',
		'remote_mode'			=> '',
		'remote_refresh_after'	=> ''
	);
	
	
	/**
	 * Where we find our config ('db', 'config', 'hook' or 'default').
	 * A 3rd party hook may also rename to something else.
	 */
	public $location = FALSE;


	/**
	 * Default settings
	 *
	 * Set once during init and NOT modified thereafter.
	 */
	protected $_default = array();
	
	
	/**
	 * Runtime settings
	 *
	 * Overrides of defaults used at runtime; our only settings modified directly.
	 */
	protected $_runtime	= array();


	// ------------------------------------------------------


	/**
	 * Constructor function
	 *
	 */
	public function __construct($runtime = array())
	{
		// grab our config settings, will become our defaults
		$this->_init();
		
		// Runtime settings? Use magic __set()
		$this->settings = $runtime;
	}
	// ------------------------------------------------------


	/**
	 * Magic Getter
	 *
	 * First looks for setting in Minimee_config::$_runtime; then Minimee_config::$_default.
	 * If requesting all settings, returns complete array
	 *
	 * @param 	string	Name of setting to retrieve
	 * @return 	mixed	Array of all settings, value of individual setting, or NULL if not valid
	 */
	public function __get($prop)
	{
		// Find & retrieve the runtime setting
		if(array_key_exists($prop, $this->_runtime))
		{
			return $this->_runtime[$prop];
		}
		
		// Find & retrieve the default setting
		if(array_key_exists($prop, $this->_default))
		{
			return $this->_default[$prop];
		}
		
		// I guess it's OK to ask for a raw copy of our settings
		if($prop == 'settings')
		{
			// merge with defaults first
			return array_merge($this->_default, $this->_runtime);
		}

		// Nothing found. Something might be wrong so log a debug message
		Minimee_logger::log('`' . $prop . '` is not a valid setting.', 2);

		return NULL;
	}
	// ------------------------------------------------------


	/**
	 * Magic Setter
	 *
	 * @param 	string	Name of setting to set
	 * @return 	mixed	Value of setting or NULL if not valid
	 */
	public function __set($prop, $value)
	{
		// are we setting the entire Minimee_helper::settings array?
		if($prop == 'settings' && is_array($value))
		{
			// is our array empty? if so, consider it "reset"
			if(count($value) === 0)
			{
				$this->_runtime = array();
			}
			else
			{
				$this->_runtime = $this->sanitise_settings($value);
			}
		}
		// just set an individual setting
		elseif(array_key_exists($prop, $this->allowed))
		{
			$this->_runtime[$prop] = $this->sanitise_setting($prop, $value);
		}
	}
	// ------------------------------------------------------


	/**
	 * Utility method
	 *
	 * Usage: if($Minimee_config->no('disable')) {...}
	 */
	public function no($setting)
	{
		return ($this->$setting == 'no') ? TRUE : FALSE;
	}
	// ------------------------------------------------------


	/**
	 * Reset runtime settings to empty array
	 * Same as doing $Minimee_config->settings = array();
	 *
	 * @return 	void
	 */
	public function reset()
	{
		$this->_runtime = array();

		// chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Return copy of runtime settings
	 *
	 * @return 	array
	 */
	public function runtime()
	{
		return $this->_runtime;
	}
	// ------------------------------------------------------


	/**
	 * Sanitise an array of settings
	 *
	 * @param 	array	Array of possible settings
	 * @return 	array	Sanitised array
	 */
	public function sanitise_settings($settings)
	{
		if( ! is_array($settings)) {
			Minimee_logger::log('Trying to sanitise a non-array of settings.', 2);
			return array();
		}

		// reduce our $settings array to only valid keys
        $valid = array_flip(array_intersect(array_keys($this->allowed), array_keys($settings)));
        
		foreach($valid as $setting => $value)
		{
			$valid[$setting] = $this->sanitise_setting($setting, $settings[$setting]);
		}
		
		return $valid;
	}
	// ------------------------------------------------------


	/**
	 * Sanitise an individual setting
	 *
	 * @param 	string	Name of setting
	 * @param 	string	potential value of setting
	 * @return 	string	Sanitised setting
	 */
	public function sanitise_setting($setting, $value)
	{
		switch($setting) :

			/* Booleans default NO */
			case('disable') :
			case('minify_html') :
				return ($value === TRUE OR preg_match('/1|true|on|yes|y/i', $value)) ? 'yes' : 'no';
			break;
		
			/* Booleans default YES */
			case('combine') :
			case('css_relative_path') :
				return ($value === FALSE OR preg_match('/0|false|off|no|n/i', $value)) ? 'no' : 'yes';
			break;
		
			/* Integer */
			case('refresh_after') :
				return (int) $value;
			break;
			
			/* ENUM */
			case('remote_mode') :
				return preg_match('/auto|curl|fgc/i', $value) ? $value : 'auto';
			break;
			
			/* String - Paths */
			case('cache_path') :
			case('base_path') :
				// regex pattern removes all double slashes
				return rtrim(preg_replace("#(^|[^:])//+#", "\\1/", $value));
			break;

			/* String - URLs */
			case('cache_url') :
			case('base_url') :
				// regex pattern removes all double slashes, preserving http:// and '//' at start
				return rtrim(preg_replace("#([^:])//+#", "\\1/", $value));
			break;

			/* Default */			
			default :
				return $value;
			break;
		
		endswitch;
	}
	// ------------------------------------------------------
	
	
	/**
	 * Return array of all settings at runtime
	 */
	public function to_array()
	{
		// merge with defaults first
		return array_merge($this->_default, $this->_runtime);
	}
	// ------------------------------------------------------


	/**
	 * Utility method
	 *
	 * Usage: if($Minimee_config->yes('disable')) {...}
	 */
	public function yes($setting)
	{
		return ($this->$setting == 'yes') ? TRUE : FALSE;
	}
	// ------------------------------------------------------


	/**
	 * Look for settings in EE's config object
	 */
	protected function _from_config()
	{
		$ee =& get_instance();
		
		$settings = FALSE;

		// check if Minimee is being set via config
		if ($ee->config->item('minimee'))
		{
	        $settings = $ee->config->item('minimee');
	        
	        // better be an array!
	        if(is_array($settings))
	        {
				$this->location = 'config';

				Minimee_logger::log('Settings taken from EE config.', 3);
	        }
	        else
	        {
	        	$settings = FALSE;

				Minimee_logger::log('Settings taken from EE config must be an array.', 1);
	        }
		}
		else
		{
			Minimee_logger::log('No settings found in EE config.', 2);
		}
		
		return $settings;
	}
	// ------------------------------------------------------
	
	
	/**
	 * Look for settings in database (set by our extension)
	 */
	protected function _from_db()
	{
		$ee =& get_instance();
		
		$settings = FALSE;

		if ($ee->config->item('allow_extensions') == 'y')
		{
			$query = $ee->db
						->select('settings')
						->from('extensions')
						->where(array( 'enabled' => 'y', 'class' => 'Minimee_ext' ))
						->limit(1)
						->get();
			
			if ($query->num_rows() > 0)
			{
				$settings = unserialize($query->row()->settings);

				$this->location = 'db';

				Minimee_logger::log('Settings retrieved from database.', 3);
			}
			else
			{
				Minimee_logger::log('No settings found in database.', 2);
			}
			
			$query->free_result();

		}
		
		return $settings;
	}
	// ------------------------------------------------------


	/**
	 * Allow 3rd parties to provide own configuration settings
	 */
	protected function _from_hook()
	{
		$ee =& get_instance();

		$settings = FALSE;
		
		if ($ee->extensions->active_hook('minimee_get_settings'))
		{
			// Must return FALSE or array()
			$settings = $ee->extensions->call('minimee_get_settings', $this);

			// Technically the hook has an opportunity to set location to whatever it wishes;
			// so only set to 'hook' if still false
			if(is_array($settings) && $this->location === FALSE)
			{
				$this->location = 'hook';
			}
		}
		
		return $settings;
	}
	// ------------------------------------------------------


	/**
	 * Retrieves settings from session, minimee_get_settings hook, config OR database (and in that order).
	 *
	 * @return void
	 */
	protected function _init()
	{
		$ee =& get_instance();
	
		// see if we have already configured our defaults
		if(isset($ee->session->cache['minimee']['config']))
		{
			$this->_default = $ee->session->cache['minimee']['config'];

			Minimee_logger::log('Settings have been retrieved from session.', 3);
		}
		else
		{
			// we are trying to turn this into an array full of goodness.
			$settings = FALSE;
	
			/*
			 * Test 1: See if anyone is hooking in
			 */
			$settings = $this->_from_hook();
			
			/*
			 * Test 2: Look in config
			 */
			if($settings === FALSE)
			{
				$settings = $this->_from_config();
			}
			
			/*
			 * Test 3: Look in db
			 */
			if($settings === FALSE)
			{
				$settings = $this->_from_db();
			}
			
			/*
			 * Set some defaults
			 */
			if( $settings === FALSE)
			{
				Minimee_logger::log('Could not find any settings to use. Using defaults.', 2);
				
				$this->location = 'default';
				
				// start with an empty array
				$settings = array();
			}

			/*
			 * Set some defaults
			 */
			if( ! array_key_exists('cache_path', $settings) || $settings['cache_path'] == '')
			{
				// use global FCPATH if nothing set
				$settings['cache_path'] = FCPATH . '/cache';
			}

			if( ! array_key_exists('cache_url', $settings) || $settings['cache_url'] == '')
			{
				// use config base_url if nothing set
				$settings['cache_url'] = $ee->config->item('base_url') . '/cache';
			}
			
			if( ! array_key_exists('base_path', $settings) || $settings['base_path'] == '')
			{
				// use global FCPATH if nothing set
				$settings['base_path'] = FCPATH;
			}
			
			if( ! array_key_exists('base_url', $settings) || $settings['base_url'] == '')
			{
				// use config base_url if nothing set
				$settings['base_url'] = $ee->config->item('base_url');
			}
	
			/*
			 * Now make a complete & sanitised settings array, and set as our default
			 */
			$this->_default = $this->sanitise_settings(array_merge($this->allowed, $settings));
	
			// cleanup
			unset($settings);
	
			/*
			 * See if we need to inject ourselves into extensions hook.
			 * This allows us to bind to the template_post_parse hook without installing our extension
			 */
			if($this->minify_html == 'yes' && $ee->config->item('allow_extensions') == 'y' &&  ! isset($ee->extensions->extensions['template_post_parse'][10]['Minimee_ext']))
			{
				// Taken from EE_Extensions::__construct(), around line 70 in system/expressionengine/libraries/Extensions.php
				$ee->extensions->extensions['template_post_parse'][10]['Minimee_ext'] = array('minify_html', '', MINIMEE_VER);
		  		$ee->extensions->version_numbers['Minimee_ext'] = MINIMEE_VER;

				Minimee_logger::log('Manually injected into template_post_parse extension hook.', 3);
			}

			/*
			 * Store this in session for subsequent runs
			 */
			if ( ! isset($ee->session->cache['minimee']))
			{
				$ee->session->cache['minimee'] = array();
			}

			$ee->session->cache['minimee']['config'] = $this->_default;

			Minimee_logger::log('Settings have been saved in session.', 3);
		}

	}
	// ------------------------------------------------------
	
}
// END CLASS

/* End of file Minimee_config.php */
/* Location: ./system/expressionengine/third_party/minimee/models/Minimee_config.php */