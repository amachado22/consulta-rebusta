<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    3.4.7
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

do_action('woocommerce_email_header', $email_heading, $email); ?>

    <p><?php printf( __( 'Hello %s,', 'wc_crm' ), $recipient ); ?></p>
    <p>
        <?php printf( __( '%s has been uploaded a validation. Please ', 'wc_crm' ), $customer->first_name . ' ' . $customer->last_name) ; ?>
        <a href="<?php print($post_url); ?>"> <?php _e('Visit here') ?> </a>
    </p>

<?php do_action('woocommerce_email_footer', $email);