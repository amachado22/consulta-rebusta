<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Hello %s,', 'wc_crm'), $recipient) . "\n\n";
echo __('You have been assigned a new customer.', 'wc_crm') . "\n\n";
echo sprintf( __('Customer Name: %s'), $customer->first_name . ' ' . $customer->last_name ) . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
?>
