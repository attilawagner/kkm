<?php
/**
 * kkm
 * Document page
 * List of tags and versions
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);
$user_can_edit = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
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
		$comp_url = site_url('kkm/composition/'.$document['compid']);
		$comp_link = '<a href="'.$comp_url.'">'.$document['comptitle'].'</a>';
		/* translators: First string is the composition title (link), the second is the document filename. Used as the title for the page. */
		$title = sprintf(
			__('%1$s: %2$s','kkm'),
			$comp_link,
			$document['filename']
		);
		echo('<h1 class="kkm_document_title">'.$title.'</h1>');
		
		$date = mysql2date('j F, Y @ G:i', $document['date']);
		/* translators: First string is the display name of the uploader, the second is the upload date. */
		$subtitle = sprintf(__('Uploaded by %1$s on %2$s.','kkm'), $document['username'], $date);
		echo('<div class="kkm_document_subtitle">'.$subtitle.'</div>');
		
		/*
		 * Show origin
		 */
		if (!empty($document['origin'])) {
			$origin = wptexturize($document['origin']);
			$origin = preg_replace('/\b(?:http|https|ftp|mailto):[^\s]+/', '<a href="$0">$0</a>', $origin);
			$origin = sprintf(__('Origin: %s','kkm'), $origin);
			echo('<div class="kkm_document_subtitle">'.$origin.'</div>');
		}
		
		/*
		 * Display download link
		 */
		$down_url = site_url('kkm/download/'.$doc_id.'-'.$document['filename']);
		echo('<p><a href="'.$down_url.'">'.__('Download','kkm').'</a></p>');
		
		/*
		 * List assiciated tags
		 */
		echo('<div class="kkm_tagpool">');
		kkm_tags::render_tag_table_for_document($doc_id);
		echo('</div>');
		
		/*
		 * Show edit and delete link
		 */
		if ($user_can_edit) {
			$edit_url = site_url('kkm/edit-document/'.$doc_id);
			$delete_url = site_url('kkm/delete-document/'.$doc_id);
			echo('<a href="'.$edit_url.'">'.__('Edit document','kkm').'</a> ');
			echo('<a href="'.$delete_url.'">'.__('Delete document','kkm').'</a> ');
		}
		
		/*
		 * Show conversion link
		 */
		if ($user_can_edit) {
			$url = site_url('kkm/convert-document/'.$doc_id);
			echo('<a href="'.$url.'">'.__('Convert this document','kkm').'</a>');
		}
		
		/*
		 * List other versions.
		 */
		$docs = $wpdb->get_results(
			$wpdb->prepare(
				'select * from `kkm_documents`
				where `firstver`=%d and `complete`=1 and `docid`!=%d
				order by `date`;',
				$document['firstver'],
				$doc_id
			),
			ARRAY_A
		);
		
		echo('<h2 id="documents">'.__('Other versions').'</h2>');
		if (empty($docs)) {
			echo('<div class="kkm_note_message">'.__('This is the only version of this document.', 'kkm').'</div>');
		} else {
			
			if ($document['activever']) {
				echo('<div class="kkm_note_message">'.__('This is the active version of this document.', 'kkm').'</div>');
			} else {
				echo('<div class="kkm_note_message">'.__('This is an inactive version of this document.', 'kkm').'</div>');
				
				if ($user_can_edit) {
					$url = site_url('kkm/make-document-active/'.$doc_id);
					echo('<a href="'.$url.'">'.__('Make this the active version','kkm').'</a>');
				}
			}
			
			echo('<ul class="kkm_document_list">');
			foreach ($docs as $doc) {
				$url = site_url('kkm/document/'.$doc['docid']);
				$date = mysql2date('j F, Y @ G:i', $doc['date']);
				$datetime = mysql2date('Y-m-d\TH:i', $doc['date'], false);
				if (!$doc['deleted']) {
					echo('<li><a href="'.$url.'">'.$doc['filename'].'</a> <time datetitme="'.$datetime.'">('.$date.')</time></li>');
				} else {
					echo('<li class="deleted" title="'.__('Deleted document','kkm').'">'.$doc['filename'].' <time datetitme="'.$datetime.'">('.$date.')</time></li>');
				}
				
			}
			echo('</ul>');
			
		}
		
		//Upload link
		if ($user_can_edit) {
			$url = site_url('kkm/upload/'.$document['compid'].'/'.$doc_id);
			$title = __('Upload a new document for this composition','kkm');
			echo('<a href="'.$url.'" title="'.$title.'">'.__('Upload new version','kkm').'</a>');
		}
	}
}
?>