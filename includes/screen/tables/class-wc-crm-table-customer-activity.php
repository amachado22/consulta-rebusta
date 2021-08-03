<?php
/**
 * Table with list of customers activity.
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class WC_CRM_Table_Customer_Activity extends WP_List_Table {
  protected $data;

  function __construct( $activity = array() ){
    $this->data = $activity;
    parent::__construct( array(
        'singular'  => __( 'log', 'wc_crm' ),
        'plural'    => __( 'logs', 'wc_crm' ),
        'ajax'      => false
    ));
  }
  function column_default( $item, $column_name ) {
    switch( $column_name ) {
        case 'subject':
        case 'activity_type':
        case 'created':
        case 'crm_actions':
            return $item[ $column_name ];
        case 'user_id':
            $user_info  = get_userdata( $item[ $column_name ] );
            return '<a href="'.get_edit_user_link( $item[ $column_name ] ).'">'.$user_info->display_name.'</a>';
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    }
  }

  function get_sortable_columns() {
    $sortable_columns = array(
      'subject'        => array('subject',false),
      'activity_type'  => array('activity_type',false),
      'created'        => array('created',false),
      'user_id'        => array('user_id',false)
    );
    return $sortable_columns;
  }
  function get_columns(){
    $columns = array(
      'subject'        => __( 'Subject', 'wc_crm' ),
      'activity_type'  => __( 'Type', 'wc_crm' ),
      'created'        => __( 'Date', 'wc_crm' ),
      'user_id'        => __( 'Author', 'wc_crm' ),
      'crm_actions'    => __( 'Actions', 'wc_crm' )
    );

    if( $_GET['page'] != WC_CRM_TOKEN ){
      unset($columns['crm_actions']);
      unset($columns['cb']);
    }
    if( isset($_GET['log_status']) && $_GET['log_status'] == 'trash'){
      unset($columns['crm_actions']);
    }
     return $columns;
  }

  function no_items() {
    _e( 'No found.' );
  }

function usort_reorder( $a, $b ) {
  // If no sort, default to title
  $orderby = ! empty( $_GET['orderby'] ) ? $_GET['orderby'] : 'created';
  // If no order, default to desc
  $order = ! empty($_GET['order'] ) ? $_GET['order'] : 'ASC';
  // Determine sort order
  $result = strcmp( $a[$orderby], $b[$orderby] );
  // Send final sort direction to usort
  return ( $order === 'desc' ) ? $result : -$result;
}


function column_crm_actions( $item ) {
    global $woocommerce;

    $actions = array(
      'view' => array(
        'classes' => 'view',
        'url' => sprintf('?page=%s&log_id=%d', WC_CRM_TOKEN.'-logs', $item['ID']),
        'name' => __( 'View', 'wc_crm' )
      ),
      'delete_log' => array(
        'classes' => 'delete_log',
        'url' => sprintf('?page=%s&c_id=%d&action=%s&log=%d', WC_CRM_TOKEN, $_GET['c_id'],'trash',$item['ID']),
        'name' => __( 'Trash', 'wc_crm' ),
      )
    );

    echo '<p>';
    foreach ( $actions as $action ) {
      printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr($action['classes']), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
    }
    echo '</p>';

  }

function column_subject($item) {
  $actions = array();
  $item['subject'] = wp_unslash($item['subject']);
  $subject = '';
  $url = sprintf('?page=%s&c_id=%d&action=%s&log=%d', WC_CRM_TOKEN, $_GET['c_id'],'trash',$item['ID']);
  $actions = array(
    'id'      => sprintf('ID: %s ', $item['ID']),
    'view'    => sprintf('<a href="?page=%s&log_id=%d">View</a>', WC_CRM_TOKEN.'-logs', $item['ID']),
    'trash'   => '<a href="'.$url.'">Trash</a>',
  );
  $subject =  sprintf(
      '<strong><a href="?page=%s&log_id=%d">%s</a></strong>',WC_CRM_TOKEN.'-logs', $item['ID'], $item['subject']
  );    
  return sprintf('%1$s %2$s', $subject, $this->row_actions($actions) );
}
function column_activity_type($item) {
    if($item['activity_type'] == 'email'){
      return '<i class="email tips" data-tip="' . esc_attr__( 'Email', 'woocommerce-crm' ) . '"></i>';
    }else if($item['activity_type'] == 'phone call'){
      return '<i class="phone tips" data-tip="' . esc_attr__( 'Phone', 'woocommerce-crm' ) . '"></i>';
    }else{
      return '-';
    }
}
function column_created($item) {
    $t_time = date("Y/m/d g:i:s A", strtotime($item['created'] ) );

    if ( '0000-00-00 00:00:00' == $item['created_gmt'] ) {
      $item['created_gmt'] =  get_gmt_from_date( $item['created'] );
    }
    $gmt_time = strtotime( $item['created_gmt'] . ' UTC' );
    $time_diff = current_time( 'timestamp', 1 ) - $gmt_time;

    if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 )
      $h_time = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( $gmt_time, current_time( 'timestamp', 1 ) ) );
    else
      $h_time = date("Y/m/d", strtotime($item['created'] ) );
    return '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time) . '</abbr>';
}

function prepare_items() {
  $columns  = $this->get_columns();

  if( $_GET['page'] != WC_CRM_TOKEN ){
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array( $columns, array(), $sortable );
    usort( $this->data, array( &$this, 'usort_reorder' ) );
    $user   = get_current_user_id();
    $screen = get_current_screen();
    $option = $screen->get_option('per_page', 'option');

    $per_page = get_user_meta($user, $option, true);

    if ( empty ( $per_page) || $per_page < 1 ) {
        $per_page = $screen->get_option( 'per_page', 'default' );
    }

    $current_page = $this->get_pagenum();
    $total_items = count( $this->data );

    $this->found_data = array_slice( $this->data,( ( $current_page-1 )* $per_page ), $per_page );

    $this->set_pagination_args( array(
      'total_items'   => $total_items,
      'per_page'      => $per_page
    ) );
    $this->items = $this->found_data;
  }else{
    $this->_column_headers = array( $columns, array(), array() );
    $this->items = $this->data;
  }
}

} //class