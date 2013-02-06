<?php
/**
 * kkm
 * Edit composition page
 * Modify title and edit tags
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);
$comp_id = $page_params[0];

//Called for creating a new composition?
$create_new = ($comp_id == 'new');

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	
	if (!$create_new) {
		$composition = $wpdb->get_row(
			$wpdb->prepare(
				'select * from `kkm_compositions` where `compid`=%d;',
				$comp_id
			),
			ARRAY_A
		);
	} else {
		$composition = array('title' => '');
		$comp_id = 0;
	}
	
	if (empty($composition)) {
		echo('<div class="kkm_error_message">'.__('Composition not found in the database.', 'kkm').'</div>');
	} else {
		
		echo('<h1>'.__('Edit composition','kkm').'</h1>');
		
		$title = $composition['title'];
		
		echo('<form action="'.site_url('kkm/').'" method="post">');
		if ($create_new) {
			echo('<input type="hidden" name="action" value="composition-add" />');
		} else {
			echo('<input type="hidden" name="action" value="composition-edit" />');
			echo('<input type="hidden" name="comp" value="'.$comp_id.'" />');
		}
		echo('<p>'.__('Title:', 'kkm').' <input type="text" name="title" value="'.$title.'" /></p>');
		echo kkm_tags::render_tagging_box_for_comp($comp_id, 'taglist');
		
		echo('<input type="submit" name="sent" value="'.__('Save','kkm').'" />');
		echo('</form>');
		
		if (!$create_new) {
			$delete_url = site_url('kkm/delete-composition/'.$comp_id);
			echo('<a href="'.$delete_url.'">'.__('Delete this composition','kkm').'</a>');
		}
	}
}
?>