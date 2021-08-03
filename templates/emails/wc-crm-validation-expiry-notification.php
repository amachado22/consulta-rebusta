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
        <?php printf( __( 'A document named %s has expired. Please ', 'wc_crm' ), $document) ; ?>
        <a href="<?php print($post_url); ?>"> <?php _e('Visit here') ?> </a>
    </p>

<?php do_action('woocommerce_email_footer', $email);