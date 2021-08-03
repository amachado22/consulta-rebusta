<?php
/**
 * WooCommerceCRM Table Class
 *
 * @author    Actuality Extensions
 * @package   WooCommerceCRM/Classes/
 * @category	Class
 * @since     0.1
 */


if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class WC_CRM_Table_Customer_Status extends WP_List_Table {
  protected static $data;
  protected $found_data;

  function __construct(){

      parent::__construct( array(
          'singular'  => __( 'status', 'wc_crm' ),     //singular name of the listed records
          'plural'    => __( 'statuses', 'wc_crm' ),   //plural name of the listed records
          'ajax'      => false        //does this table support ajax?
      ) );

  }
  function no_items() {
    _e( 'No custom statuses have been found. Try to adjust the filter.', 'wc_crm' );
  }
  function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'status_name':
      case 'status_slug':
      case 'status__icon':
        return $item[$column_name];
      default:
        return print_r( $item, true ); //Show the whole array for troubleshooting purposes
    }
  }
  function get_sortable_columns() {
    $sortable_columns = array(
      'status_name' => array('status_slug', false),
      'status_slug' => array('status_slug', false),
    );
    return $sortable_columns;
  }
  function get_columns() {
    $columns = array(
      'cb' => '<input type="checkbox" />',
      'status_name' => __( 'Name', 'wc_crm' ),
      'status_slug' => __( 'Slug', 'wc_crm' ),
      'status__icon' => __( 'Icon', 'wc_crm' ),
    );
    return $columns;
  }
  function usort_reorder( $a, $b ) {
    // If no sort, default to last purchase
    $orderby = ( !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'status_name';
    // If no order, default to desc
    $order = ( !empty( $_GET['order'] ) ) ? $_GET['order'] : 'desc';
    // Determine sort order
    if ( $orderby == 'order_value' ) {
      $result = $a[$orderby] - $b[$orderby];
    } else {
      $result = strcmp( $a[$orderby], $b[$orderby] );
    }
    // Send final sort direction to usort
    return ( $order === 'asc' ) ? $result : -$result;
  }

  function get_bulk_actions() {
    $actions = apply_filters( 'wc_customer_relationship_manager_statuses_bulk_actions', array(
      'delete' => __( 'Delete', 'wc_crm' ),
    ) );
    return $actions;
  }

  function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="id[]" value="%s" />', $item['status_id']
    );
  }
 
  function column_status_name( $item ) {

    $actions = array(
      'edit'      => sprintf('<a href="admin.php?page=wc_crm-settings&tab=statuses&action=%s&id=%s">Edit</a>', 'edit', $item['status_id']),
      'delete'      => sprintf('<a href="admin.php?page=wc_crm-settings&tab=statuses&action=%s&id=%s">Delete</a>', 'delete', $item['status_id']),
    );

    $name = sprintf(
        '<strong><a href="admin.php?page=wc_crm-settings&tab=statuses&action=%s&id=%s">%s</a></strong>', 'edit', $item['status_id'], $item['status_name']
    );

    return sprintf('%1$s %2$s', $name, $this->row_actions($actions) );
  }
  function column_status__icon( $item ) {
    $s = wc_crm_get_status_icon_code($item['status_icon']);    
    return sprintf('<i data-icomoon="%s" data-fip-value="%s" style="color: %s;"></i>', $s, $item['status_icon'],  $item['status_colour']);
  }

  function prepare_items() {
    $columns  = $this->get_columns();
    $hidden   = array();
    self::$data = wc_crm_get_statuses(true, true);
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array( $columns, $hidden, $sortable );
    usort( self::$data, array( &$this, 'usort_reorder' ) );

    $per_page = 10;

    $current_page = $this->get_pagenum();

    $total_items = count( self::$data );
    if( $_GET['page'] == 'wc_user_grps' || ( $_GET['page'] == 'wc_crm-settings' && !empty($_GET['tab']) && $_GET['tab'] == 'statuses' ) ){
      // only ncessary because we have sample data
      $this->found_data = array_slice( self::$data,( ( $current_page-1 )* $per_page ), $per_page );

      $this->set_pagination_args( array(
        'total_items'   => $total_items,                  //WE have to calculate the total number of items
        'per_page' => $per_page                     //WE have to determine how many items to show on a page
      ) );
      $this->items = $this->found_data;
    }else{
      $this->items = self::$data;
    }
  }

}