<?php
/**
 * kkm
 * Delete composition page
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$tag_id = $page_params[0];

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
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
		echo('<div class="kkm_error_message">'.__('Composition not found in the database.', 'kkm').'</div>');
	} else {
		/* translators: First string is the category name and the second is the tag name. Used as the title for the tag. */
		$title = sprintf(__('%1$s: %2$s','kkm'), $tag['catname'], $tag['name']);
		$tag_in_title = kkm_tags::render_tag($tag['name'], $tag['color'], $title, true);
		echo('<h2>'.sprintf(__('Delete tag: %s','kkm'), $tag_in_title).'<h2>');
		
		$message = __('Do you really want to delete this tag? <br/> It will be removed from every document and composition.','kkm');
		
		echo('<form action="'.site_url('kkm/').'" method="post">');
		echo('<input type="hidden" name="action" value="tag-delete" />');
		echo('<input type="hidden" name="tag" value="'.$tag_id.'" />');
		
		echo('<p>'.$message.'</p>');
		
		echo('<input type="submit" name="delete_tag" value="'.__('Delete','kkm').'" />');
		echo('<input type="submit" name="cancel" value="'.__('Cancel','kkm').'" />');
		echo('</form>');
	}
}
?>