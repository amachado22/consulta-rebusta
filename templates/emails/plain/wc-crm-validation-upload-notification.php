<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    3.4.7
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Hello %s,', 'wc_crm'), $recipient) . "\n\n";
$post_url = get_edit_post_link($post_id, 'view here');
echo sprintf('%s has been uploaded a validation. Please %s', $customer->first_name . ' ' . $customer->last_name, $post_url) . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
?>
