<?php
/**
 * kkm
 * Upload - stage 2 handler
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

/*
 * Load document from the DB.
 */
$doc_id = $_POST['doc'];
$doc = $wpdb->get_row(
	$wpdb->prepare(
		'
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
		',
		kkm_tags::TYPE_FORMAT,
		$doc_id
	),
	ARRAY_A
);

//Check uploader and document state (missing file also genereates the same error)
if (empty($doc) || $doc['uploader'] != $current_user->ID || $doc['complete'] == 1) {
	kkm::redirect_to_error_page();
}


/*
 * Get tags and save them
 */
$taglist = $_POST['taglist'];
kkm_tags::save_tags_for_doc($doc_id, $taglist);

/*
 * Save origin
 */
$wpdb->query(
	$wpdb->prepare(
		'update `kkm_documents` set `origin`=%s where `docid`=%d;',
		$_POST['origin'],
		$doc_id
	)
);

/*
 * Write meta back into the file
 */
//Load saved tags from the database
$tags = $wpdb->query(
	$wpdb->prepare(
		'select `t`.`name` as `tagname`, `tc`.`name` as `catname`
		from `kkm_tags` as `t`
		left join `kkm_tag_categories` as `tc`
			on (`tc`.`tcid`=`t`.`tcid`)
		left join `kkm_doc_tags` as `dt`
			on (`dt`.`tid`=`t`.`tid`)
		where `dt`.`docid`=%d;',
		$doc_id
	),
	ARRAY_A
);
//Build associative array
$meta = array();
foreach ($tags as $tag) {
	list ($tagname, $catname) = $tag;
	if (!array_key_exists($catname, meta)) {
		$meta[$catname] = array();
	}
	$meta[$catname][] = $tagname;
}
//Call save method
$doc_path = WP_CONTENT_DIR.'/'.kkm_files::get_document_path($doc_id, $doc['filename']);
$parsers = kkm_modules::get_parsers($doc['doctype']);
if (!empty($parsers)) {
	kkm_modules::save_metadata($doc_path, $parsers, $meta);
}

/*
 * Finalize document in DB
 */
$wpdb->query(
	$wpdb->prepare(
		'update `kkm_documents` set `complete`=1 where `docid`=%d limit 1;',
		$doc_id
	)
);


//Redirect to the composition
$absolute_url = site_url('kkm/composition/'.$doc['compid']);
wp_redirect($absolute_url);//Temporal redirect
?>