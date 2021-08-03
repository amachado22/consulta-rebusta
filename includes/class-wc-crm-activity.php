<?php
/**
 * CRM Logs
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CRM_Activity{

  public $id;

  public function __construct($log_id = 0) {
    $this->id = $log_id;
    $data = $this->get_data();
    if($data){
      foreach ($data as $key => $log) {
        $this->$key = $log;
      }
    }

  }

  public function get_data()
  {
    global $wpdb;
    $result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wc_crm_log WHERE ID = {$this->id} LIMIT 1", ARRAY_A);
    return $result;
  }

  public function get_recipients()
  {
    global $wpdb;

    /*$emails = explode(',', $this->user_email);
    $emails_str = '';
    foreach ($emails as $email_c) {
      if(!empty($emails_str)) $emails_str .= ', ';
      $emails_str .= "'".$email_c."'";
    } */

    $sql = "SELECT user_id FROM {$wpdb->usermeta}                    
      WHERE meta_key = 'wc_crm_log_id' AND meta_value = {$this->id}
    " ;
    $u  = $wpdb->get_results( $sql);
    $_u = array();
    if($u){
      foreach ($u as $value) {
        $_u[] = $value->user_id;
      }
    }

    $sql = "SELECT wc_crm_customer_id FROM {$wpdb->wc_crm_customermeta}                    
      WHERE meta_key = 'wc_crm_log_id' AND meta_value = {$this->id}
    " ;
    $c = $wpdb->get_results( $sql);
    $_c = array();
    if($c){
      foreach ($c as $value) {
        $_c[] = $value->wc_crm_customer_id;
      }
    }
    $filter = array();
    if( !empty($_u) ){
      $filter[] = "user_id IN (".implode(',', $_u).")";
    }
    if( !empty($_c) ){
      $filter[] = "c_id IN (".implode(',', $_c).")";
    }
    if( !empty($filter) ){
      $_filter = "WHERE " . implode(' OR ', $filter);
      $sql = "SELECT c_id, email, first_name, last_name FROM {$wpdb->prefix}wc_crm_customer_list
        $_filter
        ORDER BY email ASC
      " ;
      return $wpdb->get_results( $sql); 
    }
    return false;
  }

} //class