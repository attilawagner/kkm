<?php
/**
 * kkm
 * Search result page.
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);

$search_query = rawurldecode($page_params[0]);

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	if (isset($_GET['tag'])) {
		//Listing by tag ID
		$tag_id = $_GET['tag'];
		$tag = $wpdb->get_row(
			$wpdb->prepare(
				'select `t`.*, `tc`.`color`, `tc`.`name` as `catname` from `kkm_tags` as `t`
				left join `kkm_tag_categories` as `tc`
					on (`tc`.`tcid`=`t`.`tcid`)
				where `t`.`tid`=%d limit 1;',
				$tag_id
			),
			ARRAY_A
		);
		if (empty($tag)) {
			kkm::redirect_to_error_page();
		}
		/* translators: First string is the category name and the second is the tag name. Used as the title for the tag. */
		$title = sprintf(__('%1$s: %2$s','kkm'), $tag['catname'], $tag['name']);
		$tag = kkm_tags::render_tag($tag['name'], $tag['color'], $title, true);
		echo('<h1>'.sprintf(__('Documents and compositions tagged as %s','kkm'), $tag).'</h1>');
		$search_results = kkm_search::get_search_results_by_tag($_GET['tag']);
	} else {
		//Normal search by terms
		echo('<h1>'.sprintf(__('Search results for \'%s\'','kkm'), $search_query).'</h1>');
		$search_results = kkm_search::get_search_results($search_query);
	}
	
	
	/*
	 * List compositions
	 */
	echo('<h2>'.__('Compositions','kkm').'</h2>');
	if (!empty($search_results['compositions'])) {
		echo('<ul class="kkm_composition_list">');
		foreach ($search_results['compositions'] as $composition) {
			$url = site_url('kkm/composition/'.$composition['compid']);
			echo('<li><a href="'.$url.'">'.$composition['title'].'</a></li>');
		}
		echo('</ul>');
	}
	
	/*
	 * Load compositions for the documents.
	 */
	$doc_comp_ids = array();
	$compositions = array();
	if (!empty($search_results['documents'])) {
		foreach ($search_results['documents'] as $document) {
			$doc_comp_ids[] = $document['compid'];
		}
		$doc_compositions = $wpdb->get_results(
			'select * from `kkm_compositions` where `compid` in ('.implode(',', $doc_comp_ids).');',
			ARRAY_A
		);
		//Build associative array
		foreach ($doc_compositions as $comp) {
			$compositions[$comp['compid']] = $comp;
		}
	}
	
	/*
	 * List documents
	 * Every document is listed in pair with its composition
	 */
	echo('<h2>'.__('Documents','kkm').'</h2>');
	if (!empty($search_results['documents'])) {
		echo('<ul class="kkm_composition_list">');
		foreach ($search_results['documents'] as $document) {
			$composition = $compositions[$document['compid']];
			$url_comp = site_url('kkm/composition/'.$composition['compid']);
			$url_doc = site_url('kkm/document/'.$document['docid']); 
			echo('<li><a href="'.$url_comp.'">'.$composition['title'].'</a> - <a href="'.$url_doc.'">'.$document['filename'].'</a></li>');
		}
		echo('</ul>');
	}
	
}
?>