<?php
/**
 * kkm
 * AJAX taglist
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);
if (!$has_permission) {
	kkm::redirect_to_error_page();
}

//Get tags from DB
$tags = $wpdb->get_results(
	'select * from `kkm_tags` order by `name` asc;',
	ARRAY_A
);

//Create an associative array with the tag IDs as the keys
$assoc_tags = array();
foreach ($tags as $tag) {
	$tid = $tag['tid'];
	unset($tag['tid']);
	$assoc_tags[$tid] = $tag;
}

//Get tag categories
$cats = $wpdb->get_results(
	'select * from `kkm_tag_categories`;',
	ARRAY_A
);

//Create an associative array with the category IDs as the keys
$assoc_cats = array();
foreach ($cats as $cat) {
	$cid = $cat['tcid'];
	unset($cat['tcid']);
	$assoc_cats[$cid] = $cat;
}

header('Content-type: application/json');
echo(json_encode(
	array(
		'tags'			=> $assoc_tags,
		'categories'	=> $assoc_cats
	)
));
?>