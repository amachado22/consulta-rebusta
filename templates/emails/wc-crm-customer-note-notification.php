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
    <p><?php printf( __( 'A note has been added to the customer record of %s:', 'wc_crm' ), $customer->first_name . ' ' . $customer->last_name) ; ?></p>
    <p><?php printf( __( 'Text: %s', 'wc_crm' ), $note->comment_content) ; ?></p>
    <p><?php printf( __( 'Author: %s', 'wc_crm' ), $note->comment_author . ' ' . $note->comment_author_email) ; ?></p>
    <p><?php printf( __( 'Type: %s', 'wc_crm' ), $note_data['note_type']) ; ?></p>

<?php do_action('woocommerce_email_footer', $email);