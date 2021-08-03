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

class WC_CRM_Table_Customer_Calls extends WP_List_Table {
  protected $data;

  public function __construct( $calls = array() ){
    $this->data = $calls;
    parent::__construct( array(
        'singular'  => 'calls',
        'plural'    => 'calls',
        'ajax'      => false
    ));
  }
  protected function column_subject( $item ) {
    echo $this->column_title( $item );
    //echo $this->handle_row_actions( $post, 'title', $primary );
  }
  public function column_title( $post ) {
    $can_edit_post = current_user_can( 'edit_post', $post->ID );
    $title = _draft_or_post_title($post->ID);

    echo "<strong>";
    if ( $can_edit_post && $post->post_status != 'trash' ) {
      $edit_link = get_edit_post_link( $post->ID );
      echo '<a class="row-title" href="' . $edit_link . '" title="' . esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ) . '">' . $title . '</a>';
    } else {
      echo $title;
    }
    _post_states( $post );
    echo "</strong>\n";
  }


  public function column_default( $item, $column ) {
    do_action('manage_wc_crm_calls_posts_custom_column', $column, $item->ID);
  }

  public function get_columns(){
    $columns = apply_filters('manage_wc_crm_calls_posts_columns', array());
    unset($columns['customer']);
    return $columns;
  }

  public function no_items() {
    _e( 'No found.' );
  }


  public function prepare_items() {
    $columns  = $this->get_columns();
    $this->_column_headers = array( $columns, array(), array() );
    $this->items = $this->data;
  }

} //class