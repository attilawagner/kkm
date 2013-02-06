<?php
/**
 * kkm
 * Converter option handling functions
 * 
 * @author Attila Wagner
 */

class kkm_options {
	
	/**
	 * Renders a form for choosing the options for a converter.
	 * 
	 * @param integer $doc_id Document ID in the database.
	 * @param string $module Converter module class name.
	 * @param string $source_file Absolute URL of the file to convert. Forwarded to the module.
	 * @param string $form_action Handler name.
	 */
	public static function render_options_form($doc_id, $module, $source_file, $form_action) {
		$option_template = call_user_func(array($module, 'get_conversion_options'), $source_file);
		
		$url = site_url('kkm/');
		echo('<form class="kkm_converter_options" action="'.$url.'" method="post" onsubmit="return kkm_conversion_validate(this);">');
		echo('<input type="hidden" name="action" value="'.$form_action.'"/>');
		echo('<input type="hidden" name="doc" value="'.$doc_id.'"/>');
		echo('<input type="hidden" name="module" value="'.$module.'"/>');

		//Render table
		echo('<table>');
		
		//First row is the filename
		echo('<tr><th>'.__('Save file as:','kkm').'</th><td><input type="text" name="filename" title="'.__('Name of the output file, without the extension.','kkm').'"/></td></tr>');
		
		if (!empty($option_template)) {
			//Each parameter gets a separate row
			foreach ($option_template as $option_name => $option_data) {
				@list($type, $name, $params) = $option_data;
				
				echo('<tr>');
				//Name in first column
				echo('<th>'.$name.'</th>');
				//Form field in the second column
				echo('<td>'.kkm_options::render_form_field($option_name, $type, $params).'</td>');
				echo('</tr>');
			}
		}

		echo('</table>');
		echo('<div id="kkm_converter_feedback"></div>');
		echo('<input type="submit" name="convert" value="'.__('Start conversion','kkm').'" />');
		echo('</form>');
	}
	
	/**
	 * Renders a form element for the given option variable.
	 * 
	 * @param string $option_name Name of the field.
	 * @param string $type Type of the field. Accepted values are 'text' and 'list'.
	 * @param array $params Parameters for the field.
	 * @return string The generated HTML fragment.
	 */
	private static function render_form_field($option_name, $type, $params) {
		switch ($type) {
			case 'text':
				return kkm_options::render_text_field($option_name);
				break;
			case 'list':
				return kkm_options::render_list_field($option_name, $params);
				break;
		}
	}
	
	/**
	 * Renders a text field to be displayed on the form.
	 * 
	 * @param string $option_name Name of the field.
	 * @return string The generated HTML fragment.
	 */
	private static function render_text_field($option_name) {
		return '<input type="text" name="kkm_option['.$option_name.']" />';
	}
	
	/**
	 * Renders a list (&lt;select&gt;) field to be displayed on the form.
	 *
	 * @param string $option_name Name of the field.
	 * @param array $params List items.
	 * If non-numerical keys are defined, they will be used as the values
	 * for the list elements.
	 * @return string The generated HTML fragment.
	 */
	private static function render_list_field($option_name, $params) {
		$ret = '<select name=kkm_option['.$option_name.']">';
		
		foreach ($params as $value => $label) {
			if (is_numeric($value)) {
				$value = $label;
			}
			$ret .= '<option value="'.$value.'">'.$label.'</option>';
		}
		
		$ret .= '</select>';
		return $ret;
	}
}
?>