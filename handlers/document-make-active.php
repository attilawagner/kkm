<?php
/**
 * kkm
 * Mark document version as active
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$doc_id = $page_params[0];

/*
 * Load the ID of the first version. 
 */
$first_version_id = $wpdb->get_var(
	$wpdb->prepare(
		'select `firstver` from `kkm_documents` where `docid`=%d and `deleted`=0 limit 1;',
		$doc_id
	)
);

if (empty($first_version_id)) {
	kkm::redirect_to_error_page();
}

/*
 * Set every other version as inactive,
 * and make this version active.
 */
$wpdb->query(
	$wpdb->prepare(
		'update `kkm_documents` set `activever`=0 where `firstver`=%d;',
		$first_version_id
	)
);
$wpdb->query(
	$wpdb->prepare(
		'update `kkm_documents` set `activever`=1 where `docid`=%d;',
		$doc_id
	)
);

//Redirect to the document
$absolute_url = site_url('kkm/document/'.$doc_id);
wp_redirect($absolute_url);//Temporal redirect
?>