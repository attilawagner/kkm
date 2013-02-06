<?php
/**
 * kkm
 * Upload - stage 1
 * File selection and uploading
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$comp_id = $page_params[1];
$prev_doc_id = (isset($page_params[2]) ? $page_params[2] : 0); //Document ID of the previus version of the document. Given when uploading a new version.

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You do not have permissions to upload a document.', 'kkm').'</div>');
} else {
	
	$comp = $wpdb->get_row(
		$wpdb->prepare(
			'select * from `kkm_compositions` where `compid`=%d limit 1;',
			$comp_id
		),
		ARRAY_A
	);
	
	if (empty($comp)) {
		kkm::redirect_to_error_page();
	}
	
	if (!empty($prev_doc_id)) {
		$prev_doc = $wpdb->get_row(
			$wpdb->prepare(
				'select * from `kkm_documents` where `docid`=%d and `deleted`=0 limit 1;',
				$prev_doc_id
			),
			ARRAY_A
		);
		
		if (empty($prev_doc)) {
			echo('<div class="kkm_error_message">'.__('Cannot find the previus document in the database. Uploaded document will be treated as new.', 'kkm').'</div>');
			$prev_doc_id = 0;
		} else {
			//Load format tag.
			$format_tag = kkm_tags::get_format_tag_for_document($prev_doc_id);
		}
	}
	
	if (empty($prev_doc_id)) {
		/* translators: Parameter is the composition title */
		echo('<h2>'.sprintf(__('Upload a new document for %s','kkm'), $comp['title']).'<h2>');
	} else {
		/* translators: First parameter is the composition title, the second is the filename of the old version */
		echo('<h2>'.sprintf(__('Upload a new version for %1$s (%2$s)','kkm'), $prev_doc['filename'], $comp['title']).'<h2>');
	}
	
?>
<form id="upload1_form" action="<?=site_url('kkm/');?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload1" />
<input type="hidden" name="comp" value="<?=$comp_id?>" />
<input type="hidden" name="prev_doc" value="<?=$prev_doc_id?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?=kkm_files::MAX_DOCUMENT_SIZE?>" />
<table class="kkm_upload" id="upload1">
<tfoot>
	<tr>
		<th colspan="2"><input type="submit" name="upload" value="<?php _e('Upload','kkm'); ?>" /></th>
	</tr>
</tfoot>
<tbody>
	<tr>
		<th><?php _e('File','kkm'); ?></th>
		<td><input type="file" name="document" class="document" /></td>
	</tr>
	<tr>
		<th><?php _e('Document Type','kkm'); ?></th>
		<td><?php
			/*
			 * Display format chooser for new documents only,
			 * Render the format tag of the old version for new versions.
			 */
			if (empty($prev_doc_id)) {
				kkm_tags::render_tag_list('Format', false, 'format');
			} else {
				kkm_tags::render_tag($format_tag['name'], $format_tag['color']);
				echo('<input type="hidden" name="format" value="'.$format_tag['tcid'].'" />');
			}
		?></td>
	</tr>
</tbody>
</table>
</form>
<?php } ?>