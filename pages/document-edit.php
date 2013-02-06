<?php
/**
 * kkm
 * Edit document page
 * Modify tags
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$doc_id = $page_params[0];

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
		
		echo('<h1>'.__('Edit document','kkm').'</h1>');
		
		/* translators: First string is the composition title, the second is the document filename. Used as the title for the page. */
		$title = sprintf(__('%1$s: %2$s','kkm'), $document['comptitle'], $document['filename']);
		echo('<h1 class="kkm_document_title">'.$title.'</h1>');
		
		echo('<form action="'.site_url('kkm/').'" method="post">');
		echo('<input type="hidden" name="action" value="document-edit" />');
		echo('<input type="hidden" name="doc" value="'.$doc_id.'" />');
		
		echo kkm_tags::render_tagging_box_for_doc($doc_id, 'taglist');
		
		echo('<input type="submit" name="sent" value="'.__('Save','kkm').'" />');
		echo('</form>');
		
		$delete_url = site_url('kkm/delete-document/'.$doc_id);
		echo('<a href="'.$delete_url.'">'.__('Delete this document','kkm').'</a>');
	}
}
?>