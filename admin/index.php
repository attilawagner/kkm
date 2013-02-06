<?php
if(!current_user_can('manage_options')) {
	die('Access Denied');
}
?>

<div class="wrap">
	<div class="icon32 kkmicon"></div>
	<h2><?php _e('General settings', 'kkm'); ?></h2>
<!--
	<form id="qheb_addcatform" name="qheb_addcatform" action="<?=admin_url('admin.php?page=kkm/admin/index.php');?>" method="post">
		<?php wp_nonce_field('qheb_options','qhebnonce'); ?>
		<table class="form-table qheb_form_table">
			<tr>
				<th><label for="qheb_path">Qhebunel path</label></th>
				<td><input type="text" name="qheb_path" id="qheb_path" value="<?=get_option('qhebunel_path');?>" class="regular-text code" /></td>
			</tr>
			<tr>
				<th><label for="qheb_url">Qhebunel URL</label></th>
				<td><input type="text" name="qheb_url" id="qheb_url" value="<?=get_option('qhebunel_url');?>" class="regular-text code" /></td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" value="<?php _e('Save changes', 'kkm') ?>" class="button-primary" id="qheb_submit" name="qheb_submit">
		</p>
	</form>
-->
</div>