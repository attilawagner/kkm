<?php
/**
 * kkm
 * Composition page
 * List of tags and attached documents
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);
$user_can_edit = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$comp_id = $page_params[0];

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
	$composition = $wpdb->get_row(
		$wpdb->prepare(
			'select * from `kkm_compositions` where `compid`=%d;',
			$comp_id
		),
		ARRAY_A
	);
	
	if (empty($composition)) {
		echo('<div class="kkm_error_message">'.__('Composition not found in the database.', 'kkm').'</div>');
	} else {
		echo('<h1 class="kkm_composition_title">'.$composition['title'].'</h1>');
		if ($user_can_edit) {
			$edit_url = site_url('kkm/edit-composition/'.$comp_id);
			echo('<a href="'.$edit_url.'">'.__('Edit composition','kkm').'</a>');
		}
		
		
		/*
		 * List assiciated tags
		 */
		
		echo('<div class="kkm_tagpool">');
		kkm_tags::render_tag_table_for_composition($comp_id);
		echo('</div>');
		
		/*
		 * List attached documents.
		 */
		$docs = $wpdb->get_results(
			$wpdb->prepare(
				'select * from `kkm_documents`
				where `compid`=%d and `complete`=1 and `activever`=1 and `deleted`=0
				order by `date`;',
				$comp_id
			),
			ARRAY_A
		);
		
		echo('<h2 id="documents">'.__('Documents').'</h2>');
		if (empty($docs)) {
			echo('<div class="kkm_note_message">'.__('The composition does not have any attached documents.', 'kkm').'</div>');
		} else {
			
			echo('<ul class="kkm_document_list">');
			foreach ($docs as $doc) {
				$url = site_url('kkm/document/'.$doc['docid']);
				$date = mysql2date('j F, Y @ G:i', $doc['date']);
				$datetime = mysql2date('Y-m-d\TH:i', $doc['date'], false);
				
				$action_links = '';
				if($user_can_edit) {
					$action_links = ' - ';
					$edit_url = site_url('kkm/edit-document/'.$doc['docid']);
					$delete_url = site_url('kkm/delete-document/'.$doc['docid']);
					$convert_url = site_url('kkm/convert-document/'.$doc['docid']);
					$action_links .= '<a href="'.$edit_url.'">'.__('Edit','kkm').'</a> ';
					$action_links .= '<a href="'.$delete_url.'">'.__('Delete','kkm').'</a> ';
					$action_links .= '<a href="'.$convert_url.'">'.__('Convert','kkm').'</a> ';
				}
				
				echo('<li><a href="'.$url.'">'.$doc['filename'].'</a> <time datetitme="'.$datetime.'">('.$date.')</time>'.$action_links.'</li>');
			}
			echo('</ul>');
			
		}
		
		//Upload link
		if ($user_can_edit) {
			$url = site_url('kkm/upload/'.$comp_id);
			$title = __('Upload a new document for this composition','kkm');
			echo('<a href="'.$url.'" title="'.$title.'">'.__('Upload new document','kkm').'</a>');
		}
	}
}
?>