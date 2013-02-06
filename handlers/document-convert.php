<?php
/**
 * kkm
 * AJAX call to validate conversion options
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

$doc_id = $_POST['doc'];
$converter = $_POST['module'];
$options = $_POST['kkm_option'];
$target_filename = $_POST['filename'];

/*
 * Load document
 */
$document = $wpdb->get_row(
	$wpdb->prepare(
		'select * from `kkm_documents` where `docid`=%d and `deleted`=0 limit 1;',
		$doc_id
	),
	ARRAY_A
);
if (empty($document)) {
	//Invalid document ID
	kkm::redirect_to_error_page();
}
$source_path = WP_CONTENT_DIR.'/'.kkm_files::get_document_path($doc_id, $document['filename']);

/*
 * Load module
 */
$converters = kkm_modules::get_converters();
if (!in_array($converter, $converters)) {
	//Invalid converter name
	kkm::redirect_to_error_page();
}
$messages = kkm_modules::validate_options($converter, $source_path, $options);
if (!empty($messages)) {
	kkm::redirect_to_error_page();
}

/*
 * Sanitize filename and generate target document.
 */
$target_filename = kkm_files::get_safe_name(trim($target_filename));
if (empty($target_filename)) {
	kkm::redirect_to_error_page();
}
//Append extension
$target_filename .= '.'.kkm_modules::get_target_extension($converter);

//Insert database record
$wpdb->query(
	$wpdb->prepare(
		'insert into `kkm_documents` (`generated`, `uploader`, `date`, `compid`, `filename`, `filesize`,`source`) values (1, %d, %s, %d, %s, %d, %d);',
		$current_user->ID,
		current_time('mysql'),
		$document['compid'],
		$target_filename,
		0, //Set the size to 0 temporarily
		$doc_id
	)
);
$new_doc_id = $wpdb->insert_id;
if (empty($new_doc_id)) {
	kkm::redirect_to_error_page();
}

//Generate full path for new document
$target_path = WP_CONTENT_DIR.'/'.kkm_files::create_document_path($new_doc_id, $target_filename);

//Call converter
$log_id = kkm_modules::convert_document($converter, $doc_id, $source_path, $new_doc_id, $target_path, $options, $current_user->ID);

//Redirect to log page
$url = site_url('kkm/show-log/'.$log_id);
wp_redirect($url);//Temporal redirect
?>