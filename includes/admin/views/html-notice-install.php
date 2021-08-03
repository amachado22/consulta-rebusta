<?php
/**
 * Admin View: Notice - Install
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="message" class="updated">
	<p><?php _e( '<strong>Welcome to WooCommerce Customer Relationship Manager</strong> &#8211; You&lsquo;re almost ready to start', 'wc_crm' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( admin_url( 'admin.php?page='.WC_CRM_TOKEN.'-setup' ) ); ?>" class="button-primary"><?php _e( 'Run the Setup Wizard', 'wc_crm' ); ?></a></p>
</div>
