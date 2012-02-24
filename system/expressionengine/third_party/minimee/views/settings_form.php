<?php

	// extra params for form_() methods
	$extra_string = '';
	$extra_array = array();

	// only open form table if we have 'db' config settings
	if ($config_loc == 'db')
	{
		echo $form_open;
	}
	else
	{
		echo '<p class="notice">' . lang('config_loc_caution_' . $config_loc) . '<br /><br /></p>';
		
		$extra_string = ' disabled="disabled" ';
		$extra_array['disabled'] = 'disabled';
	}
	
	/**
	 * Open 'basic' table
	 */
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('basic_config'), 'style' => 'width:50%;'),
	    lang('setting')
	);
	
	// some variables to use along the way
	$label = $setting = '';
	$note_format = '<small style="display:block;font-weight:normal;margin-top:0.5em">%s</small>';
	
	
	/**
	 * Cache Path
	 */
	$label = lang('cache_path', 'cache_path') . sprintf($note_format, lang('note_cache_path'));
	$setting = form_input(array_merge($extra_array, array('name' => 'cache_path', 'id' => 'cache_path', 'value' => $settings['cache_path'])));
	$this->table->add_row($label, $setting);


	/**
	 * Cache URL
	 */
	$label = lang('cache_url', 'cache_url') . sprintf($note_format, lang('note_cache_url'));
	$setting = form_input(array_merge($extra_array, array('name' => 'cache_url', 'id' => 'cache_url', 'value' => $settings['cache_url'])));
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
	 * Disable
	 */
	$label = lang('disable', 'disable') . sprintf($note_format, lang('note_disable'));
	$setting = form_dropdown('disable', array('no' => lang('no'),'yes' => lang('yes')), $settings['disable'], 'id="disable" ' . $extra_string);
	$this->table->add_row($label, $setting);


	/**
	 * Combine settings
	 */
	$label = lang('combine', 'combine') . sprintf($note_format, lang('note_combine'));
	$setting = '<label for="combine_css">CSS&nbsp;' . form_checkbox(array_merge($extra_array, array('name' => 'combine_css', 'id' => 'combine_css', 'value' => 'yes', 'checked' => ($settings['combine_css'] == 'yes')))) . '</label>';
	$setting .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="combine_js">JS&nbsp;' . form_checkbox(array_merge($extra_array, array('name' => 'combine_js', 'id' => 'combine_js', 'value' => 'yes', 'checked' => ($settings['combine_js'] == 'yes')))) . '</label>';
	$this->table->add_row($label, $setting);


	/**
	 * Minify settings
	 */
	$label = lang('minify', 'minify') . sprintf($note_format, lang('note_minify'));
	$setting = '<label for="minify_css">CSS&nbsp;' . form_checkbox(array_merge($extra_array, array('name' => 'minify_css', 'id' => 'minify_css', 'value' => 'yes', 'checked' => ($settings['minify_css'] == 'yes')))) . '</label>';
	$setting .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="minify_js">JS&nbsp;' . form_checkbox(array_merge($extra_array, array('name' => 'minify_js', 'id' => 'minify_js', 'value' => 'yes', 'checked' => ($settings['minify_js'] == 'yes')))) . '</label>';
	$setting .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="minify_html">HTML&nbsp;' . form_checkbox(array_merge($extra_array, array('name' => 'minify_html', 'id' => 'minify_html', 'value' => 'yes', 'checked' => ($settings['minify_html'] == 'yes')))) . '</label>';
	$this->table->add_row($label, $setting);


	/**
	 * Base Path
	 */
	$label = lang('base_path', 'base_path') . sprintf($note_format, lang('note_base_path'));
	$setting = form_input(array_merge($extra_array, array('name' => 'base_path', 'id' => 'base_path', 'value' => $settings['base_path'])));
	$this->table->add_row($label, $setting);


	/**
	 * Base URL
	 */
	$label = lang('base_url', 'base_url') . sprintf($note_format, lang('note_base_url'));
	$setting = form_input(array_merge($extra_array, array('name' => 'base_url', 'id' => 'base_url', 'value' => $settings['base_url'])));
	$this->table->add_row($label, $setting);



?>
	<p><a href="#" id="minimee_advanced_handle"><?php echo lang('advanced_config'); ?></a><br /><br /></p>
	<div id="minimee_advanced_table"><?php echo $this->table->generate(); ?></div>
<?php

	if ($config_loc == 'db')
	{
		echo '<p>' . form_submit('submit', lang('save'), 'class="submit"') . '</p>';
	}
	$this->table->clear();
	echo form_close();
?>

	<script type="text/javascript">
		jQuery(function($) {
			(function($) {
				var $MIN_ADV_TABLE = $('#minimee_advanced_table'),
					$MIN_ADV_HANDLE = $('#minimee_advanced_handle').parent('p');
				$MIN_ADV_TABLE.hide();
				$MIN_ADV_HANDLE.click(function(e) {
					e.preventDefault();
					$MIN_ADV_HANDLE.slideUp();
					$MIN_ADV_TABLE.slideDown();
				});
				$('th', $MIN_ADV_TABLE).click(function(e) {
					e.preventDefault();
					$MIN_ADV_HANDLE.slideDown();
					$MIN_ADV_TABLE.slideUp();
				});
		    })(jQuery);
		});
	</script>

<?php
/* End of file settings_form.php */
/* Location: ./system/expressionengine/third_party/minimee/views/settings_form.php */