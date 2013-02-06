<?php
/**
 * kkm
 * Upload - stage 2
 * Preview and meta modification
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You do not have permissions to upload a document.', 'kkm').'</div>');
} else {
	
	$doc_id = $page_params[0];
	$doc = $wpdb->get_row(
		$wpdb->prepare(
			"
			select
			    `d`.*, `t`.`name` as `doctype`
			from
			    `kkm_documents` as `d`
			    left join `kkm_doc_tags` as `dt`
			        on (`dt`.`docid`=`d`.`docid`)
			    left join `kkm_tags` as `t`
			        on (`t`.`tid`=`dt`.`tid`)
			where
			    `t`.`tcid`=%d and `d`.`docid`=%d and `d`.`deleted`=0
			",
			kkm_tags::TYPE_FORMAT,
			$doc_id
		),
		ARRAY_A
	);
	
	//Check uploader and document state (missing file also genereates the same error)
	if (empty($doc) || $doc['uploader'] != $current_user->ID || $doc['complete'] == 1) {
		kkm::redirect_to_error_page();
	}
	
	$doc_path = WP_CONTENT_DIR.'/'.kkm_files::get_document_path($doc_id, $doc['filename']);
	$parsers = kkm_modules::get_parsers($doc['doctype']);
	if (empty($parsers)) {
		//Unsupported document format, show only tagging
		kkm_ul2_tagging_and_source($doc_id, $parsers, $doc_path);
		
	} else {
		//Call parser(s) to validate and to make a preview.
		if (kkm_ul2_validate($parsers, $doc_path)) {
			kkm_ul2_preview($parsers, $doc_path);
			kkm_ul2_tagging_and_source($doc_id, $parsers, $doc_path);
		} else {
			//A validator returned an error, delete the document
			kkm_files::delete_document($doc_id);
		}
	}
}

/**
 * Calls the validators and prints the result
 * @return boolean True if there was no error.
 */
function kkm_ul2_validate($parsers, $doc_path) {
	$results = kkm_modules::validate_document($doc_path, $parsers);
	if ($results == null) {
		return true; //Do not terminate upload process because of missing validators.
	} else {
		$has_error = false;
		//Print the results
		echo('<h3>'.__('Validation results:','kkm').'</h3>');
		echo('<div class="kkm_validation_area">');
		foreach ($results as $result) {
			echo('<div class="kkm_'.$result[0].'_message">'.$result[1].'</div>');
			if ($result[0] == 'error') {
				$has_error = true;
			}
		}
		echo('</div>');
			
		return !$has_error; //If there was no error, only then we want to continue.
	}
}

/**
 * Renders preview box
 */
function kkm_ul2_preview($parsers, $doc_path) {
	$preview = kkm_modules::get_preview($doc_path, $parsers);
	
	echo('<h3>'.__('Preview:','kkm').'</h3>');
	echo('<div class="kkm_preview_area">');
	if (empty($preview)) {
		echo('<div class="kkm_note_message">'.__('Preview could not be generated.', 'kkm').'</div>');
	} else {
		echo($preview);
	}
	echo('</div>');
}

/**
 * Renders the tagging interface
 * Metadata is loaded from the document and tag suggestions are generated based on them.
 */
function kkm_ul2_tagging_and_source($doc_id, $parsers, $doc_path) {
	//Get meta
	$meta = kkm_modules::get_metadata($doc_path, $parsers);
	
	//Convert metadata into (category name - tag name) pairs
	$suggested_tags = array();
	foreach ($meta as $key => $values) {
		foreach ($values as $value) {
			$suggested_tags[] = array($key, $value);
		}
	}
	
	echo('<form id="upload2_form" action="'.site_url('kkm/').'" method="post">');
	echo('<input type="hidden" name="action" value="upload2" />');
	echo('<input type="hidden" name="doc" value="'.$doc_id.'" />');
	
	echo('<h3>'.__('Tags:','kkm').'</h3>');
	//call common function from lib
	echo kkm_tags::render_tagging_box_for_doc($doc_id, 'taglist', $suggested_tags);
	
	echo('<h3>'.__('Origins :','kkm').'</h3>');
	echo('<div><textarea name="origin"></textarea></div>');
	
	echo('<div><input type="submit" name="sent" value="'.__('Save','kkm').'" /></div>');
	echo('</form>');
}
?>