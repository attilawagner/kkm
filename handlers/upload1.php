<?php
/**
 * kkm
 * Upload - stage 1 handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$comp_id = $_POST['comp'];
$doc_format = $_POST['format'];
$prev_doc_id = $_POST['prev_doc'];

/*
 * Check for composition in the database.
 */
$comp = $wpdb->get_row(
	$wpdb->prepare(
		'select * from `kkm_compositions` where `compid`=%d limit 1;',
		$comp_id
	),
	ARRAY_A
);
if (empty($comp)) {
	kkm::redirect_to_error_page();
}

/*
 * Check for the previous version if provided.
 * Jump to error page if the provided ID is invalid.
 */
if (!empty($prev_doc_id)) {
	$prev_doc = $wpdb->get_row(
		$wpdb->prepare(
			'select * from `kkm_documents` where `docid`=%d and `deleted`=0 limit 1;',
			$prev_doc_id
		),
		ARRAY_A
	);
	
	if (empty($prev_doc)) {
		kkm::redirect_to_error_page();
	}
}

/*
 * Check tag ID
 * Jump to error page if the provided ID does not belong
 * to a tag in the Format category. 
 */
if (!kkm_tags::is_valid_format_tag($doc_format)) {
	kkm::redirect_to_error_page();
}

/*
 * Try to save the file.
 * If an error occurred, jump to the error page.
 */
$doc_id = kkm_files::save_document($_FILES['document'], $comp_id, null, $prev_doc_id);
if (!$doc_id) {
	kkm::redirect_to_error_page();
}

/*
 * Save Format tag for document.
 */
$wpdb->query(
	$wpdb->prepare(
		'insert into `kkm_doc_tags` (`docid`, `tid`) values (%d, %d)',
		$doc_id,
		$doc_format
	)
);


//Redirect to next stage
$absolute_url = site_url('kkm/upload-step2/'.$doc_id);
wp_redirect($absolute_url);//Temporal redirect
?>