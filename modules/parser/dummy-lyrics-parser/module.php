<?php

class kkm_dummy_lyrics_parser implements kkm_parser_module {
	
	/**
	 * We return name and description.
	 *
	 * (non-PHPdoc)
	 * @see kkm_parser_module::get_name()
	 */
	public static function get_name(){
		return array(
			'name' => 'Dummy lyrics parser',
			'description' => 'This parser checks if lyrics contain enough of foo bar.'
		);
	}
	
	/**
	 * We only accept documents with the lyrics type tag.
	 * 
	 * (non-PHPdoc)
	 * @see kkm_parser_module::get_accepted_formats()
	 */
	public static function get_accepted_formats() {
		return array('Lyrics');
	}
	
	/**
	 * Some useless validation.
	 * 
	 * (non-PHPdoc)
	 * @see kkm_parser_module::validate()
	 */
	 
	public static function validate($path) {
		$doc = file_get_contents($path);
		$messages = array();
				
		if (preg_match('/foo/', $doc) == 0) {
			$messages[] = array('warning', 'These lyrics do not contain any \'foo\'. Should be revised.');
		}
		
		if (preg_match_all('/foo/', $doc, $dontcare) == 1) { //preg_match_all needed the third parameter until php5.4 (and i have 5.3.2)
			$messages[] = array('warning', 'There\'s only one \'foo\'. It\'s ok for now.');
		}
		
		if (preg_match_all('/bar/', $doc, $dontcare) > 5) {
			$messages[] = array('error', 'Too much \'bar\'...');
			//Becaues this is an error, the system will refuse to save this file.
		}
		
		return $messages;
	}
	
	/**
	 * We do not load any meta.
	 * 
	 * (non-PHPdoc)
	 * @see kkm_parser_module::get_metadata()
	 */
	public static function get_metadata($path) {
		return null;
	}
	
	/**
	 * We do not save any meta.
	 * 
	 * (non-PHPdoc)
	 * @see kkm_parser_module::save_metadata()
	 */
	public static function save_metadata($path, $meta) {
	}
	
	/**
	 * As preview we show the first line as preformatted text.
	 * 
	 * (non-PHPdoc)
	 * @see kkm_parser_module::get_preview()
	 */
	public static function get_preview($path) {
		$doc = file_get_contents($path);
		preg_match('/(.*)/', $doc, $matches);
		
		$escaped = str_replace(
			array('&','<','>'),
			array('&amp;','&lt;','&gt;'),
			$matches[1]
		);
		
		return '<pre>'.$escaped.'</pre>';
	}
}
?>
