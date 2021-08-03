<?php
/**
 * Email template
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <?php
    if(!($task instanceof WC_CRM_Task)){
        return;
    }
    ?>
	
	<p><?php printf( __( 'Dear %s,', 'wc_crm' ), $task->get_owner_name() ); ?></p>
	<p><?php  _e( 'A new task has been assigned to you:', 'wc_crm' ); ?></p>
		<?php if ( !empty($task->subject) ) { ?>
		<br><?php printf( __( 'Subject: %s', 'wc_crm' ), $task->subject ); ?>
		<?php } ?>
		<?php if ( !empty($task->due_date) && $task->due_date != '0000-00-00 00:00:00' ) { ?>
		<br><?php printf( __( 'Due date: %s', 'wc_crm' ), date_i18n( __( 'M j, Y @ H:i' ), strtotime( $task->due_date ) ) ); ?>
		<?php } ?>
		<?php if ( $task->customer_id > 0 ) { ?>
		<br><?php printf( __( 'Customer: #%d - %s', 'wc_crm' ), $task->customer_id, $task->get_customer_name()); ?>
		<?php } ?>
		<?php if ( $task->account > 0 ) { ?>
		<br><?php printf( __( 'Account: #%d - %s', 'wc_crm' ), $task->account, $task->get_account_name() ); ?>
		<?php } ?>
	</ul>

<?php  do_action( 'woocommerce_email_footer', $email );