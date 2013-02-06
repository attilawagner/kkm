<?php
/**
 * kkm
 * Taglist - List of every tag grouped by category name.
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);
global $user_can_edit;
$user_can_edit = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	$tags = $wpdb->get_results(
		'select `t`.*, `tc`.`name` as `catname`, `tc`.`color`
			from `kkm_tags` as `t`
			left join `kkm_tag_categories` as `tc`
				on (`tc`.`tcid`=`t`.`tcid`)
			order by `catname` asc, `t`.`name` asc;',
		ARRAY_A
	);
	
	/*
	 * Build associative list.
	 */
	global $tags_by_parent;
	$tags_by_parent = array();
	foreach ($tags as $tag) {
		$parent = ($tag['parent'] == null ? 0 : $tag['parent']);
		if (!isset($tags_by_parent[$parent])) {
			$tags_by_parent[$parent] = array();
		}
		$tags_by_parent[$parent][] = $tag;
	}
	
	echo('<h1>'.__('List of available tags','kkm').'</h1>');
	
	$prev_cat = 0;
	foreach ($tags_by_parent[0] as $tag) {
		if ($tag['tcid'] != $prev_cat) {
			echo('<h2 title="'.sprintf(__('Tags in the \'%s\' category', 'kkm'), $tag['catname']).'">'.$tag['catname'].'</h2>');
			$prev_cat = $tag['tcid'];
		
			kkm_taglist_render_sublist(0, $tag['tcid']);
		}
	}
}

/**
 * Renders a sublist of the categories that belongs to the given parent tag.
 *
 * @param integer $parent Parent tag ID.
 * @param integer $tcid Only tags with this category ID will be listed.
 */
function kkm_taglist_render_sublist($parent, $tcid) {
	global $tags_by_parent, $user_can_edit;
	if (empty($tags_by_parent[$parent])) {
		return;
	}

	echo('<ul class="kkm_composition_list">');
	foreach ($tags_by_parent[$parent] as $tag) {
		if ($tag['tcid'] == $tcid) {
			$url = site_url('kkm/search/?tag='.$tag['tid']);
			$rendered_tag = kkm_tags::render_tag($tag['name'], $tag['color'], __('Click to list documents and compositions that have this tag.', 'kkm'), true);
			
			$action_links = '';
			if ($user_can_edit) {
				$action_links .= ' - ';
				$edit_url = site_url('kkm/edit-tag/'.$tag['tid']);
				$delete_url = site_url('kkm/delete-tag/'.$tag['tid']);
				$action_links .= '<a href="'.$edit_url.'">'.__('Edit', 'kkm').'</a> ';
				$action_links .= '<a href="'.$delete_url.'">'.__('Delete', 'kkm').'</a> ';
			}
			
			echo('<li><a href="'.$url.'">'.$rendered_tag.'</a>'.$action_links.'</li>');
			kkm_taglist_render_sublist($tag['tid'], $tcid);
		}
	}
	echo('</ul>');
}
?>