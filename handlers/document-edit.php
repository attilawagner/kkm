<?php
/**
 * kkm
 * Document editing handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$doc_id = $_POST['doc'];

/*
 * Load document from the database.
 */
$document = $wpdb->get_row(
	$wpdb->prepare(
		'select * from `kkm_documents` where `docid`=%d and `deleted`=0 limit 1;',
		$doc_id
	),
	ARRAY_A
);
if (empty($document)) {
	kkm::redirect_to_error_page();
}

/*
 * Update tags.
 */
$taglist = $_POST['taglist'];
kkm_tags::save_tags_for_doc($doc_id, $taglist);

//Redirect to the document
$absolute_url = site_url('kkm/document/'.$doc_id);
wp_redirect($absolute_url);//Temporal redirect
?>