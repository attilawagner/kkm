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
$filename = $_POST['filename'];

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
$doc_path = WP_CONTENT_DIR.'/'.kkm_files::get_document_path($doc_id, $document['filename']);

/*
 * Load module
 */
$converters = kkm_modules::get_converters();
if (!in_array($converter, $converters)) {
	//Invalid converter name
	kkm::redirect_to_error_page();
}

$messages = kkm_modules::validate_options($converter, $doc_path, $options);

$filename = kkm_files::get_safe_name(trim($filename));
if (empty($filename)) {
	$messages[] = array('error', __('You must specify a filename.','kkm'));
}

if (empty($messages)) {
	echo('ok');
} else {
	foreach ($messages as $message) {
		echo('<div class="kkm_'.$message[0].'_message">'.$message[1].'</div>');
	}
}
?>