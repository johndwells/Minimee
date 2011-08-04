<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'minimee/config.php';
require_once PATH_THIRD . 'minimee/helper.php';

/**
 * Minimee: minimize & combine your CSS and JS files. For EE2 only.
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_ext {

	public $name			= MINIMEE_NAME;
	public $version			= MINIMEE_VER;
	public $description		= MINIMEE_DESC;
	public $docs_url		= MINIMEE_DOCS;
	public $settings_exist	= 'y';

	public $settings		= array();
	
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
		$this->settings = Minimee_helper::default_settings();
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
		
		Minimee_helper::normalize_settings($settings);
		
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
		$vars = array('config_loc' => Minimee_helper::config());
		
		// normalize current settings just in case
		Minimee_helper::normalize_settings($current);

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
				Minimee_helper::normalize_settings($this->settings);

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
				Minimee_helper::normalize_settings($this->settings);

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
				Minimee_helper::normalize_settings($this->settings);

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

	
}
// END CLASS

	
/* End of file ext.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/ext.minimee.php */