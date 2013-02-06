<?php

/**
 * Common interface that the parser modules must implement.
 * @author Attila Wagner
 */
interface kkm_parser_module {
	
	/**
	 * Returns the human-readable name of the module along with a short description.
	 * These will be displayed both on the admin page and on the frontend.
	 *
	 * An example of a valid return value:
	 * array(
	 *   'name' => 'LilyPond parser',
	 *   'description' => 'Can load .ly files.'
	 * )
	 *
	 * @return array An array of strings.
	 */
	public static function get_name();
	
	/**
	 * Returns an array of the accepted document formats.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   'LilyPond'
	 * )
	 * 
	 * @return array An array of strings containing file format labels.
	 */
	public static function get_accepted_formats();
	
	/**
	 * Loads and validates the given file.
	 * The validation will be only called with files that belongs to
	 * one of the formats defined by the get_accepted_formats() function.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   array('warning', 'No author specified.'),
	 *   array('error', 'Invalid file format: this is not a valid LilyPond file.')
	 * )
	 * 
	 * If an error is returned, the file upload will be terminated. If only warning messages are returned,
	 * they will be shown to the users, and they might choose to continue the uploading/saving progress.
	 * 
	 * @param string $path Full (absolute) path to the file.
	 * @return array An array of error and warning messages.
	 * A message is an array containing its type ('warning' or 'error') and the message string itself.
	 * For valid files an empty array should be returned. If the validation is not or cannot be implemented,
	 * null should be returned (e.g. it's only metadata loader module).
	 */
	public static function validate($path);
	
	/**
	 * Loads metadata from the file.
	 * 
	 * An example of a valid return value:
	 * array(
	 *   'composer' => array('Jonathan Davis', 'Richard Gibbs'),
	 *   'title' => 'Not meant for me'
	 * )
	 * The key is treated as a Tag category name, and the system may select existing tags
	 * from this category or suggest creating new ones with the label specified in the value.
	 * If multiple values belong to a key, they should be grouped into an array.
	 * 
	 * @param string $path Full (absolute) path to the file.
	 * @return array Array containing metadata as key-value pairs.
	 * An empty array or null should be returned for files that do not contain metadata,
	 * or in the case the class cannot load it.
	 */
	public static function get_metadata($path);
	
	/**
	 * Saves modified metadata into the file.
	 * 
	 * The system may call this function before saving the uploaded file into
	 * the storage, if any metadata has been modified or assigned to the document.
	 * 
	 * @param string $path Full (absolute) path to the file.
	 * @param array $meta An array of metadata. The format is the same
	 * as for the return value of get_metadata() function.
	 * @see kkm_parser_module::get_metadata($path)
	 */
	public static function save_metadata($path, $meta);
	
	/**
	 * Loads the file and generates a preview for it.
	 *
	 * This preview will be presented to the user during the document uploading/saving process.
	 *
	 * @param string $path Full (absolute) path to the file.
	 * @return string HTML segment that will be included into the page and displayed to the user
	 * during the uploading/saving process.
	 */
	public static function get_preview($path);
}
