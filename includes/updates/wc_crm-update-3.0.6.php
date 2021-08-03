<?php
/**
 * Update WC_CRM to 3.0.6
 *
 * @author     Adailton Machado
 * @category    Admin
 * @package     WC_CRM/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $wpdb;
$wpdb->hide_errors();
$activity = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_crm_log WHERE activity_type = 'phone call' ");
if($activity){
	foreach ($activity as $log) {
		$args = array(
			'post_type'     => 'wc_crm_calls',
			'post_title'    => $log->subject,
		    'post_content'  => $log->message,
		    'post_status'   => $log->log_status == 'trash' ? 'trash' : 'wcrm-completed',
		    'post_author'   => $log->user_id,
		    'post_date'     => $log->created,
		    'post_date_gmt' => $log->created_gmt,
		);
		$call_id = wp_insert_post( $args, false );
		if( $call_id > 0 ){
			update_post_meta( $call_id, '_type', $log->call_type );
			update_post_meta( $call_id, '_purpose', $log->call_purpose );
			update_post_meta( $call_id, '_phone_number', $log->phone );

			$duration = explode(':', $log->call_duration);
			$call_duration = array(
				isset($duration[0]) && !empty($duration[0]) ? $duration[0] : 0,
				isset($duration[1]) && !empty($duration[1]) ? $duration[1] : 0,
				isset($duration[2]) && !empty($duration[2]) ? $duration[2] : 0,
			);
			update_post_meta( $call_id, '_call_duration', $call_duration );
			
			$product = $log->related_to == 'product' ? $log->number_order_product : 0;
			$order   = $log->related_to == 'order' ? $log->number_order_product : 0;
			
			update_post_meta( $call_id, '_product', $product );
			update_post_meta( $call_id, '_order', $order );

			$customer_id = 0;
			$customer = $wpdb->get_var("SELECT wc_crm_customer_id FROM {$wpdb->prefix}wc_crm_customermeta WHERE meta_value = {$log->ID} AND meta_key = 'wc_crm_log_id' LIMIT 1");
			if( !$customer ){
				$user = $wpdb->get_var("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_value = {$log->ID} AND meta_key = 'wc_crm_log_id' LIMIT 1");
				if( $user ){
					$customer = $wpdb->get_var("SELECT c_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE user_id = {$user} LIMIT 1");
				}
			}
			if($customer){
				$customer_id = $customer;
			}

			update_post_meta( $call_id, '_customer_id', $customer_id );
		}
		$wpdb->query("DELETE FROM {$wpdb->prefix}wc_crm_log WHERE ID = {$log->ID};");
	}
}