<?php
/**
 * kkm
 * Document deletion handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$doc_id = $_POST['doc'];
$deletion_confirmed = isset($_POST['delete_document']);

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
 * Check whether the deletion has been cancelled.
 */
if ($deletion_confirmed == false) {
	//Cancelled, redirect to document page
	$absolute_url = site_url('kkm/document/'.$doc_id);
	wp_redirect($absolute_url);//Temporal redirect
	die();
}

/*
 * Delete document
 */
if (!kkm_files::delete_document($doc_id)) {
	kkm::redirect_to_error_page();
}

/*
 * Set latest document version as active.
 */
$latest_id = $wpdb->get_var(
	$wpdb->prepare(
		'select `docid` from `kkm_documents` where `firstver`=%d and `deleted`=0 order by `date` desc limit 1;',
		$document['firstver']
	)
);
if (!empty($latest_id)) {
	$wpdb->query(
		$wpdb->prepare(
			'update `kkm_documents` set `activever`=1 where `docid`=%d limit 1;',
			$latest_id
		)
	);
}

//Redirect to composition page
$absolute_url = site_url('kkm/composition/'.$document['compid']);
wp_redirect($absolute_url);//Temporal redirect
?>