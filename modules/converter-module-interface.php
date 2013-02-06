<?php

/**
 * Common interface that the converter modules must implement.
 * @author Attila Wagner
 */
interface kkm_converter_module {
	
	/**
	 * Returns the human-readable name of the module along with a short description.
	 * These will be displayed both on the admin page and on the frontend.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   'name' => 'PDF Converter',
	 *   'description' => 'Converty LilyPond documents into PDF.'
	 * )
	 *
	 * @return array An array of strings.
	 */
	public static function get_name();
	
	/**
	 * Returns whether the converter can be called automatically upon a file upload.
	 * If the module may require information specific to a single input file, it should return false.
	 *
	 * @return boolean True if the converter can be run unattended, false otherwise.
	*/
	public static function is_automatic();
	
	/**
	 * Returns an array of document format pairs that this
	 * class is capable to convert between.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   array('LilyPond', 'PDF Sheet')
	 * )
	 * 
	 * @return array An array of document format pairs.
	 */
	public static function get_conversion_formats();
	
	/**
	 * Returns a file extension to be used in the name
	 * of the destination file.
	 * 
	 * @return string Extension used in the filename.
	 */
	public static function get_target_extension();
	
	/**
	 * Returns an array of options that are presented to the user.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   'title'=> array('text', 'Custom title for the document:'),
	 *   'size' => array('list', 'Select paper size:', array('A4','A5')),
	 *   'orientation' => array('list', 'Select paper orientation:', array('p' => 'Portrait', 'l' => 'Landscape'))
	 * )
	 * Text options are displayed as a text type input field,
	 * lists are displayed as dropdown menus with the options listed in the
	 * third element of the array. If the keys of the array are string, it will be used
	 * in the value attribute of the &lt;option&gt; tag.
	 * 
	 * @param string $path Full (absolute) path to the input file.
	 * @return array Array of option descriptions.
	 */
	public static function get_conversion_options($path);
	
	/**
	 * Validates the conversion options entered by the user.
	 * 
	 * This function will get the options as an array:
	 * array(
	 *   'title' => '',
	 *   'size' => 'A4',
	 *   'orientation' => 'p'
	 * )
	 * 
	 * @param string $path Full (absolute) path to the input file.
	 * @param array $options Options as an array.
	 * @return array An array of error messages. If an empty array or null is returned,
	 * the system assumes that the options are valid and calls the convert() function.
	 */
	public static function validate_conversion_options($path, $options);
	
	/**
	 * Converts the input file into the output file.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   array('warning', 'No instrument defined, using Grand Piano by default.'),
	 *   array('note', 'Conversion done.')
	 * )
	 * 
	 * @param string $source Full (absolute) path to the input file.
	 * @param string $destination Full (absolute) path to the output file.
	 * @param array $options Options as an array. See validate_conversion_options for more information.
	 * @return array Messages that will be presented to the user.
	 * A message is an array containing its type ('note', 'warning' or 'error') and the message string itself.
	 * If error is returned, the generated file (if it exists) will be deleted. Notes and warnings are
	 * presented to the user in different ways, but do not have any effect on the conversion pipeline.
	 */
	public static function convert($source, $destination, $options);
}
