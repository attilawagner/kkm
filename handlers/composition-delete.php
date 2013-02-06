<?php
/**
 * kkm
 * Composition deletion handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$comp_id = $_POST['comp'];
$deletion_confirmed = isset($_POST['delete_composition']);

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
if (empty($comp)) {
	kkm::redirect_to_error_page();
}

/*
 * Check whether the deletion has been cancelled.
 */
if ($deletion_confirmed == false) {
	//Cancelled, redirect to composition page
	$absolute_url = site_url('kkm/composition/'.$comp_id);
	wp_redirect($absolute_url);//Temporal redirect
	die();
}

/*
 * Load and delete documents.
 * Needs to be done separately since the files must be deleted too.
 * IDs are in descending order, so every document can be deleted even those that were
 * referenced from converted or newer versions.
 */
$documents = $wpdb->get_results(
	$wpdb->prepare(
		'select * from `kkm_documents` where `compid`=%d order by `docid` desc;',
		$comp_id
	),
	ARRAY_A
);

$error_occurred = false;

if (!empty($documents)) {
	foreach ($documents as $doc) {
		if (!kkm_files::delete_document($doc['docid'], true)) {
			$error_occurred = true;
			break;
		}
	}
}

if ($error_occurred) {
	kkm::redirect_to_error_page();
}

/*
 * Delete compositions.
 */
$wpdb->query(
	$wpdb->prepare(
		'delete from `kkm_compositions` where `compid`=%d limit 1;',
		$comp_id
	)
);

//Redirect main page
$absolute_url = site_url('kkm/');
wp_redirect($absolute_url);//Temporal redirect
?>