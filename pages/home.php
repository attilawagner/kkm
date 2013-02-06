<?php
/**
 * kkm
 * Home - List of compositions in alphabetical order.
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;
$has_permission = (kkm_user::get_rights() >= kkm_user::RIGHT_READ);
$user_can_edit = (kkm_user::get_rights() >= kkm_user::RIGHT_WRITE);

if (!$has_permission) {
	echo('<div class="kkm_error_message">'.__('You don\'t have permissions to view this page.', 'kkm').'</div>');
} else {
	$compositions = $wpdb->get_results(
		'select * from `kkm_compositions` order by `title`;',
		ARRAY_A
	);
	
	if (empty($compositions)) {
		echo('<div class="kkm_error_message">'.__('There are no compositions in the database.', 'kkm').'</div>');
	} else {
		
		/*
		 * List compositions by name
		 */
		$last_initial = '';
		$is_list_open = false;
		foreach ($compositions as $comp) {
			
			/*
			 * Sanitize initial and write heading for new initials
			 */
			$initial = strtoupper(substr($comp['title'], 0, 1));
			if (is_numeric($initial)) {
				$initial = '123';
			} elseif (!preg_match('/[A-Z]/', $initial)) {
				$initial = 'Other';
			}
			if ($initial != $last_initial) {
				//Close previous list if there was one
				if ($is_list_open) {
					echo('</ul>');
				}
				
				echo('<h2 id="'.$initial.'" title="'.sprintf(__('Compositions starting with %s', 'kkm'), $initial).'">'.$initial.'</h2>');
				$last_initial = $initial;
				
				echo('<ul class="kkm_composition_list">');
				$is_list_open = true;
			}
			
			//Echo composition title as list item
			$url = site_url('kkm/composition/'.$comp['compid']);
			$action_links = '';
			if($user_can_edit) {
				$action_links = ' - ';
				$edit_url = site_url('kkm/edit-composition/'.$comp['compid']);
				$delete_url = site_url('kkm/delete-composition/'.$comp['compid']);
				$action_links .= '<a href="'.$edit_url.'">'.__('Edit','kkm').'</a> ';
				$action_links .= '<a href="'.$delete_url.'">'.__('Delete','kkm').'</a> ';
			}
			echo('<li><a href="'.$url.'">'.$comp['title'].'</a>'.$action_links.'</li>');
		}
		
		//Close last list
		echo('</ul>');
	}
	
	//New composition link
	$new_url = site_url('kkm/edit-composition/new');
	echo('<a href="'.$new_url.'">'.__('Add new composition','kkm').'</a>');
}
?>