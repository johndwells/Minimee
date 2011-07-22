<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'minimee/config.php';

/**
 * Minimee: minimize & combine your CSS and JS files. For EE2 only.
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_ext {

	public $name			= MIMIMEE_NAME;
	public $version			= MIMIMEE_VER;
	public $description		= MIMIMEE_DESC;
	public $docs_url		= MIMIMEE_DOCS;
	public $settings_exist	= 'y';

	public $settings		= array();
	public $config_loc		= FALSE;
	
	public $EE;

	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 * @return void
	 */
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();

		// initialise default settings array
		$this->settings = $this->_default_settings();
		
		/*
		 * Try to determine where Minimee is being configured.
		 * This check is only reliable on the front end.
		 * ================================================ */
		// first check config
		if ($this->EE->config->item('minimee_cache_path') && $this->EE->config->item('minimee_cache_url'))
		{
			$this->config_loc = 'config';
		}
		// check in global varas
		elseif (array_key_exists('minimee_cache_path', $this->EE->config->_global_vars) && array_key_exists('minimee_cache_url', $this->EE->config->_global_vars))
		{
			$this->config_loc = 'global';
		}
		// assume db (default)
		else
		{
			$this->config_loc = 'db';
		}
	}
	// END


	/**
	 * Activate Extension
	 * @return void
	 */
	public function activate_extension()
	{
		$data = array(
			'class'		=> __CLASS__,
			'settings'	=> serialize($this->settings),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);
	}
	// END


	/**
	 * Disable Extension
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	// END


	/**
	 * Used by plugin, retrieves settings from config, global variables OR database (and in that order)
	 *
	 * @return void
	 */
	public function get_settings()
	{
		// if settings are already in session cache, use those
		if (isset($this->EE->session->cache['minimee']['settings']))
		{
			$this->settings = $this->EE->session->cache['minimee']['settings'];
			return;
		}
		
		// retrieve config settings (location may vary)
		switch ($this->config_loc) :

			case ('config') :
				$this->settings['cache_path'] = $this->EE->config->item('minimee_cache_path');
				$this->settings['cache_url'] = $this->EE->config->item('minimee_cache_url');
				$this->settings['base_path'] = $this->EE->config->item('minimee_base_path'); // optional
				$this->settings['base_url'] = $this->EE->config->item('minimee_base_url'); // optional
				$this->settings['debug'] = $this->EE->config->item('minimee_debug'); // optional
				$this->settings['disable'] = $this->EE->config->item('minimee_disable'); // optional
				$this->settings['remote_mode'] = $this->EE->config->item('remote_mode'); // optional
				
				$this->EE->TMPL->log_item('Minimee has retrieved settings from config.');
			break;
			
			case ('global') :
				$this->settings['cache_path'] = $this->EE->config->_global_vars['minimee_cache_path'];
				$this->settings['cache_url'] = $this->EE->config->_global_vars['minimee_cache_url'];
	
				// optional
				if (array_key_exists('minimee_base_path', $this->EE->config->_global_vars))
				{
					$this->settings['base_path'] = $this->EE->config->_global_vars['minimee_base_path'];
				}
	
				// optional
				if (array_key_exists('minimee_base_url', $this->EE->config->_global_vars))
				{
					$this->settings['base_url'] = $this->EE->config->_global_vars['minimee_base_url'];
				}
	
				// optional
				if (array_key_exists('minimee_debug', $this->EE->config->_global_vars))
				{
					$this->settings['debug'] = $this->EE->config->_global_vars['minimee_debug'];
				}
	
				// optional
				if (array_key_exists('minimee_disable', $this->EE->config->_global_vars))
				{
					$this->settings['disable'] = $this->EE->config->_global_vars['minimee_disable'];
				}
	
				// optional
				if (array_key_exists('minimee_remote_mode', $this->EE->config->_global_vars))
				{
					$this->settings['remote_mode'] = $this->EE->config->_global_vars['minimee_remote_mode'];
				}
	
				$this->EE->TMPL->log_item('Minimee has retrieved settings from global variables.');
			break;
			
			case ('db') :
			default :
				$this->EE->db
							->select('settings')
							->from('extensions')
							->where(array('enabled' => 'y', 'class' => __CLASS__ ))
							->limit(1);
				$query = $this->EE->db->get();
				
				if ($query->num_rows() > 0)
				{
					$this->settings = unserialize($query->row()->settings);
					$this->EE->TMPL->log_item('Minimee has retrieved settings from DB.');
				}
				else
				{
					$this->EE->TMPL->log_item('Minimee has not yet been configured.');
				}
			break;

		endswitch;

		// normalize settings before adding to session
		$this->settings = $this->_normalize_settings($this->settings);
		
		// now set to session for subsequent calls
		$this->EE->session->cache['minimee'] = array(
			'settings' => array(),
			'js' => array(),
			'css' => array()
		);
		$this->EE->session->cache['minimee']['settings'] = $this->settings;
		
	}
	// END


	/**
	 * Save settings
	 *
	 * @return 	void
	 */
	public function save_settings()
	{
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}

		$this->EE->lang->loadfile('minimee');

		$settings['cache_path'] = $this->EE->input->post('cache_path');
		$settings['cache_url'] = $this->EE->input->post('cache_url');
		$settings['base_path'] = $this->EE->input->post('base_path');
		$settings['base_url'] = $this->EE->input->post('base_url');
		$settings['debug'] = $this->EE->input->post('debug');
		$settings['disable'] = $this->EE->input->post('disable');
		$settings['remote_mode'] = $this->EE->input->post('remote_mode');
		
		$settings = $this->_normalize_settings($settings);
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($settings)));
		
		$this->EE->session->set_flashdata(
			'message_success',
		 	$this->EE->lang->line('preferences_updated')
		);
	}
	// END

	/**
	 * Settings Form
	 *
	 * @param	Array	Current settings from DB
	 * @return 	void
	 */
	public function settings_form($current)
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('table');

		// view vars		
		$vars = array('config_loc' => $this->config_loc);
		
		// normalize current settings just in case
		$current = $this->_normalize_settings($current);

		$yes_no_options = array(
			'no'	=> lang('no'),
			'yes' 	=> lang('yes') 
		);
		
		$remote_mode_options = array(
			'auto' 	=> lang('auto'), 
			'curl'	=> lang('curl'),
			'fgc' 	=> lang('fgc'),
		);
		
		$vars['settings'] = array(
			'cache_path'	=> form_input(array('name' => 'cache_path', 'id' => 'cache_path', 'value' => $current['cache_path'])),
			'cache_url'		=> form_input(array('name' => 'cache_url', 'id' => 'cache_url', 'value' => $current['cache_url']))
			);
		
		$vars['settings_advanced'] = array(
			'disable'		=> form_dropdown('disable', $yes_no_options, $current['disable'], 'id="disable"'),
			'debug'			=> form_dropdown('debug', $yes_no_options, $current['debug'], 'id="debug"'),
			'remote_mode'	=> form_dropdown('remote_mode', $remote_mode_options, $current['remote_mode'], 'id="remote_mode"'),
			'base_path'		=> form_input(array('name' => 'base_path', 'id' => 'base_path', 'value' => $current['base_path'])),
			'base_url'		=> form_input(array('name' => 'base_url', 'id' => 'base_url', 'value' => $current['base_url'])),
		);
		
		return $this->EE->load->view('settings_form', $vars, TRUE);			
	}


	/**
	 * Update Extension
	 *
	 * @param 	string	String value of current version
	 * @return 	mixed	void on update / false if none
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < '1.0.3')
		{
			$this->EE->db
						->select('settings')
						->from('extensions')
						->where(array('enabled' => 'y', 'class' => __CLASS__ ))
						->limit(1);
			$query = $this->EE->db->get();
			
			if ($query->num_rows() > 0)
			{
				$this->settings = unserialize($query->row()->settings);
				
				// convert boolean to string
				$this->settings['disable'] = ($this->settings['disable']) ? 'yes' : 'no';
				
				// remove legacy strict mode
				unset($this->settings['strict']);
				
				// add new remote_mode
				$this->settings['remote_mode'] = 'auto';
				
				//normalize just to be safe
				$this->settings = $this->_normalize_settings($this->settings);

				// update db				
				$this->EE->db
						->where(array('enabled' => 'y', 'class' => __CLASS__ ))
						->update('extensions', array('settings' => serialize($this->settings)));
			}
		}

		if ($current < '1.0.4')
		{
			$this->EE->db
						->select('settings')
						->from('extensions')
						->where(array('enabled' => 'y', 'class' => __CLASS__ ))
						->limit(1);
			$query = $this->EE->db->get();
			
			if ($query->num_rows() > 0)
			{
				$this->settings = unserialize($query->row()->settings);

				// add new debug
				$this->settings['debug'] = 'no';
				
				//normalize just to be safe
				$this->settings = $this->_normalize_settings($this->settings);

				// update db				
				$this->EE->db
						->where(array('enabled' => 'y', 'class' => __CLASS__ ))
						->update('extensions', array('settings' => serialize($this->settings)));
						
			}
		}
				
		if ($current < '1.1.2')
		{
			$this->EE->db
						->select('settings')
						->from('extensions')
						->where(array('enabled' => 'y', 'class' => __CLASS__ ))
						->limit(1);
			$query = $this->EE->db->get();
			
			if ($query->num_rows() > 0)
			{
				$this->settings = unserialize($query->row()->settings);

				// add new base_path & base_url
				$this->settings['base_path'] = '';
				$this->settings['base_url'] = '';
				
				//normalize just to be safe
				$this->settings = $this->_normalize_settings($this->settings);

				// update db				
				$this->EE->db
						->where(array('enabled' => 'y', 'class' => __CLASS__ ))
						->update('extensions', array('settings' => serialize($this->settings)));
						
			}
		}
				
		// update table row with version
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);
	}
	// END

	
	/**
	 * Returns a default array of settings
	 *
	 * @return array default settings & values
	 */
	function _default_settings()
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
	 * Standardise settings just to be safe!
	 *
	 * @param array an array of options to be normalised
	 * @return void
	 */
	private function _normalize_settings($settings)
	{
		// this ensures we avoid any PHP errors
		$settings = array_merge($this->_default_settings(), $settings);

		// required
		$settings['cache_path'] = rtrim($settings['cache_path'], '/');
		$settings['cache_url'] = rtrim($settings['cache_url'], '/');
		
		// optional
		$settings['base_path'] = rtrim($settings['base_path'], '/');
		$settings['base_url'] = rtrim($settings['base_url'], '/');
		$settings['debug'] = (in_array(strtolower($settings['debug']), array('yes', 'y', 'on'))) ? 'yes' : 'no'; // default = 'no'
		$settings['disable'] = ($settings['disable'] === TRUE OR in_array(strtolower($settings['disable']), array('yes', 'y', 'on'))) ? 'yes' : 'no'; // default = 'no'
		$settings['remote_mode'] = (in_array(strtolower($settings['remote_mode']), array('auto', 'fgc', 'curl'))) ? strtolower($settings['remote_mode']) : 'auto'; // default = 'auto'
		
		return $settings;
	}
	// END

}
// END CLASS

	
/* End of file ext.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/ext.minimee.php */