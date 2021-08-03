<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

do_action('woocommerce_email_header', $email_heading, $email); ?>

    <p><?php printf( __( 'Hello %s,', 'wc_crm' ), $recipient ); ?></p>
    <p><?php print __('You have been assigned a new customer.', 'wc_crm') ?></p>
    <p><?php printf( __('Customer Name: %s'), $customer->first_name . ' ' . $customer->last_name ) ?></p>
    <p><?php print __('Please click ', 'wc_crm') . '<a href="'.get_admin_url(null, 'admin.php?page=wc_crm&c_id=') . $customer->id .'" target="_blank">'.__('here ', 'wc_crm').'</a>' . __('to view the customers profile.', 'wc_crm') ?></p>

<?php do_action('woocommerce_email_footer', $email);