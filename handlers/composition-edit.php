<?php
/**
 * kkm
 * Composition editing handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$comp_id = $_POST['comp'];
$new_title = trim($_POST['title']);

/*
 * Load composition from the database.
 */
$comp = $wpdb->get_row(
	$wpdb->prepare(
		'select * from `kkm_compositions` where `compid`=%d',
		$comp_id
	),
	ARRAY_A
);
if (empty($comp) || empty($new_title)) {
	kkm::redirect_to_error_page();
}

/*
 * Update title if needed.
 */
if ($new_title != $comp['title']) {
	$wpdb->query(
		$wpdb->prepare(
			'update `kkm_compositions` set `title`=%s where `compid`=%d limit 1;',
			$new_title,
			$comp_id
		)
	);
	if (!empty($wpdb->last_error)) {
		//An error occurred while updating the title. Possible reason: duplicate title.
		kkm::redirect_to_error_page();
	}
}

/*
 * Update tags.
 */
$taglist = $_POST['taglist'];
kkm_tags::save_tags_for_comp($comp_id, $taglist);

//Redirect to the composition
$absolute_url = site_url('kkm/composition/'.$comp_id);
wp_redirect($absolute_url);//Temporal redirect
?>