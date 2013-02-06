<?php
if(!current_user_can('manage_options')) {
	die('Access Denied');
}

$messages = array();

/*
 * Process new category request
 */
if (isset($_POST['kkm_cat_new']) && check_admin_referer('kkm_tagcats_add', 'kkmnonce')) {
	$kkm_cat_id = $_POST['kkm_cat_id'];
	$kkm_cat_name = $_POST['kkm_cat_name'];
	$kkm_cat_comp = $_POST['kkm_cat_comp'];
	$kkm_cat_doc = $_POST['kkm_cat_doc'];
	$kkm_cat_color = $_POST['kkm_cat_color'];
	
	//Check for empty fields
	if (empty($kkm_cat_name) || empty($kkm_cat_color)) {
		$messages[] = array('error', __('Every field is required. Tag category is not saved.','kkm'));
	}
	
	//Check for invalid values
	if ($kkm_cat_comp < 0 || $kkm_cat_comp > 3 || $kkm_cat_doc < 0 || $kkm_cat_doc > 3 || !preg_match('/#?[0-9a-fA-F]{6}/', $kkm_cat_color)) {
		$messages[] = array('error', __('A field contains an invalid value. Tag category is not saved.','kkm'));
	}
	
	//Sanitize color
	preg_match('/#?([0-9a-fA-F]{6})/', $kkm_cat_color, $regs);
	$color = $regs[1];
	
	if (empty($kkm_cat_id)) {
		//Insert into DB
		$wpdb->query(
			$wpdb->prepare(
				'insert into `kkm_tag_categories`
					(`name`, `color`, `mandatory_doc`, `mandatory_comp`)
					values (%s, %s, %d, %d);',
				$kkm_cat_name,
				$color,
				$kkm_cat_doc,
				$kkm_cat_comp
			)
		);
	} else {
		//Update DB
		$wpdb->query(
			$wpdb->prepare(
				'update `kkm_tag_categories`
					set `name`=%s, `color`=%s, `mandatory_doc`=%d, `mandatory_comp`=%d where `tcid`=%d;',
				$kkm_cat_name,
				$color,
				$kkm_cat_doc,
				$kkm_cat_comp,
				$kkm_cat_id
			)
		);
	}
	
}

/*
 * Delete categories
 */
if (isset($_POST['kkm_cat_del'])) {
	$tcids = $_POST['kkm_cat_del_id'];
	$idlist = '';
	//Build a comma separated list while casting each element of the array into integer
	foreach ($tcids as $id) {
		$id = (int)$id;
		if ($id > 0) {
			$idlist .= $id . ',';
		}
	}
	if (strlen($idlist) > 1) {
		$idlist = substr($idlist, 0, -1);
		$wpdb->query('delete from `kkm_tag_categories` where `tcid` in ('.$idlist.');');
	}
	if (!empty($wpdb->last_error)) {
		$messages[] = array('warning', __('Only empty categories has been deleted.','kkm'));
	}
}

/*
 * Load category for editing
 */
if (isset($_GET['editid'])) {
	$edit_cat = $wpdb->get_row(
		$wpdb->prepare(
			'select * from `kkm_tag_categories` where `tcid`=%d limit 1;',
			$_GET['editid']
		),
		ARRAY_A
	);
}

/*
 * Load categories
 */
$categories = $wpdb->get_results(
	'select `tc`.`tcid`, `tc`.`name`, `tc`.`color`, count(`t`.`tid`) as `tcount`, `tc`.`mandatory_doc`, `tc`.`mandatory_comp`
	from `kkm_tag_categories` as `tc`
	left join `kkm_tags` as `t`
		on (`t`.`tcid`=`tc`.`tcid`)
	group by `tc`.`tcid`
	order by `tc`.`name` asc;',
	ARRAY_A
);

/**
 * Renders a &lt;select&gt; tag with the values for the mandatory flag.
 * 
 * @param string $name Name and ID of the tag.
 * @param integer $selected Default value.
 */
function kkm_render_mandatory_select($name, $selected) {
	echo('<select name="'.$name.'" id="'.$name.'">');
	for ($i=0; $i<=3; $i++) {
		$sel_attrib = ($selected == $i ? ' selected="selected"' : '');
		echo('<option value="'.$i.'"'.$sel_attrib.'>'.kkm_tags::get_mandatory_name($i).'</option>');
	}
	echo('</select>');
}
?>

<div class="wrap">
	<div class="icon32 kkmicon"></div>
	<h2><?php _e('Tag Categories', 'kkm'); ?></h2>
	
	<?php 
		foreach ($messages as $message) {
			echo('<div class="kkm_'.$message[0].'_message">'.$message[1].'</div>');
		}
	?>
	
	<form id="kkm_tagcatform" name="kkm_tagcatform" action="<?=admin_url('admin.php?page=kkm/admin/tagcats.php');?>" method="post">
		<?php wp_nonce_field('kkm_tagcats','kkmnonce'); ?>
		<table class="widefat fixed kkm_tagcatlist">
			<thead>
				<tr>
					<th scope="col" class="kkm_tagcatname"><?php _e('Category name'); ?></th>
					<th scope="col" class="kkm_tagmandatory"><?php _e('Composition/Document'); ?></th>
					<th scope="col" class="kkm_tagcount"><?php _e('Tag count'); ?></th>
					<th scope="col" class="kkm_action"><?php _e('Actions'); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" class="kkm_tagcatname"><?php _e('Category name'); ?></th>
					<th scope="col" class="kkm_tagmandatory"><?php _e('Composition/Document'); ?></th>
					<th scope="col" class="kkm_tagcount"><?php _e('Tag count'); ?></th>
					<th scope="col" class="kkm_action"><?php _e('Actions'); ?></th>
				</tr>
			</tfoot>
			<tbody>
				<?php
				if (empty($categories)) {
					echo('<tr><td colspan="4">'.__('There are no tag categories.','kkm').'</td></tr>');
				} else {

					foreach ($categories as $cat) {
						echo('<tr>');
						
						//Render category as a tag belonging to this category
						$tag = kkm_tags::render_tag($cat['name'], $cat['color'], '', true);
						echo('<td>'.$tag.'</td>');
						
						echo('<td>'.kkm_tags::get_mandatory_name($cat['mandatory_comp']).'/'.kkm_tags::get_mandatory_name($cat['mandatory_doc']).'</td>');
						
						echo('<td>'.$cat['tcount'].'</td>');
						
						echo('<td>');
						//Categories with ID<100 are built in, so they can not be deleted.
						if ($cat['tcid'] >= 100) {
							echo('<a href="'.admin_url('admin.php?page=kkm/admin/tagcats.php&amp;editid='.$cat['tcid']).'">'.__('Edit').'</a> ');
							
							//Only empty categories can be deleted.
							$del_disabled = ($cat['tcount'] > 0 ? ' disabled="disabled"' : '');
							echo('<label><input type="checkbox" class="kkm_delcb" name="kkm_cat_del_id[]" value="'.$cat['tcid'].'"'.$del_disabled.' /> '.__('Delete','kkm').'</label>');
						}
						echo('</td></tr>');
					}
					
				}
				?>
			</tbody>
		</table>
		<div class="tablenav bottom">
			<div class="alignright actions">
				<input class="action-secondary button" type="submit" name="kkm_cat_del" value="<?php _e('Delete','kkm'); ?>"/>
			</div>
			<br class="clear" />
		</div>
	</form>

	<?php
	if (!isset($edit_cat)) {
		$title = __('Create new', 'kkm');
		$button_label = __('Create','kkm');
		$current_tcid = '';
		$current_name = '';
		$current_mandatory_doc = 2;
		$current_mandatory_comp = 2;
		$current_color = '';
	} else {
		$title = __('Edit', 'kkm');
		$button_label = __('Save','kkm');
		$current_tcid = $edit_cat['tcid'];
		$current_name = $edit_cat['name'];
		$current_mandatory_doc = $edit_cat['mandatory_doc'];
		$current_mandatory_comp = $edit_cat['mandatory_comp'];
		$current_color = '#'.$edit_cat['color'];
	} 
	?>
	<h3 class="title"><?=$title;?></h3>
	<form id="kkm_addcatform" name="kkm_addcatform" action="<?=admin_url('admin.php?page=kkm/admin/tagcats.php');?>" method="post">
		<?php wp_nonce_field('kkm_tagcats_add','kkmnonce'); ?>
		<input type="hidden" name="kkm_cat_id" value="<?=$current_tcid;?>" />
		<div id="poststuff" class="metabox-holder kkm_metabox">
			<div class="stuffbox">
				<h3><span><?php _e('Create new category', 'kkm'); ?></span></h3>
				<div class="inside">
					<table class="editform">
						<tr>
							<th scope="row"><label for="kkm_cat_name"><?php _e('Category Name', 'kkm'); ?></label></th>
							<td><input type="text" name="kkm_cat_name" id="kkm_cat_name" value="<?=$current_name;?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="kkm_cat_comp"><?php _e('For Compositions', 'kkm'); ?></label></th>
							<td><?php kkm_render_mandatory_select('kkm_cat_comp', $current_mandatory_comp); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="kkm_cat_doc"><?php _e('For Documents', 'kkm'); ?></label></th>
							<td><?php kkm_render_mandatory_select('kkm_cat_doc', $current_mandatory_doc); ?></td>
						</tr>
						<tr>
							<th scope="row"><label for="kkm_cat_color"><?php _e('Color', 'kkm'); ?></label></th>
							<td><input type="text" name="kkm_cat_color" id="kkm_cat_color" maxlength="7" value="<?=$current_color;?>" /></td>
						</tr>
					</table>
					<div id="submitlink" class="submitbox">
						<div id="major-publishing-actions">
							<div id="publishing-action">
								<input type="submit" id="publish" class="button-primary" value="<?=$button_label;?>" name="kkm_cat_new" />
							</div>
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
	</form>

</div>