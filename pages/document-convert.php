<?php
/**
 * kkm
 * Convert document page
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$doc_id = $page_params[1];
$converter_class = (isset($page_params[2]) ? $page_params[2] : null);

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
	$document = $wpdb->get_row(
		$wpdb->prepare(
			'select `d`.*, `c`.`title` as `comptitle`, `u`.`display_name` as `username`, `t`.`name` as `doctype`
			from `kkm_documents` as `d`
			left join `kkm_compositions` as `c`
				on (`c`.`compid`=`d`.`compid`)
			left join `'.$wpdb->users.'` as `u`
				on (`u`.`ID`=`d`.`uploader`)
			left join `kkm_doc_tags` as `dt`
		        on (`dt`.`docid`=`d`.`docid`)
		    left join `kkm_tags` as `t`
		        on (`t`.`tid`=`dt`.`tid`)
			where `d`.`docid`=%d and `d`.`deleted`=0 and `t`.`tcid`=%d limit 1;',
			$doc_id,
			kkm_tags::TYPE_FORMAT
		),
		ARRAY_A
	);
	
	if (empty($document)) {
		echo('<div class="kkm_error_message">'.__('Document not found in the database.', 'kkm').'</div>');
	} else {
		
		/* translators: First string is the composition title, the second is the document filename. Used as the title for the page. */
		$title = sprintf(__('Convert document: %2$s (%1$s)','kkm'), $document['comptitle'], $document['filename']);
		echo('<h1 class="kkm_document_title">'.$title.'</h1>');
		
		$converters = kkm_modules::get_converters($document['doctype']);
		
		if (empty($converter_class)) {
			//First step, render the list of usable converters
			kkm_convert_render_converter_list($doc_id, $converters);
		} else {
			
			//Validate converter
			if (!in_array($converter_class, $converters)) {
				echo('<div class="kkm_error_message">'.__('Specified converter cannot be found.', 'kkm').'</div>');
			} else {
				
				//Display options form
				$doc_path = WP_CONTENT_DIR.'/'.kkm_files::get_document_path($doc_id, $document['filename']);
				kkm_options::render_options_form($doc_id, $converter_class, $doc_path, 'document-convert');
			}
			
		}
	}
}

/**
 * Renders the list of usable converters.
 */
function kkm_convert_render_converter_list($doc_id, $converters) {
	if (empty($converters)) {
		echo('<div class="kkm_error_message">'.__('There\'s no converter that supports this document type.', 'kkm').'</div>');
	} else {
		_e('Choose a converter:', 'kkm');
		echo('<ul class="kkm_converter_list">');
		foreach ($converters as $converter) {
			$url = site_url('kkm/convert-document/'.$doc_id.'/'.$converter);
			$module_info = kkm_modules::get_module_name($converter);
			echo('<li><a href="'.$url.'">'.$module_info['name'].'</a><br/>'.$module_info['description'].'</li>');
		}
		echo('</ul>');
	}
}
?>