<?php
/**
 * kkm
 * Delete document page
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$doc_id = $page_params[1];

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
	$document = $wpdb->get_row(
		$wpdb->prepare(
			'select `d`.*, `c`.`title` as `comptitle`, `u`.`display_name` as `username`
			from `kkm_documents` as `d`
			left join `kkm_compositions` as `c`
				on (`c`.`compid`=`d`.`compid`)
			left join `'.$wpdb->users.'` as `u`
				on (`u`.`ID`=`d`.`uploader`)
			where `d`.`docid`=%d and `d`.`deleted`=0 limit 1;',
			$doc_id
		),
		ARRAY_A
	);
	
	if (empty($document)) {
		echo('<div class="kkm_error_message">'.__('Document not found in the database.', 'kkm').'</div>');
	} else {
		
		$date = mysql2date('j F, Y @ G:i', $document['date']);
		/* translators: Parameters: filename, username, date */
		echo('<h2>'.sprintf(__('Delete document: %1$s (uploaded by %2$s on %3s)','kkm'), $document['filename'], $document['username'], $date).'<h2>');
		
		$message = __('Do you really want to delete this document? <br/> Other versions of this document and any document that was generated from this one will not be deleted.','kkm');
		
		echo('<form action="'.site_url('kkm/').'" method="post">');
		echo('<input type="hidden" name="action" value="document-delete" />');
		echo('<input type="hidden" name="doc" value="'.$doc_id.'" />');
		
		echo('<p>'.$message.'</p>');
		
		echo('<input type="submit" name="delete_document" value="'.__('Delete','kkm').'" />');
		echo('<input type="submit" name="cancel" value="'.__('Cancel','kkm').'" />');
		echo('</form>');
	}
}
?>