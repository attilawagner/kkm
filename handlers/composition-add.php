<?php
/**
 * kkm
 * New composition handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$new_title = trim($_POST['title']);
if (empty($new_title)) {
	kkm::redirect_to_error_page();
}

/*
 * Insert database record
 */

$wpdb->query(
	$wpdb->prepare(
		'insert into `kkm_compositions` (`title`) values (%s);',
		$new_title
	)
);
if (!empty($wpdb->last_error)) {
	//An error occurred while updating the title. Possible reason: duplicate title.
	kkm::redirect_to_error_page();
}

$comp_id = $wpdb->insert_id;

/*
 * Insert tags into DB.
 */
$taglist = $_POST['taglist'];
kkm_tags::save_tags_for_comp($comp_id, $taglist);

//Redirect to the composition
$absolute_url = site_url('kkm/composition/'.$comp_id);
wp_redirect($absolute_url);//Temporal redirect
?>