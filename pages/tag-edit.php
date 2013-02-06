<?php
/**
 * kkm
 * Edit tag page
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
		echo('<div class="kkm_error_message">'.__('Tag not found in the database.', 'kkm').'</div>');
	} else {
		echo('<h1>'.__('Edit tag','kkm').'</h1>');
		
		echo('<form id="kkm_edit_tag" action="'.site_url('kkm/').'" method="post">');
		echo('<input type="hidden" name="action" value="tag-edit" />');
		echo('<input type="hidden" name="tag" value="'.$tag_id.'" />');
		
		echo('<table>');
		echo('<tfoot><tr><td colspan="2"><input type="submit" name="save" value="'.__('Save','kkm').'" /></td></tr></tfoot>');
		echo('<tbody>');
		echo('<tr><th>'.__('Name:', 'kkm').'</th><td><input type="text" name="kkm_tag_name" value="'.$tag['name'].'"></td></tr>');
		echo('<tr><th>'.__('Parent tag:', 'kkm').'</th><td>'.kkm_tags::render_tag_list($tag['tcid'], false, 'kkm_tag_parent', '', $tag['parent'], true, true).'</td></tr>');
		echo('</tbody>');
		echo('</table>');
		
		echo('</form>');
	}
}
?>