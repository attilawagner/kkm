<?php
/**
 * kkm
 * Bootstrap file
 *
 * This file is called when a HTTP request is made to the kkm system.
 * It calls the specific handler for user input actions (such as posting)
 * and calls the page that should be displayed. It also builds up the WP
 * frame, so header, footer and sidebar should not be called within
 * standalone pages.
 */

if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;

/*
 * Check whether a handler is needed, and include it,
 * or display a page that belong to a normal GET request.
 */
if (isset($_POST['action']) && !empty($_POST['action'])) {
	/*
	 * Handlers - Processed only for POST actions.
	 * A GET request may belong to a special handler instead of a page,
	 * in this case it will be loaded by the URL parser further down.
	 * GET handlers DO NOT modify any data, only POST actions do.
	 */
	
	$action = $_POST['action'];
	$handler = dirname(__FILE__)."/handlers/${action}.php";

	if (is_file($handler)) {
		//Include handler for supported action
		//A redirect will be issued at the end of it.
		require_once($handler);
	} else {
		//Redirect to to main page if action is unsupported
		$absolute_url = site_url('kkm/');
		wp_redirect($absolute_url, 301);//Moved permanently
	}
	die();
	
	
} else {
	/*
	 * A page is rendered only if it's not a modifying user action (eg. posting a comment).
	 * If a handler is finished, it sends a redirect in the end, so a doublepost cannot happen.
	 */
	
	/*
	 * Determine which page should be displayed
	 * An URI can belong to only one section and may contain parameters:
	 * /kkm/taglist
	 * /kkm/composition/82
	 * /kkm/document/475
	 * /kkm/upload
	 * etc.
	 */
	$pos = strpos($_SERVER['REQUEST_URI'], '/kkm/') + 5;//strpos gives back the start, we need the end
	$kkm_uri = substr($_SERVER['REQUEST_URI'], $pos);

	/*
	 * Global Sections
	 * The section names are the keys in the array.
	 * For each section, an array defines the file name that should be included,
	 * and a boolean flag that indicates whether it's a handler or a page.
	 * Format:
	 * 'uri_for_special_page' =>	array('filename', false),
	 * 'uri_for_command' =>			array('filename', true)
	 */
	//TODO
	$global_sections = array(
		//Special pages - no theme header and footer rendered
		'taglist' =>			array('ajax-taglist', true),
		'make-document-active'=>array('document-make-active', true),
		'download' =>			array('download', true),
		
		//Normal pages - wrapped inside the WP theme
		'error' =>				array('error', false),
		'upload' =>				array('upload1', false),
		'upload-step2' =>		array('upload2', false),
		'composition' =>		array('composition', false),
		'edit-composition' =>	array('composition-edit', false),
		'delete-composition' =>	array('composition-delete', false),
		'document' =>			array('document', false),
		'edit-document' =>		array('document-edit', false),
		'delete-document' =>	array('document-delete', false),
		'convert-document' =>	array('document-convert', false),
		'show-log' =>			array('document-conversion-log', false),
		'search' =>				array('search', false),
		'tags' =>				array('tag-list', false),
		'edit-tag' =>			array('tag-edit', false),
		'delete-tag' =>			array('tag-delete', false),
		

		'__test' 			=>	array('__test', false),
	);
	
	
	$pattern = implode('|', array_keys($global_sections));
	if (preg_match("%^(${pattern})(?:/(.*))?$%", $kkm_uri, $regs)) {
		$section_name = $regs[1];
		$page_params = (isset($regs[2]) ? $regs[2] : '');
		
		/*
		 * Make $page_params an array where the 0th value is the whole parameter string,
		 * and the next items are the fields (separated by the / character in the url).
		 */
		if (!empty($page_params)) {
			$page_params = array_merge(array($page_params), explode('/', $page_params));
		} else {
			$page_params = array();
		}
			
		if ($global_sections[$section_name][1] == true) {
	
			//Handler -> include and terminate the bootstrap
			$handler_file = dirname(__FILE__).'/handlers/'.$global_sections[$section_name][0].'.php';
			require_once($handler_file);
			die();
	
		} else {
	
			//Displayable page
			$page_file = $global_sections[$section_name][0];
		}
	} else {
		//Fallback: default page
		$page_file = 'home';
	}
	
	/*
	 * Display the page
	* This section should be modified to be in accordance with the other pages of the theme
	*/
	get_header();
	echo('<section class="site-content" id="primary"><div role="main" id="content">'."\n");
	include('pages/'.$page_file.'.php');
	echo('</div></section>'."\n");
	get_sidebar();
	get_footer();
}
?>