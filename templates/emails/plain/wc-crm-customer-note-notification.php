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

echo printf(__('Hello %s,', 'wc_crm'), $recipient) . "\n\n";
echo printf(__('A note has been added to the customer record of %s:', 'wc_crm'), $customer->first_name . ' ' . $customer->last_name) . "\n\n";
echo printf(__('Text: %s', 'wc_crm'), $note->comment_content) . "\n\n";
echo printf(__('Author: %s', 'wc_crm'), $note->comment_author . ' ' . $note->comment_author_email) . "\n\n";
echo printf(__('Type: %s', 'wc_crm'), $note_data['note_type']) . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
?>
