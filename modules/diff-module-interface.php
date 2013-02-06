<?php

/**
 * Common interface that the diff modules must implement.
 * @author Attila Wagner
 */
interface kkm_diff_module {
	
	/**
	 * Returns the human-readable name of the module along with a short description.
	 * These will be displayed both on the admin page and on the frontend.
	 *
	 * An example of a valid return value:
	 * array(
	 *   'name' => 'Lyrics diff',
	 *   'description' => 'Displays the two versions of the lyrics highlighting the differences.'
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
	 * Calculates the difference between two files of the same format.
	 * 
	 * The returned HTML code will be inserted into the diff page
	 * and presented to the user.
	 * 
	 * @param string $path1 Full (absolute) path to the file.
	 * @param string $path2 Full (absolute) path to the file.
	 * @return string HTML fragment.
	 */
	public static function get_diff($path1, $path2);
}
