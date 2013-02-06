<?php
/*
Plugin Name: kkm
Description: Community based music score management system.
Version: 1.0
Author: Attila Wagner, Erzsébet Frigó
*/

/**
 * Main class for the kkm plugin.
 * Contains functions that are given to the WordPress
 * through action hooks.
 * 
 * @author Attila Wagner
 */
class kkm {
	
	/**
	 * Called by WP after the user data has been processed in any request,
	 * but the $wp_query is still uninitalized.
	 * 
	 * Catches /kkm/ URLs and admin requests.
	 */
	public static function init() {
		$plugin_visible = false;
		
		//kkm pages only
		if (($pos = strpos($_SERVER['REQUEST_URI'], '/kkm/')) !== false) {
			$plugin_visible = true;
			
			add_action('wp_loaded', array('kkm','bootstrap'), 99, 2);
		}
	
		//Admin area only
		if (is_admin()) {		
			if (strpos($_SERVER['REQUEST_URI'], 'wp-admin/admin.php?page=kkm') !== false) {
				$plugin_visible = true;
				
				wp_enqueue_script('farbtastic');
				wp_register_script('kkm-admin', plugins_url('kkm/ui/admin.js'), array('jquery'));
				wp_enqueue_script('kkm-admin');
			}
		}
		
		if ($plugin_visible) {
			self::register_plugin_content();
		}
	}
	
	/**
	 * Registered to be called by the template_redirect action,
	 * this function redirects search requests to be handled by the plugin's
	 * own search result page.
	 */
	public static function on_template_redirect() {
		global $wp_query;
		if (is_search()) {
			$search_query = $wp_query->get('s');
			$absolute_url = site_url('kkm/search/'.rawurlencode($search_query));
			wp_redirect($absolute_url); //Temporal redirect
			die();
		}
	}
	
	/**
	 * Registers the CSS and JS files of the plugin,
	 * and loads the localization.
	 */
	private static function register_plugin_content() {
		//Localization
		//load_plugin_textdomain('kkm', 'languages');
		
		self::remove_wp_magic_quotes();
		
		//Files used by both the admin section and the frontend
		wp_register_style('kkm', plugins_url('kkm/ui/main.css'));
		wp_enqueue_style('kkm');
		wp_register_script('kkm', plugins_url('kkm/ui/main.js'), array('jquery'));
		wp_enqueue_script('kkm');
			
		add_action('wp_head', array('kkm', 'render_header_js'));
	}
	
	/**
	 * Renders a JavaScript stub into the header.
	 */
	public static function render_header_js() {
		echo('<script type="text/javascript" language="javascript">kkm_root_url="'.KKM_PLUGIN_URL.'"</script>');
	}
	
	/**
	 * Called on /kkm/ requests, this function includes the bootstrap,
	 * which terminates any further processing.
	 */
	public static function bootstrap() {
		global $wpdb, $current_user;
		
		define('KKM_REQUEST', true);
		include('bootstrap.php');
		die();
	}
	
	/**
	 * Calls wp_redirect() to show the user the error page.
	 * Terminates further processing.
	 */
	public static function redirect_to_error_page() {
		$absolute_url = site_url('kkm/error');
		wp_redirect($absolute_url); //Temporal redirect
		die();
	}
	
	/**
	 * Registers the admin menus.
	 * 
	 * A separate admin section is registered (called 'kkm'),
	 * and the separate config pages are registered as its menuitems.
	 */
	public static function register_admin_menus() {
		add_menu_page('kkm', 'kkm', 'manage_options', 'kkm/admin/index.php');
		add_submenu_page('kkm/admin/index.php', __('General settings &lsaquo; kkm', 'kkm'), __('General settings', 'kkm'), 'manage_options', 'kkm/admin/index.php');
		add_submenu_page('kkm/admin/index.php', __('Tag categories &lsaquo; kkm', 'kkm'), __('Tag categories', 'kkm'), 'manage_options', 'kkm/admin/tagcats.php');
	}
	
	/**
	 * Called upon plugin activation from the admin interface.
	 * Includes the install script and calls the install function.
	 */
	public static function plugin_activation() {
		require_once plugin_dir_path(__FILE__).'/install.php';
		kkm_install();
	}
	
	/**
	 * Called upon plugin deactivation from the admin interface.
	 * Includes the install script and calls the uninstall function.
	 */
	public static function plugin_deactivation() {
		require_once plugin_dir_path(__FILE__).'/install.php';
		kkm_uninstall();
	}
	
	/**
	 * Helper function to undo WP's magic quoting.
	 */
	private static function remove_wp_magic_quotes() {
		$_POST = stripslashes_deep($_POST);
	}
}

/*
 * Define constants.
 */
define('KKM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KKM_PLUGIN_URL', plugin_dir_url(__FILE__));
/*
 * Include function libraries.
 */
require_once('lib/kkmfiles.php');
require_once('lib/kkmtags.php');
require_once('lib/kkmuser.php');
require_once('lib/kkmmodules.php');
require_once('lib/kkmoptions.php');
require_once('lib/kkmsearch.php');

/*
 * Register WP hooks.
 */
register_activation_hook(__FILE__, array('kkm', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('kkm', 'plugin_deactivation'));
add_action('init', array('kkm','init'), 99);
add_action('admin_menu', array('kkm','register_admin_menus'));
add_action('template_redirect', array('kkm','on_template_redirect'), 1);

//DEBUG
add_action('activated_plugin','save_error');
function save_error(){
	file_put_contents('D:/debug_install_log.txt', ob_get_contents());
}
?>