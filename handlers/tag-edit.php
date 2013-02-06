<?php
/**
 * kkm
 * Tag editing handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$tag_id = (int)$_POST['tag'];
$new_name = trim($_POST['kkm_tag_name']);
$new_parent = (int)$_POST['kkm_tag_parent'];

/*
 * Load tag from the database.
 */
$tag = $wpdb->get_row(
	$wpdb->prepare(
		'select * from `kkm_tags` where `tid`=%d',
		$tag_id
	),
	ARRAY_A
);
if (empty($tag) || empty($new_name)) {
	kkm::redirect_to_error_page();
}

/*
 * Update tag name.
 * The parent will be updated with the library function.
 */
$wpdb->query(
	$wpdb->prepare(
		'update `kkm_tags` set `name`=%s where `tid`=%d limit 1;',
		$new_name,
		$tag_id
	)
);
if (!empty($wpdb->last_error)) {die($wpdb->last_error);
	kkm::redirect_to_error_page();
}

/*
 * Rebuild tag hierarchy relations if needed.
 */
$parent = (int)$new_parent;
if ($parent <= 0) {
	$parent = 'null';
}
if ($parent != $tag['parent']) {
	if (!kkm_tags::update_tag_parent($tag_id, $parent)) {
		kkm::redirect_to_error_page();
	}
}

//Redirect to the tag list
$absolute_url = site_url('kkm/tags');
wp_redirect($absolute_url);//Temporal redirect
?>