<?php
/**
 * kkm
 * Tag deletion handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$tag_id = $_POST['tag'];
$deletion_confirmed = isset($_POST['delete_tag']);

/*
 * Check whether the deletion has been cancelled.
 */
if ($deletion_confirmed == false) {
	//Cancelled, redirect to composition page
	$absolute_url = site_url('kkm/composition/'.$tag_id);
	wp_redirect($absolute_url);//Temporal redirect
	die();
}

/*
 * Delete it using the library function
 */
if (!kkm_tags::delete_tag($tag_id)) {
	kkm::redirect_to_error_page();
}

//Redirect to tag list page
$absolute_url = site_url('kkm/tags');
wp_redirect($absolute_url);//Temporal redirect
?>