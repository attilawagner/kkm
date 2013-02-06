<?php
/**
 * kkm
 * Delete composition page
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$comp_id = $page_params[0];

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
	$composition = $wpdb->get_row(
		$wpdb->prepare(
			'select * from `kkm_compositions` where `compid`=%d;',
			$comp_id
		),
		ARRAY_A
	);
	
	if (empty($composition)) {
		echo('<div class="kkm_error_message">'.__('Composition not found in the database.', 'kkm').'</div>');
	} else {
		
		/* translators: Parameter is the composition title */
		echo('<h2>'.sprintf(__('Delete composition: %s','kkm'), $composition['title']).'<h2>');
		
		$message = __('Do you really want to delete this composition and every related data from the database? <br/> Every document that has been uploaded for this composition will be deleted too.','kkm');
		
		echo('<form action="'.site_url('kkm/').'" method="post">');
		echo('<input type="hidden" name="action" value="composition-delete" />');
		echo('<input type="hidden" name="comp" value="'.$comp_id.'" />');
		
		echo('<p>'.$message.'</p>');
		
		echo('<input type="submit" name="delete_composition" value="'.__('Delete','kkm').'" />');
		echo('<input type="submit" name="cancel" value="'.__('Cancel','kkm').'" />');
		echo('</form>');
	}
}
?>