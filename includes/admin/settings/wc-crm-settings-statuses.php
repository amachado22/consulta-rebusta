<?php
/**
 * WooCommerce Customer Status Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Crm_Settings_Customer_Status' ) ) :

/**
 * WC_Crm_Settings_Customer_Status
 */
class WC_Crm_Settings_Customer_Status extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'statuses';
		$this->label = __( 'Customer Status', 'woocommerce' );

		add_filter( 'wc_crm_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wc_crm_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wc_crm_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	public function output()
	{
		$GLOBALS['hide_save_button'] = true;
		if( isset($_GET['action']) && $_GET['action'] == 'delete' ){
			$this->delete_status();
		}

		if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id']) && !empty($_GET['id'])){
	    $data = wc_crm_get_status($_GET['id']);
	    ?>
	    <div class="wrap">
	      <h2><?php _e( 'Edit Status ', 'wc_crm' ); ?></h2>
	      <form method="post" action="" enctype="multipart/form-data" id="wc_crm_customer_statuses">
	        <input type="hidden" value="wc_crm_add_customer_status" name="action">
	        <input type="hidden" value="<?php echo $_GET['id']; ?>" name="status_id">  
	        <table class="form-table">
	          <tbody>
	            <tr class="form-field form-required">
	              <th scope="row">
	                <label for="status_name"><?php _e( 'Name', 'wc_crm' ); ?></label>
	              </th>
	              <td>
	                <input id="status_name" type="text" aria-required="true" size="40" value="<?php echo $data['status_name']; ?>" name="status_name">
	              </td>
	            </tr>
	            <tr class="form-field">
	              <th scope="row">
	                <label for="status_slug"><?php _e( 'Slug', 'wc_crm' ); ?></label>
	              </th>
	              <td>
	                <input id="status_slug" type="text" aria-required="true" size="40" value="<?php echo $data['status_slug']; ?>" name="status_slug">
	              </td>
	            </tr>
	            <tr class="form-field form-required">
	              <th scope="row">
	                <label for="status_icon"><?php _e( 'Icon', 'wc_crm' ); ?></label>
	              </th>
	              <td>
	                <input id="status_icon" type="text" aria-required="true" size="40" value="<?php echo $data['status_icon']; ?>" name="status_icon">
	              </td>
	            </tr>
	            <tr class="form-field form-required">
	              <th scope="row">
	                <label for="status_colour"><?php _e( 'Colour', 'wc_crm' ); ?></label>
	              </th>
	              <td>
	                <input id="status_colour" type="text" aria-required="true" size="40" value="<?php echo $data['status_colour']; ?>" name="status_colour">
	              </td>
	            </tr>
	          </tbody>
	        </table>
	        <p class="submit"><input type="submit" value="Save Status" class="button button-primary" id="submit" name="submit"></p>
	        <p><a href="<?php echo admin_url('admin.php?page=wc_crm-settings&tab=statuses'); ?>"><?php _e('Back to statuses list', 'wc_crm'); ?></a></p>
	        <?php wp_nonce_field( 'wc-crm-settings' ); ?>
	      </form>
	    </div>
	    <?php
	  }else{
	    ?>
	    <div class="wrap nosubsub" id="wc-crm-page">
	      <h2><?php _e( 'Customer Status ', 'wc_crm' ); ?></h2>
	      <div id="col-container">
	        <div id="col-right">
	          <div class="col-wrap">
	            <form method="post" action="">
	              <?php
	              $statuses_table = new WC_CRM_Table_Customer_Status;
	              $statuses_table->prepare_items();
	              $statuses_table->display();
	              ?>
	              <?php wp_nonce_field( 'wc-crm-settings' ); ?>
	            </form>
	          </div>
	        </div><!-- /col-right -->
	        <div id="col-left">
	          <div class="col-wrap">
	            <div class="form-wrap">
	              <form method="post" action="" enctype="multipart/form-data"  id="wc_crm_customer_statuses">
	                <input type="hidden" value="wc_crm_add_customer_status" name="action">   
	                <div class="form-field form-required">
	                  <label for="status_name"><?php _e( 'Name', 'wc_crm' ); ?></label>
	                  <input id="status_name" type="text" aria-required="true" size="40" value="" name="status_name">
	                </div>
	                 <div class="form-field">
	                  <label for="status_slug"><?php _e( 'Slug', 'wc_crm' ); ?></label>
	                  <input id="status_slug" type="text" aria-required="true" size="40" value="" name="status_slug">
	                </div>
	                <div class="form-field form-required">
	                  <label for="status_icon"><?php _e( 'Icon', 'wc_crm' ); ?></label>
	                  <input id="status_icon" type="text" aria-required="true" size="40" value="" name="status_icon">
	                </div>
	                <div class="form-field form-required">
	                  <label for="status_colour"><?php _e( 'Colour', 'wc_crm' ); ?></label>
	                  <input id="status_colour" type="text" aria-required="true" size="40" value="" name="status_colour">
	                </div>
	                <p class="submit"><input type="submit" value="Add New Status" class="button button-primary" id="submit" name="submit"></p>
	                <?php wp_nonce_field( 'wc-crm-settings' ); ?>
	              </form>
	            </div>
	          </div>
	        </div><!-- /col-left -->
	      </div><!-- /col-container -->
	    </div>
	    <?php
	  }
	}

	/**
	 * Save settings
	 */
	public function save() {

		if(!isset($_GET['page']) || $_GET['page'] != 'wc_crm-settings')
			return;

		if(!isset($_REQUEST['action']) && !isset($_REQUEST['action2']))
			return;

		$action = '';
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			$action = $_REQUEST['action'];

		elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			$action = $_REQUEST['action2'];

		if( !empty($action) ){
			switch ($action) {
			case 'wc_crm_add_customer_status':
			    $this->save_status();
			    break;
		    case 'delete':
			    $this->delete_status();
			    break;
			}
		}

	}
	function save_status(){
		global $wpdb;
		$table = $wpdb->prefix . "wc_crm_statuses";
		extract($_POST);

		if(!isset($status_slug) || empty($status_slug) ){
			$status_slug = sanitize_title($status_name);
		}else{
			$status_slug = sanitize_title($status_slug);
		}
		$filter = '';
		if(isset($status_id) && !empty($status_id)){
		$filter = " AND status_id != {$status_id}";
		}
		$check_sql = "SELECT status_slug FROM {$table} WHERE status_slug = '%s' {$filter} LIMIT 1";

		$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $status_slug ) );


		if ( $slug_check ) {
			$suffix = 2;
			do {
			  $alt_slug = _truncate_post_slug( $status_slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
			  $slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug ) );
			  $suffix++;
			} while ( $slug_check );
			$status_slug = $alt_slug;
		}
		$data = array(
			'status_name'   => $status_name,
			'status_slug'   => $status_slug,
			'status_icon'   => $status_icon,
			'status_colour' => $status_colour,
		);
		if(isset($status_id) && !empty($status_id)){
			$wpdb->update( $table, $data, array('status_id'=>$status_id) );
		}else{
			$wpdb->insert($table, $data);
		}
		wc_crm_clear_transient();
	}

	function delete_status(){
		global $wpdb;
		if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
			$id = $_REQUEST['id'];
			if(is_array($_REQUEST['id'])){
			  $filter = " WHERE status_id IN(".implode(',', $id).") ";
			  foreach ($id as $st_id) {
			    $_status = wc_crm_get_status($st_id);
			    $status  = $_status['status_slug'];
			    if($status){
			      $user_sql = "SELECT user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE status = '$status' ";
			      $user_ids = array();
			      $users = $wpdb->get_results($user_sql);
			      if(!empty($users)){
			        foreach ($users as $value) {
			          $user_ids[] = $value->user_id;
			        }
			        wc_crm_change_customer_status('Customer', $user_ids);
			      }
			    }
			  }
			}else{
			  $filter = " WHERE status_id = $id";

			    $_status = wc_crm_get_status($id);
			    $status  = $_status['status_slug'];
			    if($status){
			      $user_sql = "SELECT user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE status = '$status' ";
			      $user_ids = array();
			      $users = $wpdb->get_results($user_sql);
			      if(!empty($users)){
			        foreach ($users as $value) {
			          $user_ids[] = $value->user_id;
			        }
			        wc_crm_change_customer_status('Customer', $user_ids);
			      }
			    }
			}

			$table = $wpdb->prefix . "wc_crm_statuses";
			$sql = "DELETE FROM $table $filter";
			$wpdb->query($sql);
		}
		wc_crm_clear_transient();
	}

}

endif;

return new WC_Crm_Settings_Customer_Status();
