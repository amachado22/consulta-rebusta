<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if( !WC_CRM_Admin_Notices::has_notice('crm_install') ) {
?>
<div id="message" class="updated">
	<p><?php _e( '<strong>WooCommerce Customer Relationship Manager Data Update Required</strong> &#8211; We just need to update your install to the latest version', 'wc_crm' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_wc_crm', 'true', admin_url( 'admin.php?page='.WC_CRM_TOKEN.'-settings' ) ) ); ?>" class="wc-update-now button-primary"><?php _e( 'Run the updater', 'woocommerce' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.wc-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woocommerce' ) ); ?>' );
	});
</script>
<?php }