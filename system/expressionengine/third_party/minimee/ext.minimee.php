<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'minimee/models/Minimee_helper.php';

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

	public $EE;

	public $log;
	public $cache;
	public $config;

	/**
	 * Constructor
	 *
	 * NOTE: We never use the $settings variable passed to us,
	 * because we want our Minimee_config object to always be in charge.
	 * There is an edge case where someone has configured Minimee via an extension,
	 * and then moved to config bootstrap. The bootstrap takes precedence.
	 *
	 * @param 	mixed	Settings array - only passed when activating a hook
	 * @return void
	 */
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();

		// grab alias of our cache
		$this->cache =& Minimee_helper::cache();

		// create our config object
		$this->config = new Minimee_config();
		
		// create our logger
		$this->log = new Minimee_logger();

		$this->log->info('Extension has been instantiated.');
	}
	// ------------------------------------------------------


	/**
	 * Activate Extension
	 * 
	 * @return void
	 */
	public function activate_extension()
	{
		$data = array(
			'class'		=> __CLASS__,
			'hook'		=> 'template_post_parse',
			'method'	=> 'minify_html',
			'settings'	=> serialize(array()),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);

		$this->log->info('Extension has been activated.');
	}
	// ------------------------------------------------------


	/**
	 * Disable Extension
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');

		$this->log->info('Extension has been disabled.');
	}
	// ------------------------------------------------------


	/**
	 * Method for template_post_parse hook
	 *
	 * @param 	string	Parsed template string
	 * @param 	bool	Whether is a sub-template or not
	 * @param 	string	Site ID
	 * @return 	string	Template string, possibly minified
	 */
	public function minify_html($template, $sub, $site_id)
	{
		// play nice with others
		if (isset($this->EE->extensions->last_call) && $this->EE->extensions->last_call)
		{
			$template = $this->EE->extensions->last_call;
		}

		// do nothing if not final template
		if($sub !== FALSE)
		{
			return $template;
		}
		
		// do not run through HTML minifier?
		if($this->config->no('minify') || $this->config->no('minify_html'))
		{
			return $template;
		}

		$this->log->info('Running HTML minification.');

		// we've made it this far, so...
		Minimee_helper::library('html');
		return Minify_HTML::minify($template);
	}
	// ------------------------------------------------------


	/**
	 * Save settings
	 *
	 * @return 	void
	 */
	public function save_settings()
	{
		if (empty($_POST))
		{
			$this->log->error($this->EE->lang->line('unauthorized_access'));
		}

		// because our settings default to "yes", we need to ensure these values are in
		$settings = $_POST;
		
		// a non-existent key means "no"
		if( ! isset($settings['combine_css']))
		{
			$settings['combine_css'] = 'no';
		}
		if( ! isset($settings['combine_js']))
		{
			$settings['combine_js'] = 'no';
		}
		if( ! isset($settings['minify_css']))
		{
			$settings['minify_css'] = 'no';
		}
		if( ! isset($settings['minify_html']))
		{
			$settings['minify_html'] = 'no';
		}
		if( ! isset($settings['minify_js']))
		{
			$settings['minify_js'] = 'no';
		}

		// Protected by our sanitise_settings() method, we are safe to pass all of $_POST
		$settings = $this->config->sanitise_settings(array_merge($this->config->allowed(), $settings));
		
		$this->EE->db->where('class', __CLASS__)
					 ->update('extensions', array('settings' => serialize($settings)));
		
		$this->EE->session->set_flashdata(
			'message_success',
		 	$this->EE->lang->line('preferences_updated')
		);

		$this->log->info('Extension settings have been saved.');
		
		unset($settings);
	}
	// ------------------------------------------------------


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

		// Merge the contents of our db with the allowed
		$current = array_merge($this->config->allowed(), $current);

		// view vars		
		$vars = array(
			'config_loc' => $this->config->location,
			'disabled' => ($this->config->location != 'db'),
			'form_open' => form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=minimee'),
			'settings' => $current
			);

		// return our view
		return $this->EE->load->view('settings_form', $vars, TRUE);			
	}
	// ------------------------------------------------------


	/**
	 * Update Extension
	 *
	 * @param 	string	String value of current version
	 * @return 	mixed	void on update / false if none
	 */
	public function update_extension($current = '')
	{
		/**
		 * Up-to-date
		 */
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		/**
		 * 2.0.0
		 * 
		 * - refactor to use new Minimee_config object
		 */
		if ($current < '2.0.0')
		{
			$query = $this->EE->db
							->select('settings')
							->from('extensions')
							->where('class', __CLASS__)
							->limit(1)
							->get();
			
			if ($query->num_rows() > 0)
			{
				$settings = unserialize($query->row()->settings);

				// Sanitise & merge to get a complete up-to-date array of settings
				$settings = $this->config->sanitise_settings(array_merge($this->config->allowed, $settings));
				
				// update db				
				$this->EE->db
						->where('class', __CLASS__)
						->update('extensions', array(
							'hook'		=> 'template_post_parse',
							'method'	=> 'minify_html',
							'settings' => serialize($settings)
						));
			}
			
			$this->log->info('Upgraded to 2.0.0');
		}

		// update table row with version
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);

		$this->log->info('Upgrade complete. Now running ' . $this->version);
	}
	// ------------------------------------------------------

}
// END CLASS

	
/* End of file ext.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/ext.minimee.php */