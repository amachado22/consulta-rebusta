<?php
/**
 * Update WC_CRM to 3.0
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
$activity = $wpdb->get_results("SELECT ID, user_email FROM {$wpdb->prefix}wc_crm_log ");
if($activity){
	foreach ($activity as $act) {
		$emails = explode(',', $act->user_email);
		$log_id = $act->ID;

		if(!empty($emails)){
			foreach ($emails as $key => $email) {
				if( empty( $email ) ) continue;

				$customer = $wpdb->get_row("SELECT c_id, user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE email = '{$email}' LIMIT 1");			
				if($customer){
					if($customer->user_id > 0){
						add_user_meta($customer->user_id, 'wc_crm_log_id', $log_id);
					}else{
						wc_crm_add_cmeta($customer->c_id, 'wc_crm_log_id', $log_id);
					}
				}

			}
		}

	}
}

$groups = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_crm_groups_relationships ");
if($groups){
	foreach ($groups as $group) {
		$email    = $group->customer_email;
		$group_id = $group->group_id;
		$ID       = $group->ID;

		if( empty( $email ) ) continue;
		
		$customer = $wpdb->get_row("SELECT c_id, user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE email = '{$email}' LIMIT 1");			
		if($customer){
			$table        =  $wpdb->prefix.'wc_crm_groups_relationships';
			
			$data = array(
				'c_id'     => $customer->c_id
			);
			$where = array(
				'ID'       => $ID
			);
			$wpdb->update( $table, $data, $where );
		}

	}
}
$comments = $wpdb->get_results("SELECT * FROM {$wpdb->commentmeta} WHERE meta_key = 'customer_id' ");
if($comments){
	foreach ($comments as $comment) {
		$user_id = $comment->meta_value;
		if( empty( $user_id ) ) continue;

		$customer = $wpdb->get_row("SELECT c_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE user_id = {$user_id} LIMIT 1");			
		if($customer){
			$table        =  $wpdb->prefix.'wc_crm_groups_relationships';
			
			$data = array(
				'meta_value' => $customer->c_id
			);
			$where = array(
				'meta_id'  => $comment->meta_id
			);
			$wpdb->update( $wpdb->commentmeta, $data, $where );
		}
	}
}