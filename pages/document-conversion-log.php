<?php
/**
 * kkm
 * Document conversion - log page
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$log_id = $page_params[1];

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
	$log = $wpdb->get_row(
		$wpdb->prepare(
			'select * from `kkm_conversion_logs` where `logid`=%d limit 1;',
			$log_id
		),
		ARRAY_A
	);
	if (empty($log)) {
		kkm::redirect_to_error_page();
	}
	
	$messages = unserialize($log['messages']);
	
	echo('<div id="kkm_converter_feedback" class="kkm_converter_result">');
	if (empty($messages)) {
		echo('<div class="kkm_note_message">'.__('The conversion has been finished.', 'kkm').'</div>');
	} else {
		foreach ($messages as $message) {
			echo('<div class="kkm_'.$message[0].'_message">'.$message[1].'</div>');
		}
	}
	echo('</div>');
	
	$url = site_url('kkm/edit-document/'.$log['target']);
	echo('<a href="'.$url.'">'.__('Assign tags to the document.','kkm').'</a>');
}
?>