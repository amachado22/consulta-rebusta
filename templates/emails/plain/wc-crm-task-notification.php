<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( 'Dear %s,', 'wc_crm' ), $task->get_owner_name() ) . "\n\n";
echo __( 'A new task has been assigned to you:', 'wc_crm' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( !empty($task->subject) ){
	echo sprintf( __( 'Subject: %s', 'wc_crm' ), $task->subject ) . "\n\n";
}
if ( !empty($task->due_date) && $task->due_date != '0000-00-00 00:00:00' ) {
	echo sprintf( __( 'Due date: %s', 'wc_crm' ), date_i18n( __( 'M j, Y @ H:i' ), strtotime( $task->due_date ) ) ) . "\n\n";
}
if ( $task->customer_id > 0 ) {
	echo sprintf( __( 'Customer: #%d - %s', 'wc_crm' ), $task->customer_id, $task->get_customer_name()) . "\n\n";
}
if ( $task->account > 0 ) {
	echo sprintf( __( 'Account: #%d - %s', 'wc_crm' ), $task->account, $task->get_account_name() ) . "\n\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
