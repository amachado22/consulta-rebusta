<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

	<?php echo $email_message; ?>

<?php do_action( 'woocommerce_email_footer' ); ?>