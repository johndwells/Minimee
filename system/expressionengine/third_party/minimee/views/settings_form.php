<?php

	// some variables to use along the way
	$label = $setting = $hint = '';
	$note_format = '<small style="display:block;font-size:.95em;font-weight:normal;margin-top:0.5em">%s</small>';
	$hint_format = '<small style="display:block;font-size:.9em;font-weight:normal;margin-top:0.5em">%s</small>';

	// only open form table if we have 'db' config settings
	if ($config_loc != 'db')
	{
		echo '<p><br />' . lang('config_loc_caution_' . $config_loc) . '<br /><br /></p>';
	}

	echo $form_open;


	/**
	 * Disable
	 */
	$label = lang('disable', 'disable');
	$setting = form_dropdown('disable', array('no' => lang('no'),'yes' => lang('yes')), $settings['disable'], 'id="disable"');
	echo '<p>' . $label . '&nbsp;&nbsp;' . $setting . '</p>';


	/**
	 * begin our DOM wrapper
	 */
	echo '<div class="minimee_settings">';


	/**
	 * Open 'basic' table
	 */
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('basic_config'), 'style' => 'width:50%;'),
	    lang('setting')
	);
	

	/**
	 * Cache Path
	 */
	$label = lang('cache_path', 'cache_path') . sprintf($note_format, lang('cache_path_note'));
	$setting = form_input(array('name' => 'cache_path', 'id' => 'cache_path', 'value' => $settings['cache_path']))
			 . sprintf($hint_format, lang('cache_path_hint'));
	$this->table->add_row($label, $setting);


	/**
	 * Cache URL
	 */
	$label = lang('cache_url', 'cache_url') . sprintf($note_format, lang('cache_url_note'));
	$setting = form_input(array('name' => 'cache_url', 'id' => 'cache_url', 'value' => $settings['cache_url']))
			 . sprintf($hint_format, lang('cache_url_hint'));
	$this->table->add_row($label, $setting);


	/**
	 * Combine settings
	 */
	$label = lang('combine', 'combine') . sprintf($note_format, lang('combine_note'));
	$setting = '<label for="combine_css">CSS&nbsp;' . form_checkbox(array('name' => 'combine_css', 'id' => 'combine_css', 'value' => 'yes', 'checked' => ($settings['combine_css'] == 'yes'))) . '</label>';
	$setting .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="combine_js">JS&nbsp;' . form_checkbox(array('name' => 'combine_js', 'id' => 'combine_js', 'value' => 'yes', 'checked' => ($settings['combine_js'] == 'yes'))) . '</label>';
	$this->table->add_row($label, $setting);


	/**
	 * Minify settings
	 */
	$label = lang('minify', 'minify') . sprintf($note_format, lang('minify_note'));
	$setting = '<label for="minify_css">CSS&nbsp;' . form_checkbox(array('name' => 'minify_css', 'id' => 'minify_css', 'value' => 'yes', 'checked' => ($settings['minify_css'] == 'yes'))) . '</label>';
	$setting .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="minify_js">JS&nbsp;' . form_checkbox(array('name' => 'minify_js', 'id' => 'minify_js', 'value' => 'yes', 'checked' => ($settings['minify_js'] == 'yes'))) . '</label>';
	$setting .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="minify_html">HTML&nbsp;' . form_checkbox(array('name' => 'minify_html', 'id' => 'minify_html', 'value' => 'yes', 'checked' => ($settings['minify_html'] == 'yes'))) . '</label>';
	$this->table->add_row($label, $setting);


	/**
	 * Spit our our 'basic' table
	 */
	echo $this->table->generate();

	/**
	 * Begin building our 'advanced' table
	 */
	$this->table->clear();
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('advanced_config'), 'style' => 'width:50%;'),
	    lang('setting')
	);


	/**
	 * Base Path
	 */
	$label = lang('base_path', 'base_path') . sprintf($note_format, lang('base_path_note'));
	$setting = form_input(array('name' => 'base_path', 'id' => 'base_path', 'value' => $settings['base_path']))
			 . sprintf($hint_format, lang('base_path_hint'));
	$this->table->add_row($label, $setting);


	/**
	 * Base URL
	 */
	$label = lang('base_url', 'base_url') . sprintf($note_format, lang('base_url_note'));
	$setting = form_input(array('name' => 'base_url', 'id' => 'base_url', 'value' => $settings['base_url']))
			 . sprintf($hint_format, lang('base_url_hint'));
	$this->table->add_row($label, $setting);


	/**
	 * Cachebust
	 */
	$label = lang('cachebust', 'cachebust') . sprintf($note_format, lang('cachebust_note'));
	$setting = form_input(array('name' => 'cachebust', 'id' => 'cachebust', 'value' => $settings['cachebust']))
			 . sprintf($hint_format, lang('cachebust_hint'));
	$this->table->add_row($label, $setting);


	/**
	 * CSS Prepend Mode
	 */
	$label = lang('css_prepend_mode', 'css_prepend_mode') . sprintf($note_format, lang('css_prepend_mode_note'));
	$setting = form_dropdown('css_prepend_mode', array('yes' => lang('On'),'no' => lang('Off')), $settings['css_prepend_mode'], 'id="css_prepend_mode"');
	$this->table->add_row($label, $setting);


	/**
	 * CSS Prepend URL
	 */
	$label = lang('css_prepend_url', 'css_prepend_url') . sprintf($note_format, lang('css_prepend_url_note'));
	$setting = form_input(array('name' => 'css_prepend_url', 'id' => 'css_prepend_url', 'value' => $settings['css_prepend_url']))
			 . sprintf($hint_format, lang('css_prepend_url_hint'));
	$this->table->add_row($label, $setting);


	/**
	 * Remote mode
	 */
	$label = lang('remote_mode', 'remote_mode') . sprintf($note_format, lang('remote_mode_note'));
	$setting = form_dropdown('remote_mode', array('auto' => lang('auto'),'curl' => lang('curl'),'fgc' => lang('fgc')), $settings['remote_mode'], 'id="remote_mode"');
	$this->table->add_row($label, $setting);


	/**
	 * Spit out our advanced table
	 */
	 echo $this->table->generate();


	/**
	 * end our DOM wrapper
	 */
	echo '</div> <!-- /.minimee_settings -->';


	/**
	 * finish form
	 */
	echo '<p>' . form_submit('submit', lang('save'), 'class="submit"') . '</p>';
	$this->table->clear();
	echo form_close();
?>

	<script type="text/javascript">
		jQuery(function($) {
		
			var MINIMEE = MINIMEE || [];

			MINIMEE.$settings = $('.minimee_settings');
			
			MINIMEE.toggleSettings = function(val) {
			
				if(val == 'no')
				{
					MINIMEE.$settings.slideDown(600);
				}
				
				else {
					MINIMEE.$settings.slideUp(300);
				}
			
			};

			$('select[name="disable"]').change(function() {
				MINIMEE.toggleSettings($(this).val());
			}).trigger('change');
				
		});
	</script>

<?php
/* End of file settings_form.php */
/* Location: ./system/expressionengine/third_party/minimee/views/settings_form.php */