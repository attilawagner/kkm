<?php
/**
 * kkm
 * User handling functions
 * 
 * @author Attila Wagner
 */

class kkm_user {
	
	/**
	 * The user has right to access the database.
	 * @var integer
	 */
	const RIGHT_READ = 1;
	
	/**
	 * The user can upload documents into the database, modify tags, etc.
	 * @var integer
	 */
	const RIGHT_WRITE = 2;
	
	/**
	 * Returns the highest access level the user possesses.
	 * @return integer The permission level of the current user.
	 * Comparable to the RIGHT_* constants of this class.
	 */
	public static function get_rights() {
		if (is_user_logged_in()) {
			if (current_user_can('edit_posts')) {
				return self::RIGHT_WRITE;
			} else {
				return self::RIGHT_READ;
			}
		}
		return 0;
	}
}
?>