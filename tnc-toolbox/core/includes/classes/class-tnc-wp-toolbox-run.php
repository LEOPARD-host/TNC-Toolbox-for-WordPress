<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HELPER COMMENT START
 * 
 * This class is used to bring your plugin to life. 
 * All the other registered classed bring features which are
 * controlled and managed by this class.
 * 
 * Within the add_hooks() function, you can register all of 
 * your WordPress related actions and filters as followed:
 * 
 * add_action( 'my_action_hook_to_call', array( $this, 'the_action_hook_callback', 10, 1 ) );
 * or
 * add_filter( 'my_filter_hook_to_call', array( $this, 'the_filter_hook_callback', 10, 1 ) );
 * or
 * add_shortcode( 'my_shortcode_tag', array( $this, 'the_shortcode_callback', 10 ) );
 * 
 * Once added, you can create the callback function, within this class, as followed: 
 * 
 * public function the_action_hook_callback( $some_variable ){}
 * or
 * public function the_filter_hook_callback( $some_variable ){}
 * or
 * public function the_shortcode_callback( $attributes = array(), $content = '' ){}
 * 
 * 
 * HELPER COMMENT END
 */

/**
 * Class Tnc_Wp_Toolbox_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		TNCWPTBOX
 * @subpackage	Classes/Tnc_Wp_Toolbox_Run
 * @author		The Network Crew Pty Ltd
 * @since		1.0.0
 */
class Tnc_Wp_Toolbox_Run{

	/**
	 * Our Tnc_Wp_Toolbox_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct(){
		$this->add_hooks();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */
	
	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function add_hooks(){
	
		add_action( 'plugin_action_links_' . TNCWPTBOX_PLUGIN_BASE, array( $this, 'add_plugin_action_link' ), 20 );
		add_action( 'admin_bar_menu', array( $this, 'add_clear_cache_button' ), 100 );
		add_action( 'admin_post_clear_nginx_cache', array( $this, 'clear_nginx_cache' ) );
		add_action( 'admin_notices', array( $this, 'tnc_wp_toolbox_clear_cache_error_notice') );
		add_action( 'admin_notices', array( $this, 'tnc_wp_toolbox_clear_cache_success_notice') );
	
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	function tnc_wp_toolbox_clear_cache_error_notice() {
	    if ( $error_message = get_transient( 'tnc_wp_toolbox_clear_cache_error' ) ) {
		?>
		<div class="notice notice-error">
		    <p><?php echo $error_message; ?></p>
		</div>
		<?php
		delete_transient( 'tnc_wp_toolbox_clear_cache_error' );
	    }
	}

	function tnc_wp_toolbox_clear_cache_success_notice() {
	    if ( $success_message = get_transient( 'tnc_wp_toolbox_clear_cache_success' ) ) {
		?>
		<div class="notice notice-success">
		    <p><?php echo $success_message; ?></p>
		</div>
		<?php
		delete_transient( 'tnc_wp_toolbox_clear_cache_success' );
	    }
	}
	
	/**
	* Adds action links to the plugin list table
	*
	* @access	public
	* @since	1.0.0
	*
	* @param	array	$links An array of plugin action links.
	*
	* @return	array	An array of plugin action links.
	*/
	public function add_plugin_action_link( $links ) {

		$links['our_shop'] = sprintf( '<a href="%s" title="my.LEOPARD" style="font-weight:700;">%s</a>', 'https://my.leopard.host', __( 'my.LEOPARD', 'tnc-wp-toolbox' ) );

		return $links;
	}

	/**
	 * Add a new menu item to the WordPress topbar
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @param	object $admin_bar The WP_Admin_Bar object
	 *
	 * @return	void
	 */
	public function add_clear_cache_button( $wp_admin_bar ) {
	    $args = array(
		'id'    => 'clear_nginx_cache',
		'title' => 'Purge NGINX Cache',
		'href'  => admin_url( 'admin-post.php?action=clear_nginx_cache' ),
		'meta'  => array( 'class' => 'clear-nginx-cache' ),
	    );
	    $wp_admin_bar->add_node( $args );
	}
	
	/**
	 * Function to handle the NGINX User Cache purging
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	Success/Failure
	 */
	function clear_nginx_cache() {
	    // Get the cPanel username
	    $cpanel_username = get_current_user();
	    // Get the API token, exit if not present
	    if( is_readable("/home/".$cpanel_username."/.tnc/cp-api-key") ) {
		$api_token = file_get_contents("/home/".$cpanel_username."/.tnc/cp-api-key");
	    } else {
		set_transient( 'tnc_wp_toolbox_clear_cache_error', 'cPanel API Token could not be read - please set it in /home/USER/.tnc/cp-api-key');
		wp_safe_redirect(admin_url());
		exit;
	    }
	    // Get the server hostname
	    $server_hostname = gethostname();
	    // Build the headers for the request
	    $headers = array(
		'Authorization' => 'cpanel '. $cpanel_username . ':' . $api_token,
	    );
	    // Build the body for the request
	    $body = array(
		'parameter' => 'value',
	    );
	    // Build the URL for the request
	    $url = 'https://' . $server_hostname . ':2083/execute/NginxCaching/clear_cache';
	    // Make the request
	    $response = wp_remote_post( $url, array(
		'headers' => $headers,
		'body' => $body,
	    ) );
	    // Report the outcome
	    if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		set_transient( 'tnc_wp_toolbox_clear_cache_error', $error_message, 60 );
		wp_redirect( admin_url() );
		exit;
	    } elseif ( wp_remote_retrieve_response_code( $response ) == 200 ) {
		set_transient( 'tnc_wp_toolbox_clear_cache_success', 'NGINX User Cache has been successfully purged!', 60 );
		wp_redirect( admin_url() );
		exit;
	    } else {
		set_transient( 'tnc_wp_toolbox_clear_cache_error', 'We hit a snag while purging the NGINX User Cache. If this continues, please contact us.', 60 );
		wp_redirect( admin_url() );
		exit;
	    }	
	}

}