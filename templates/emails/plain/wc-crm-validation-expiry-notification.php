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
echo sprintf('A document named %s has expired', $document) . '<a href="' . $post_url . '" target="_blank">' . __("Please visit here") . '</a>' ."\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
?>
