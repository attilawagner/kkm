<?php

/**
 * Sample converter
 * @author Attila Wagner
 */
class kkm_lyrics_case_converter implements kkm_converter_module {
	
	public static function get_name() {
		return array(
			'name' => 'Lyrics Case Converter',
			'description' => 'Converts a plain text lyrics file to contain lowercase/UPPERCASE/Title Case words.'
		);
	}
	
	public static function is_automatic() {
		return true;
	}
	
	public static function get_conversion_formats() {
		return array(
			array('Lyrics', 'Lyrics')
		);
	}
	
	/**
	 * @see kkm_parser_module::get_target_extension
	 */
	public static function get_target_extension() {
		return 'txt';
	}
	
	public static function get_conversion_options($path) {
		return array(
			'title'=> array('text', 'Custom title to be inserted into the document:'),
			'title_position' => array('list', 'Select the position of the title:', array('Above content','Below content')),
			'case' => array('list', 'Select target case:', array('u' => 'UPPERCASE', 'l' => 'lowercase', 't'=> 'Title Case'))
		);
	}
	
	public static function validate_conversion_options($path, $options) {
		$messages = array();
		if (!in_array($options['title_position'], array('Above content','Below content'))) {
			$messages[] = array('error', 'Position is invalid.');
		}
		if (!in_array($options['case'], array('u','l','t'))) {
			$messages[] = array('error', 'Orientation is not supported.');
		}
		if (empty($options['title'])) {
			$messages[] = array('error', 'You must specify a title.');
		}
		return $messages;
	}
	
	
	public static function convert($source, $destination, $options) {
		$messages = array();
		
		$original_text = file_get_contents($source);
		switch ($options['case']) {
			case 'u':
				$converted_text = strtoupper($original_text);
				break;
			case 'l':
				$converted_text = strtolower($original_text);
				break;
			case 't':
				$converted_text = ucwords(strtolower($original_text));
				break;
		}
		
		$title = $options['title'];
		if ($options['title_position'] == 'Above content') {
			$converted_text = $title ."\n\n\n".$converted_text;
		} else {
			$converted_text = $converted_text."\n\n\n".$title;
		}
		
		file_put_contents($destination, $converted_text);
		
		return $messages;
	}
}
?>