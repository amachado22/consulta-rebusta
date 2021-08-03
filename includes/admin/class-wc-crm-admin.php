<?php
/**
 * WC_CRM Admin.
 *
 * @class       WC_CRM_Admin
 * @author     Adailton Machado
 * @category    Admin
 * @package     WC_CRM/Admin
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_CRM_Admin class.
 */
class WC_CRM_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'wc_print_js', 25 );

		if( isset($_GET['page']) && ($_GET['page'] == 'wc_crm-new-customer' || $_GET['page'] == 'wc_crm_import' ) ){
        	add_filter('woocommerce_email_actions', 'wc_crm_automatic_emails_new_customer', 150, 1);
		}
    	add_filter('woocommerce_email_classes', array($this, 'woocommerce_email_classes'), 150, 1);
    	#add_filter('woocommerce_locate_template', array($this, 'locate_email_template'), 150, 3);
    	add_action( 'admin_bar_menu', array($this, 'current_call_bar'), 999 );

		include_once( 'class-wc-crm-admin-post-types.php' );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {		
		include_once( 'class-wc-crm-admin-post-actions.php' );
		include_once( 'class-wc-crm-admin-import.php' );
		include_once( 'class-wc-crm-admin-menus.php' );
		if ( version_compare( WC_VERSION, '2.6', '>=' ) ) {
			include_once( 'class-wc-crm-admin-notices.php' );
        }else{
			include_once( 'class-wc-crm-admin-notices-v-2.5.5.php' );
        }
        
		include_once( 'class-wc-crm-admin-orders-page.php' );
		/*********** ACF ************/
        if (class_exists('acf')){
			include_once( 'class-wc-crm-admin-acf.php' );
        }
        /***********************/

		// Setup/welcome
		if ( ! empty( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case WC_CRM_TOKEN.'-setup' :
					include_once( 'class-wc-crm-admin-setup-wizard.php' );
					break;
				case WC_CRM_TOKEN.'-about' :
					include_once( 'class-wc-crm-admin-welcome.php' );
					break;
			}
		}
	}

	/**
	 * Include admin files conditionally
	 */
	public function conditional_includes() {
		$screen = get_current_screen();

		switch ( $screen->id ) {
			case 'dashboard' :
				include( 'class-wc-admin-dashboard.php' );
			break;
			case 'options-permalink' :
				include( 'class-wc-admin-permalink-settings.php' );
			break;
			case 'users' :
			case 'user' :
			case 'profile' :
			case 'user-edit' :
				include( 'class-wc-admin-profile.php' );
			break;
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * Transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		if ( ! get_transient( '_wc_crm_activation_redirect' ) || is_network_admin() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		delete_transient( '_wc_crm_activation_redirect' );

		if ( ! empty( $_GET['page'] ) && in_array( $_GET['page'], array( WC_CRM_TOKEN.'-setup', WC_CRM_TOKEN.'-about' ) ) ) {
			return;
		}

		if (  defined( 'DOING_AJAX' ) && DOING_AJAX  ) {
			return;
		}

		// If the user needs to install, send them to the setup wizard
		if ( WC_CRM_Admin_Notices::has_notice( 'crm_install' ) ) {
			wp_safe_redirect( admin_url( 'index.php?page='.WC_CRM_TOKEN.'-setup' ) );
			exit;

		// Otherwise, the welcome page
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page='.WC_CRM_TOKEN.'-about' ) );
			exit;
		}
	}

	public function locate_email_template($template, $template_name, $template_path)
	{

		if( $template_name == 'emails/wc-crm-task-notification.php' || $template_name == 'emails/plain/wc-crm-task-notification.php'){
			var_dump($template);
			var_dump($template_path);
			die;
			
		}
		return $template;
	}

	public function woocommerce_email_classes($email)
	{
		$email['WC_CRM_Email_Task_Notification'] = include( 'emails/class-wc-crm-email-task-notification.php' );
        $email['WC_CRM_Email_Customer_Note'] = include( 'emails/class-wc-crm-email-customer-note.php' );
        $email['WC_CRM_Email_Agent_Assignment'] = include( 'emails/class-wc-crm-email-agent-assignment.php' );
        $email['WC_CRM_Email_Agent_Order'] = include( 'emails/class-wc-crm-email-agent-order.php' );
        $email['WC_CRM_Email_Validation_Upload'] = include( 'emails/class-wc-crm-email-validation-upload.php' );
        $email['WC_CRM_Email_Validation_Expiry'] = include( 'emails/class-wc-crm-email-validation-expiry.php' );
		return $email;
	}



	function current_call_bar( $wp_admin_bar ) {

		global $post;
		if( !isset( $_COOKIE['wc_crm_current_call'] ) ) return;
		$current_call = json_decode( stripslashes( $_COOKIE['wc_crm_current_call'] ) );
		if( $post && !is_null($post) && $post->ID == $current_call->post_id ) return;

		$call = new WC_CRM_Call( $current_call->post_id );

		$can_edit_post = current_user_can( 'edit_post', $call->id );
	    $title = _draft_or_post_title($call->id);

	    if ( !$can_edit_post || $call->call_status == 'trash' ) return;
        $edit_link = get_edit_post_link( $call->id );

		 if($current_call->call_stop <= 0 ){
            $current_call->call_stop = time();
        }
        if($current_call->pause_stamp > 0 ){
            $current_call->call_stop   = $current_call->pause_stamp;
            $current_call->pause_stamp = 0;
        }
		$duration = $current_call->call_stop - $current_call->call_start - $current_call->pause_duration;
		
		ob_start();
		include WC_CRM()->plugin_path() . '/includes/screen/views/html-call-current_call.php';

		$html = ob_get_contents();

		ob_end_clean();

		$args = array(
			'id'     => 'current_call_bar',
			'title'  => '<span class="ab-icon"></span><span class="ab-label">' . __( 'Current Call', 'wc_crm' ) . '</span> - <span class="current_call_time" style="font-family: Segoe UI,Roboto,Oxygen-Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif; font-size: 14px;" id="current_call_time_bar">'.wc_crm_formatTime($duration).'</span>',
			'parent' => 'top-secondary',
			'meta'   => array(
				'class' => 'hide-if-no-js',
			),
		);
		$wp_admin_bar->add_node( $args );

		$args = array(
			'id'     => 'cal_title',
			'title'  => __('Call:', 'wc_crm') . ' ' . $title,
			'href'   =>	$edit_link,
			'parent' => 'current_call_bar'
		);
		$wp_admin_bar->add_node( $args );

	}

}

return new WC_CRM_Admin();
