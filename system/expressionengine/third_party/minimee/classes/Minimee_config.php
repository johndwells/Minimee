<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// just in case
require_once PATH_THIRD . 'minimee/classes/Minimee_helper.php';

/**
 * Minimee Config settings
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_config {

	/**
	 * EE, obviously
	 */
	private $EE;
	

	/**
	 * Alias of our EE session cache
	 */
	public $cache = FALSE;


	/**
	 * Where we find our config - 'db', 'config', 'hook' or 'default'.
	 * A 3rd party hook may also rename to something else.
	 */
	public $location = FALSE;


	/**
	 * Allowed settings - the master list
	 * If it isn't listed here, it won't exist during runtime
	 */
	protected $_allowed = array(
		'base_path'				=> '',
		'base_url'				=> '',
		'cachebust'				=> '',
		'cache_path'			=> '',
		'cache_url'				=> '',
		'combine'				=> '',
		'combine_css'			=> '',
		'combine_js'			=> '',
		'css_prepend_mode'		=> '',
		'css_prepend_url'		=> '',
		'disable'				=> '',
		'minify'				=> '',
		'minify_css'			=> '',
		'minify_html'			=> '',
		'minify_js'				=> '',
		'remote_mode'			=> ''
	);
	
	
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
	 * If an array is passed, then we clearly expect to init itself
	 * 
	 * @param Array	An array of settings to extend runtime
	 */
	public function __construct($extend = array())
	{
		$this->EE =& get_instance();
		
		// by 'extend' we mean merge runtime with defaults
		if ($extend)
		{
			// grab our config settings, will become our defaults
			$this->init()->extend($extend);
		}
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
		if (array_key_exists($prop, $this->_runtime))
		{
			return $this->_runtime[$prop];
		}
		
		// Find & retrieve the default setting
		if (array_key_exists($prop, $this->_default))
		{
			return $this->_default[$prop];
		}
		
		// I guess it's OK to ask for a raw copy of our settings
		if ($prop == 'settings')
		{
			// merge with defaults first
			return array_merge($this->_default, $this->_runtime);
		}

		// Nothing found. Something might be wrong so log a debug message
		Minimee_helper::log('`' . $prop . '` is not a valid setting.', 2);

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
		// are we setting the entire Minimee_config::settings array?
		if ($prop == 'settings' && is_array($value))
		{
			// is our array empty? if so, consider it "reset"
			if (count($value) === 0)
			{
				$this->_runtime = array();
			}
			else
			{
				$this->_runtime = $this->sanitise_settings($value);
			}
		}
		// just set an individual setting
		elseif (array_key_exists($prop, $this->_allowed))
		{
			$this->_runtime[$prop] = $this->sanitise_setting($prop, $value);
		}
	}
	// ------------------------------------------------------


	/**
	 * Explicit method for setting/modifying runtime settings
	 * __set() still does heavy lifting
	 *
	 * @param 	array
	 * @return 	Object	$this
	 */
	public function extend($extend = array())
	{
		// must be an array
		if (is_array($extend))
		{
			$this->settings = $extend;
		}
		
		//chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Reset our runtime to 'factory' defaults
	 *
	 * @return 	Object	$this
	 */
	public function factory()
	{
		// reset & extend to our empty allowed
		$this->reset()->extend($this->get_allowed());
		
		//chaining
		return $this;
	}
	// ------------------------------------------------------


	/**
	 * Return copy of allowed settings
	 *
	 * @return 	array
	 */
	public function get_allowed()
	{
		return $this->_allowed;
	}
	// ------------------------------------------------------


	/**
	 * Return copy of default settings
	 *
	 * @return 	array
	 */
	public function get_default()
	{
		return $this->_default;
	}
	// ------------------------------------------------------


	/**
	 * Return copy of runtime settings
	 *
	 * @return 	array
	 */
	public function get_runtime()
	{
		return $this->_runtime;
	}
	// ------------------------------------------------------


	/**
	 * Initialise / Initialize.
	 *
	 * Retrieves settings from: session, minimee_get_settings hook, config OR database (and in that order).
	 *
	 * @return void
	 */
	public function init()
	{
		// grab alias of our cache
		$this->cache =& Minimee_helper::cache();

		// see if we have already configured our defaults
		if (isset($this->cache['config']))
		{
			$this->_default = $this->cache['config'];

			Minimee_helper::log('Settings have been retrieved from session.', 3);
		}
		else
		{
			// we are trying to turn this into an array full of goodness.
			$settings = FALSE;

			/*
			 * Test 1: See if anyone is hooking in
			 * Skip this if we're doing anything with our own extension settings
			 */
			if ( ! (isset($_GET['M']) && $_GET['M'] == 'extension_settings' && $_GET['file'] == 'minimee'))
			{
				$settings = $this->_from_hook();
			}
			
			/*
			 * Test 2: Look in config
			 */
			if ($settings === FALSE)
			{
				$settings = $this->_from_config();
			}
			
			/*
			 * Test 3: Look in db
			 */
			if ($settings === FALSE)
			{
				$settings = $this->_from_db();
			}
			
			/*
			 * Set some defaults
			 */
			if ( $settings === FALSE)
			{
				Minimee_helper::log('Could not find any settings to use. Trying defaults.', 3);
				
				$this->location = 'default';
				
				// start with an empty array
				$settings = array();
			}

			/*
			 * Set some defaults
			 */
			if ( ! array_key_exists('cache_path', $settings) || $settings['cache_path'] == '')
			{
				// use global FCPATH if nothing set
				$settings['cache_path'] = FCPATH . '/cache';
			}

			if ( ! array_key_exists('cache_url', $settings) || $settings['cache_url'] == '')
			{
				// use config base_url if nothing set
				$settings['cache_url'] = $this->EE->config->item('base_url') . '/cache';
			}
			
			if ( ! array_key_exists('base_path', $settings) || $settings['base_path'] == '')
			{
				// use global FCPATH if nothing set
				$settings['base_path'] = FCPATH;
			}
			
			if ( ! array_key_exists('base_url', $settings) || $settings['base_url'] == '')
			{
				// use config base_url if nothing set
				$settings['base_url'] = $this->EE->config->item('base_url');
			}
	
			/*
			 * Now make a complete & sanitised settings array, and set as our default
			 */
			$this->_default = $this->sanitise_settings(array_merge($this->_allowed, $settings));
	
			// cleanup
			unset($settings);
	
			/*
			 * See if we need to inject ourselves into extensions hook.
			 * This allows us to bind to the template_post_parse hook without installing our extension
			 */
			if ($this->EE->config->item('allow_extensions') == 'y' &&  ! isset($this->EE->extensions->extensions['template_post_parse'][10]['Minimee_ext']))
			{
				// Taken from EE_Extensions::__construct(), around line 70 in system/expressionengine/libraries/Extensions.php
				$this->EE->extensions->extensions['template_post_parse'][10]['Minimee_ext'] = array('template_post_parse', '', MINIMEE_VER);
		  		$this->EE->extensions->version_numbers['Minimee_ext'] = MINIMEE_VER;

				Minimee_helper::log('Manually injected into template_post_parse extension hook.', 3);
			}

			// set our settings to cache for retrieval later on
			$this->cache['config'] = $this->_default;
			
			Minimee_helper::log('Settings have been saved in session cache. Settings came from: ' . $this->location, 3);
		}
		
		// chaining
		return $this;

	}
	// ------------------------------------------------------


	/**
	 * Utility method
	 *
	 * Usage: if ($Minimee_config->is_no('disable')) {...}
	 */
	public function is_no($setting)
	{
		return ($this->$setting == 'no') ? TRUE : FALSE;
	}
	// ------------------------------------------------------


	/**
	 * Utility method
	 *
	 * Usage: if ($Minimee_config->is_yes('disable')) {...}
	 */
	public function is_yes($setting)
	{
		return ($this->$setting == 'yes') ? TRUE : FALSE;
	}
	// ------------------------------------------------------


	/**
	 * Reset runtime settings to empty array
	 * Same as doing $Minimee_config->settings = array();
	 *
	 * @return 	Object	$this
	 */
	public function reset()
	{
		$this->_runtime = array();

		// chaining
		return $this;
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
		if ( ! is_array($settings)) {
			Minimee_helper::log('Trying to sanitise a non-array of settings.', 2);
			return array();
		}

		// reduce our $settings array to only valid keys
        $valid = array_flip(array_intersect(array_keys($this->_allowed), array_keys($settings)));
        
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
				return ($value === TRUE OR preg_match('/1|true|on|yes|y/i', $value)) ? 'yes' : 'no';
			break;
		
			/* Booleans default YES */
			case('combine') :
			case('combine_css') :
			case('combine_js') :
			case('css_prepend_mode') :
			case('minify') :
			case('minify_css') :
			case('minify_html') :
			case('minify_js') :
				return ($value === FALSE OR preg_match('/0|false|off|no|n/i', $value)) ? 'no' : 'yes';
			break;

			/* ENUM */
			case('remote_mode') :
				return preg_match('/auto|curl|fgc/i', $value) ? $value : 'auto';
			break;
			
			/* String - Paths */
			case('cache_path') :
			case('base_path') :
				return rtrim(Minimee_helper::remove_double_slashes($value), '/');
			break;

			/* String - URLs */
			case('cache_url') :
			case('base_url') :
			case('css_prepend_url') :
				return rtrim(Minimee_helper::remove_double_slashes($value, TRUE), '/');
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
	 * Look for settings in EE's config object
	 */
	protected function _from_config()
	{
		$settings = FALSE;

		// check if Minimee is being set via config
		if ($this->EE->config->item('minimee'))
		{
	        $settings = $this->EE->config->item('minimee');
	        
	        // better be an array!
	        if (is_array($settings) && count($settings) > 0)
	        {
				$this->location = 'config';

				Minimee_helper::log('Settings taken from EE config.', 3);
	        }
	        else
	        {
	        	$settings = FALSE;

				Minimee_helper::log('Settings taken from EE config must be a non-empty array.', 1);
	        }
		}
		else
		{
			Minimee_helper::log('No settings found in EE config.', 3);
		}
		
		return $settings;
	}
	// ------------------------------------------------------
	
	
	/**
	 * Look for settings in database (set by our extension)
	 * 
	 * @return void
	 */
	protected function _from_db()
	{
		$settings = FALSE;

		if ($this->EE->config->item('allow_extensions') == 'y')
		{
			$query = $this->EE->db
						->select('settings')
						->from('extensions')
						->where(array( 'enabled' => 'y', 'class' => 'Minimee_ext' ))
						->limit(1)
						->get();
			
			if ($query->num_rows() > 0)
			{
				$settings = unserialize($query->row()->settings);

				$this->location = 'db';

				Minimee_helper::log('Settings retrieved from database.', 3);
			}
			else
			{
				Minimee_helper::log('No settings found in database.', 3);
			}
			
			$query->free_result();

		}
		
		return $settings;
	}
	// ------------------------------------------------------


	/**
	 * Allow 3rd parties to provide own configuration settings
	 * 
	 * @return mixed	array of settings of FALSE
	 */
	protected function _from_hook()
	{
		$settings = FALSE;
		
		if ($this->EE->extensions->active_hook('minimee_get_settings'))
		{
			// Must return FALSE or array()
			$settings = $this->EE->extensions->call('minimee_get_settings', $this);

			// Technically the hook has an opportunity to set location to whatever it wishes;
			// so only set to 'hook' if still false
			if (is_array($settings) && $this->location === FALSE)
			{
				$this->location = 'hook';
			}
		}
		
		return $settings;
	}
	// ------------------------------------------------------
}
// END CLASS

/* End of file Minimee_config.php */
/* Location: ./system/expressionengine/third_party/minimee/classes/Minimee_config.php */