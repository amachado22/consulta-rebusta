<?php
/**
 * WooCommerceCRM Table Groups
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
class WC_CRM_Table_Groups extends WP_List_Table {
  protected $data;
  function __construct(){

      parent::__construct( array(
          'singular'  => __( 'groups_table', 'wc_crm' ),     //singular name of the listed records
          'plural'    => __( 'groups_tables', 'wc_crm' ),   //plural name of the listed records
          'ajax'      => false        //does this table support ajax?
      ) );

  }

  public function get_data(){
        global $wpdb;
        $filter = '';
        if( isset($_GET['s']) && !empty($_GET['s']) && $_GET['page'] == 'wc_crm-groups' ){
          $s = $_GET['s'];
          $filter = "WHERE lower( concat(group_name) ) LIKE lower('%$s%')";
        }
        $table_name = $wpdb->prefix . "wc_crm_groups";
        $db_data = $wpdb->get_results("SELECT * FROM $table_name $filter");
        $data = array();

        foreach ($db_data as $value) {
          $data[] = get_object_vars($value);
        }
        return $data;
  }
  function no_items() {
    _e( 'Groups not found. Try to adjust the filter.', 'wc_crm' );
  }
  function column_default( $item, $column_name ) {
    switch ( $column_name ) {
      case 'group_name':
      case 'group_slug':
      case 'group_type':
      case 'group_terms':
        return $item[$column_name];
      default:
        return print_r( $item, true ); //Show the whole array for troubleshooting purposes
    }
  }
  function get_sortable_columns() {
    $sortable_columns = array(
      'group_name' => array('group_name', false),
      'group_slug' => array('group_slug', false),
      'group_type' => array('group_type', false),
    );
    return $sortable_columns;
  }
  function get_columns() {
    $columns = array(
      'cb' => '<input type="checkbox" />',
      'group_name' => __( 'Name', 'wc_crm' ),
      'group_slug' => __( 'Slug', 'wc_crm' ),
      'group_type' => __( 'Type', 'wc_crm' ),
      'group_terms' => __( 'Terms', 'wc_crm' ),
      'group_action' => '',
    );
    return $columns;
  }
  function usort_reorder( $a, $b ) {
    // If no sort, default to last purchase
    $orderby = ( !empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'group_name';
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
    $actions = apply_filters( 'wc_customer_relationship_manager_groups_bulk_actions', array(
      'delete' => __( 'Delete', 'wc_crm' ),
    ) );
    return $actions;
  }

  function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="id[]" value="%s" />', $item['ID']
    );
  }
  function column_group_action( $item ) {
    return sprintf(
      '<a href="admin.php?page=wc_crm&group=%s" class="button tips view" data-tip="' . esc_attr__( 'View Customers in Group', 'wc_crm' ) . '">'.esc_attr__( 'View Customers in Group', 'wc_crm' ).'</a>', $item['ID']
    );
  }
  function column_group_terms( $item ) {
    if($item['group_type'] == 'dynamic'){
      $output = '';
      if(!empty($item['group_last_order_from'])){
        $order_from = strtotime($item['group_last_order_from']);
        $order_to   = strtotime($item['group_last_order_to']);
        switch ($item['group_last_order']) {
          case 'between':
            if( $order_from !== false && $order_to !== false && $order_from > 0 && $order_to > 0 )
            $output .= 'Date ' . $item['group_last_order'] . ' ' . $item['group_last_order_from'] . ' to ' . $item['group_last_order_to'] . '<br />';
            break;
          default:
            if( $order_from !== false && $order_from > 0)
              $output .= 'Date ' . $item['group_last_order'] . ' ' . $item['group_last_order_from'] . '<br />';
            break;
        }
      }
      if(!empty($item['group_user_role'])) $output .= 'User role is ' . $item['group_user_role'] . '<br />';
      if(!empty($item['group_customer_status'])){
        $group_customer_status = unserialize($item['group_customer_status']);
        if(!empty($group_customer_status)){
          if(count($group_customer_status) > 1 || !empty($group_customer_status[0]) )
            $output .= sprintf( __('Customer status is %s'), implode(', ', $group_customer_status) ) . '<br />';
        }
      }
      if(!empty($item['group_product_categories'])){
        $group_product_categories = unserialize($item['group_product_categories']);
        if(!empty($group_product_categories)){
          if(count($group_product_categories) > 1 || !empty($group_product_categories[0]) ){
            $cat_names = array();
            foreach ($group_product_categories as $cat) {
              $term = get_term_by('id', $cat, 'product_cat');
              $cat_names[] = $term->name;
            }
            $output .= sprintf( __('Product category is %s'), implode(', ', $cat_names) ) . '<br />';
          }
        }
      }
      if(!empty($item['group_order_status'])){
        $group_order_status = unserialize($item['group_order_status']);
        if(!empty($group_order_status)){
          if(count($group_order_status) > 1 || !empty($group_order_status[0]) ){
            $wc_statuses = wc_get_order_statuses();
            $staus_names = array();
            foreach ($group_order_status as $status) {
              $staus_names[] = $wc_statuses[$status];
            }
            $output .= sprintf( __('Order status is %s'), implode(', ', $staus_names) ) . '<br />';
          }
        }
      }
      if(!empty($item['group_total_spent'])) $output .= 'Total spent is ' . convert_group_total_spent_mark($item['group_total_spent_mark']) . ' ' . wc_price($item['group_total_spent']) . '<br />';
      if( !empty($output) ){
        $match = 'all';
        if( $item['group_match'] == 1 ){
          $match = 'any';
        }
        $match  = '<b>'.$match.'</b>';
        $output = sprintf('Match %s of the following rules: %s', $match, '<br>'. $output);
      }
      
      return $output;
    }
  }

  function column_group_name( $item ) {

    $actions = array(
      'edit'      => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', 'wc_crm-groups','edit', $item['ID']),
      'delete'      => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', 'wc_crm-groups','delete', $item['ID']),
    );

    $name = sprintf(
        '<strong><a style="display: block;" href="admin.php?page=%s&group=%s">%s</a></strong>', 'wc_crm',  $item['ID'], $item['group_name']
    );

    return sprintf('%1$s %2$s', $name, $this->row_actions($actions) );
  }

  function prepare_items() {
    $columns  = $this->get_columns();
    $hidden   = array();
    $this->data = $this->get_data();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array( $columns, $hidden, $sortable );
    usort( $this->data, array( &$this, 'usort_reorder' ) );

    $user = get_current_user_id();
    $screen = get_current_screen();
    $option = $screen->get_option('per_page', 'option');
    $per_page = get_user_meta($user, $option, true);
    if ( empty ( $per_page) || $per_page < 1 ) {
        $per_page = $screen->get_option( 'per_page', 'default' );
    }

    $current_page = $this->get_pagenum();

    $total_items = count( $this->data );
    if( $_GET['page'] == 'wc_crm-groups' ){
      // only ncessary because we have sample data
      $found_data = array_slice( $this->data,( ( $current_page-1 )* $per_page ), $per_page );

      $this->set_pagination_args( array(
        'total_items' => $total_items,                  //WE have to calculate the total number of items
        'per_page'    => $per_page                     //WE have to determine how many items to show on a page
      ) );
      $this->items = $found_data;
    }else{
      $this->items = $this->data;
    }
  }

}