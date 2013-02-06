<?php
/**
 * kkm
 * Download request
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$filename_with_id = $page_params[0];

if (!preg_match('%^(\d+)-([^/]+)$%s', $filename_with_id, $regs)) {
	kkm::redirect_to_error_page();
}
$doc_id = $regs[1];
$filename = $regs[2];

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
if (empty($document) || $filename != $document['filename']) {
	kkm::redirect_to_error_page();
}

$doc_path = WP_CONTENT_DIR.'/'.kkm_files::get_document_path($doc_id, $document['filename']);
kkm_files::stream_file($doc_path, $document['filename']);
?>