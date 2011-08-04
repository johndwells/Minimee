<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Minimee Helper
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_helper
{
	public static $config = FALSE;

	public static function config()
	{
		if (self::$config)
		{
			return self::$config;
		}

        $ee =& get_instance();

		/*
		 * Try to determine where Minimee is being configured.
		 * This check is only reliable on the front end (since global vars are not picked up in admin.)
		 * ================================================ */
		// first check config
		if ($ee->config->item('minimee_cache_path') && $ee->config->item('minimee_cache_url'))
		{
			self::$config = 'config';
		}
		// check in global vars
		elseif (array_key_exists('minimee_cache_path', $ee->config->_global_vars) && array_key_exists('minimee_cache_url', $ee->config->_global_vars))
		{
			self::$config = 'global';
		}
		// assume db (default)
		else
		{
			self::$config = 'db';
		}
		
		return self::$config;
	}


	/**
	 * Returns a default array of settings
	 *
	 * @return array default settings & values
	 */
	public static function default_settings()
	{
		return array(
			'base_path'		=> '',
			'base_url'		=> '',
			'cache_path'	=> '',
			'cache_url'		=> '',
			'debug'			=> 'no',
			'disable'		=> 'no',
			'remote_mode'	=> 'auto'
		);
	}

	

	/**
	 * Used by plugin, retrieves settings from config, global variables OR database (and in that order)
	 *
	 * @return void
	 */
	public static function get_settings()
	{
        $ee =& get_instance();

		// if settings are already in session cache, use those
		if ( ! isset($ee->session->cache['minimee']['settings']))
		{
			$settings = array();
			
			// retrieve config settings (location may vary)
			switch (self::config()) :
	
				case ('config') :
				
					foreach (self::default_settings() as $key => $val)
					{
						$settings[$key] = $ee->config->item('minimee_' . $key);
					}
					
					self::log('Minimee has retrieved settings from config.');
				break;
				
				case ('global') :

					foreach (self::default_settings() as $key => $val)
					{
						if (array_key_exists('minimee_' . $key, $ee->config->_global_vars))
						$settings[$key] = $ee->config->_global_vars['minimee_' . $key];
					}

					self::log('Minimee has retrieved settings from global variables.');
				break;
				
				case ('db') :
				default :
					$ee->db
						->select('settings')
						->from('extensions')
						->where(array('enabled' => 'y', 'class' => 'Minimee_ext' ))
						->limit(1);
					$query = $ee->db->get();
					
					if ($query->num_rows() > 0)
					{
						$settings = unserialize($query->row()->settings);
						self::log('Minimee has retrieved settings from DB.');
					}
					else
					{
						self::log('Minimee has not yet been configured.', 3);
					}
				break;
	
			endswitch;
	
			// normalize settings before adding to session
			self::normalize_settings($settings);
			$ee->session->cache['minimee'] = array(
				'settings' => $settings,
				'js' => array(),
				'css' => array()
			);

			// free memory where possible			
			unset($settings);
		}
		
		// return settings back to plugin
		return $ee->session->cache['minimee']['settings'];
	}
	// END


	/**
	 * Logs a message
	 * First logs message to EE's Template Debugger
	 * Then if available, sends messages to OmniLog
	 *
	 * @access  public
	 * @param   string      $message        The log entry message.
	 * @param   int         $severity       The log entry 'level'.
	 * @return  void
	 */
	public static function log($message, $severity = 1)
	{
        $ee =& get_instance();
    
   		$type = ($severity == 3) ? 'ERROR' : (($severity == 2) ? 'WARNING' : 'NOTICE');
		$ee->TMPL->log_item(MINIMEE_NAME . " [{$type}]: {$message}");
		
		// Should we use OmniLogger?
		if (array_key_exists('Omnilog', $ee->TMPL->module_data)) {
			// Load the OmniLogger class.
			if (is_file(PATH_THIRD .'omnilog/classes/omnilogger' .EXT))
			{
				include_once PATH_THIRD .'omnilog/classes/omnilogger' .EXT;
			}
	
			if (class_exists('Omnilog_entry') && class_exists('Omnilogger'))
			{
				switch ($severity)
				{
					case 3:
						$notify = TRUE;
						$type   = Omnilog_entry::ERROR;
					break;
		
					case 2:
						$notify = FALSE;
						$type   = Omnilog_entry::WARNING;
					break;
		
					case 1:
					default:
						$notify = FALSE;
						$type   = Omnilog_entry::NOTICE;
					break;
				}
		
				$omnilog_entry = new Omnilog_entry(array(
					'addon_name'    => MINIMEE_NAME,
					'date'          => time(),
					'message'       => $message,
					'notify_admin'  => $notify,
					'type'          => $type
				));
		
				Omnilogger::log($omnilog_entry);
				
				// free memory where possible
				unset($notify, $omnilog_entry, $type);
			}
		}
		else
		{
			// free memory where possible
			unset($type);
		}
	}
	// ----------------------------------------------------------------

	/**
	 * Standardise settings just to be safe!
	 *
	 * @param array an array of options to be normalised
	 * @return void
	 */
	public static function normalize_settings(&$settings)
	{
		// this ensures we avoid any PHP errors
		$settings = array_merge(self::default_settings(), $settings);

		// required
		$settings['cache_path'] = rtrim($settings['cache_path'], '/');
		$settings['cache_url'] = rtrim($settings['cache_url'], '/');
		
		// optional
		$settings['base_path'] = rtrim($settings['base_path'], '/');
		$settings['base_url'] = rtrim($settings['base_url'], '/');
		$settings['debug'] = preg_match('/1|true|on|yes|y/i', $settings['debug']) ? 'yes' : 'no';
		$settings['disable'] = ($settings['disable'] === TRUE OR preg_match('/1|true|on|yes|y/i', $settings['disable'])) ? 'yes' : 'no';
		$settings['remote_mode'] = preg_match('/auto|fgc|curl/i', $settings['remote_mode']) ? $settings['remote_mode'] : 'auto';
	}
	// END

}