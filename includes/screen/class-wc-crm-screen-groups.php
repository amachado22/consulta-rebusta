<?php
/**
 * Class for E-mail handling.
 *
  * @author     Adailton Machado
 * @package  WC_CRM
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CRM_Screen_Groups {

	/**
	 * Handles output of the Customer Groups page in admin.
	 *
	 * Shows the created groups and lets you add new ones or edit existing ones.
 	 * The added groups are stored in the database and can be used for layered navigation.
	 */
	public static function do_actions() {
		
		global $wpdb;
		// Action to perform: add, edit, delete or none
		$action = '';
		if ( ! empty( $_POST['wc_crm_add_new_group'] ) ) {
			$action = 'add';
		} elseif ( ! empty( $_POST['wc_crm_save_group'] ) && ! empty( $_GET['id'] ) ) {
			$action = 'edit';
		} elseif ( !empty( $_GET['action'] ) && $_GET['action'] == 'delete' ) {
			$action = 'delete';
		}
		elseif ( ( !empty( $_POST['action'] ) && $_POST['action'] == 'delete' ) || ( !empty( $_POST['action2'] ) && $_POST['action2'] == 'delete' ) ) {
			$action = 'delete_groups';
		}

		// Add or edit an group
		if ( 'add' === $action || 'edit' === $action ) {

			// Security check
			if ( 'add' === $action ) {
				check_admin_referer( 'wc-crm-add-new-group' );
			}
			if ( 'edit' === $action ) {
				$group_id = absint( $_GET['id'] );
			}

			// Grab the submitted data
			$group_name               = ( isset( $_POST['group_name'] ) )   ? (string) stripslashes( $_POST['group_name'] ) : '';
			$group_slug               = ( isset( $_POST['group_slug'] ) )    ? wc_sanitize_taxonomy_name( stripslashes( (string) $_POST['group_slug'] ) ) : '';
			$group_type               = ( isset( $_POST['group_type'] ) )    ? (string) stripslashes( $_POST['group_type'] ) : '';
			$group_match              = ( isset( $_POST['group_match'] ) )    ? (int) $_POST['group_match'] : 0;
			$group_total_spent_mark   = ( isset( $_POST['group_total_spent_mark'] ) )    ? (string) stripslashes( $_POST['group_total_spent_mark'] ) : '';
			$group_total_spent        = ( isset( $_POST['group_total_spent'] ) )    ? (string) stripslashes( $_POST['group_total_spent'] ) : '';
			$group_user_role          = ( isset( $_POST['group_user_role'] ) )    ? (string) stripslashes( $_POST['group_user_role'] ) : '';
			$group_customer_status    = ( isset( $_POST['group_customer_status'] ) )    ? $_POST['group_customer_status'] : array();
			$group_product_categories = ( isset( $_POST['group_product_categories'] ) ) ? $_POST['group_product_categories'] : array();
			$group_order_status       = ( isset( $_POST['group_order_status'] ) ) ? $_POST['group_order_status'] : array();
			$group_last_order         = ( isset( $_POST['group_last_order'] ) )    ? (string) stripslashes( $_POST['group_last_order'] ) : '';
			$group_last_order_from    = ( isset( $_POST['group_last_order_from'] ) )    ? (string) stripslashes( $_POST['group_last_order_from'] ) : '';
			$group_last_order_to      = ( isset( $_POST['group_last_order_to'] ) ) ? (string) stripslashes( $_POST['group_last_order_to'] ) : '';


			// Auto-generate the label or slug if only one of both was provided
			if ( ! $group_name && $group_slug ) {
				$group_name = ucfirst( $group_slug );
			}
			if ( ! $group_slug && $group_name) {
				$group_slug = wc_sanitize_taxonomy_name( stripslashes( $group_name ) );
			}

			// Forbidden group names
			// http://codex.wordpress.org/Function_Reference/register_taxonomy#Reserved_Terms
			$reserved_terms = array(
				'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category', 'category__and',
				'category__in', 'category__not_in', 'category_name', 'comments_per_page', 'comments_popup', 'cpage', 'day',
				'debug', 'error', 'exact', 'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name',
				'nav_menu', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm',
				'post', 'post__in', 'post__not_in', 'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type',
				'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence',
				'showposts', 'static', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id',
				'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'type', 'w', 'withcomments', 'withoutcomments', 'year',
			);

			// Error checking
			if('add' === $action){
				if ( ! $group_name || ! $group_slug || ! $group_type ) {					
					$error = __( 'Please, provide a group name, slug and type.', 'wc_crm' );
				} elseif ( strlen( $group_name ) >= 28 ) {
					$error = sprintf( __( 'Slug “%s” is too long (28 characters max). Shorten it, please.', 'woocommerce' ), sanitize_title( $group_name ) );
				} elseif ( in_array( $group_name, $reserved_terms ) ) {
					$error = sprintf( __( 'Slug “%s” is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), sanitize_title( $group_name ) );
				} elseif ( in_array( $group_name, $reserved_terms ) ) {
					$error = sprintf( __( 'Slug “%s” is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), sanitize_title( $group_name ) );
				} else {
					$group_exists = wc_crm_group_exists( $group_slug );

					if ( 'add' === $action && $group_exists ) {
						$error = sprintf( __( 'Slug “%s” is already in use. Change it, please.', 'woocommerce' ), sanitize_title( $group_name ) );
					}
				}
			}
			/*if ( $group_type == 'dynamic' ) {
				if( ! $group_total_spent ){
					$error = __( 'Please, provide a Total Spent.', 'wc_crm' );
				}else if( $group_last_order == 'between' && (!$group_last_order_from || !$group_last_order_to) ){
					$error = __( 'Please, provide a Date.', 'wc_crm' );
				}else if( $group_last_order != 'between' && !$group_last_order_from ){
					$error = __( 'Please, provide a Date.', 'wc_crm' );
				}
			}*/

			// Show the error message if any
			if ( ! empty( $error ) ) {
				wc_crm_add_notice( $error, 'error' );
			} else {

				// Add new group
				$group = array(
						'group_type'               => $group_type,
						'group_match'              => $group_match,
						'group_total_spent_mark'   => $group_total_spent_mark,
						'group_total_spent'        => $group_total_spent,
						'group_user_role'          => $group_user_role,
						'group_customer_status'    => serialize($group_customer_status),
						'group_product_categories' => serialize($group_product_categories),
						'group_order_status'       => serialize($group_order_status),
						'group_last_order'         => $group_last_order,
						'group_last_order_from'    => $group_last_order_from,
						'group_last_order_to'      => $group_last_order_to
					);
				if ( 'add' === $action ) {

					$group['group_slug'] = $group_slug;
					$group['group_name'] = $group_name;

					$wpdb->insert( $wpdb->prefix . 'wc_crm_groups', $group );

					do_action( 'wc_crm_group_added', $wpdb->insert_id, $group );
					wc_crm_add_notice( __( 'Group successfully added.', 'wc_crm' ), 'success' );
				}

				// Edit existing group
				if ( 'edit' === $action ) {

					$wpdb->update( $wpdb->prefix . 'wc_crm_groups', $group, array( 'ID' => $group_id ) );
					

					do_action( 'wc_crm_group_updated', $group_id, $group);
					wc_crm_add_notice( __( 'Group successfully updated.', 'wc_crm' ), 'success' );
				}

				flush_rewrite_rules();
			}
		}

		// Delete an group
		if ( 'delete' === $action ) {
			// Security check
			$group_id = absint( $_GET['id'] );

			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_crm_groups WHERE ID = $group_id" );

			do_action( 'wc_crm_group_deleted', $group_id);
			wc_crm_add_notice( __('Group deleted', 'wc_crm'), 'success' );
		}

		// Delete an groups

		if ( 'delete_groups' === $action ) {
			// Security check
			$ids = $_POST['id'];
			$count_groups = count($ids);
			$ids = implode(',', $ids);
			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_crm_groups WHERE ID IN ($ids)" );

			do_action( 'wc_crm_group_deleted', $group_id);
			wc_crm_add_notice( sprintf( _n( '%d Groups deleted.', '%d Groups deleted.', $count_groups, 'wc_crm' ), $count_groups ) , 'success' );
		}		
	}

	/**
	 * Edit group admin panel
	 *
	 * Shows the interface for changing an groups type between select and text
	 */
	public static function edit_group() {
		global $wpdb;
		$id = absint( $_GET['id'] );

		$group_to_edit = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "wc_crm_groups WHERE ID = '$id'");

		$group_name 	          = $group_to_edit->group_name;
		$group_type               = ( isset( $_POST['group_type'] ) )     ? (string) stripslashes( $_POST['group_type'] ) : $group_to_edit->group_type;
		$group_match              = (int)( isset( $_POST['group_match'] ) ? $_POST['group_match'] : $group_to_edit->group_match);
		$group_total_spent_mark   = ( isset( $_POST['group_total_spent_mark'] ) )    ? (string) stripslashes( $_POST['group_total_spent_mark'] ) : $group_to_edit->group_total_spent_mark;
		$group_total_spent        = ( isset( $_POST['group_total_spent'] ) )    ? (string) stripslashes( $_POST['group_total_spent'] ) : $group_to_edit->group_total_spent;
		$group_user_role          = ( isset( $_POST['group_user_role'] ) )    ? (string) stripslashes( $_POST['group_user_role'] ) : $group_to_edit->group_user_role;

		$group_customer_status    = ( isset( $_POST['group_customer_status'] ) )    ? $_POST['group_customer_status'] : unserialize($group_to_edit->group_customer_status);
		$group_product_categories = ( isset( $_POST['group_product_categories'] ) ) ? $_POST['group_product_categories'] : unserialize($group_to_edit->group_product_categories);
		$group_order_status       = ( isset( $_POST['group_order_status'] ) ) ? $_POST['group_order_status'] : unserialize($group_to_edit->group_order_status);

		$group_last_order         = ( isset( $_POST['group_last_order'] ) )    ? (string) stripslashes( $_POST['group_last_order'] ) : $group_to_edit->group_last_order;
		$group_last_order_from    = ( isset( $_POST['group_last_order_from'] ) )    ? (string) stripslashes( $_POST['group_last_order_from'] ) : $group_to_edit->group_last_order_from;
		$group_last_order_to      = ( isset( $_POST['group_last_order_to'] ) ) ? (string) stripslashes( $_POST['group_last_order_to'] ) : $group_to_edit->group_last_order_to;

		if(!is_array($group_customer_status))
			$group_customer_status = array();

		if(!is_array($group_product_categories))
			$group_product_categories = array();

		if(!is_array($group_order_status))
			$group_order_status = array();

		?>
		<div class="wrap woocommerce">
			<div class="icon32 icon32-groups" id="icon-woocommerce"><br/></div>
		    <h2><?php _e( 'Edit group', 'wc_crm' ) ?></h2>
		    <?php wc_crm_print_notices(); ?>
			<p><?php _e( 'Groups are used to organise your customers.', 'wc_crm' ) ?></p>
			<form action="" method="post">
				<input type="hidden" name="wc_crm_edit_group" value="<?php echo $id; ?>">
				<table class="form-table">
					<tbody>
								<tr class="form-field">
									<td>
										<label for="f_group_name"><?php _e( 'Name', 'woocommerce' ); ?></label>
									</td>
									<td>
										<?php echo  $group_name; ?>
									</td>
								</tr>

								<tr class="form-field">
									<td>
										<label for="f_group_type"><?php _e( 'Type', 'wc_crm' ); ?></label>
									</td>
									<td>
										<select name="group_type" id="f_group_type">
                                            <option value="static" <?php selected( $group_type, 'static' ); ?> ><?php _e( 'Static', 'wc_crm' ) ?></option>
											<option value="dynamic" <?php selected( $group_type, 'dynamic' ); ?> ><?php _e( 'Dynamic', 'wc_crm' ) ?></option>
											<?php do_action('wc_crm_customer_group_types', $group_to_edit); ?>
										</select>
										<p class="description"><?php _e( 'Determines how you select group for customers.', 'wc_crm' ); ?></p>
									</td>
								</tr>
								<tr class="form-field dynamic_group_type">
									<td>
									</td>
									<td>
										<?php _e( 'Match', 'wc_crm' ); ?>
										<select name="group_match" id="group_match">
											<option value="0" <?php selected( $group_match, 0 ); ?>><?php _e( 'all', 'wc_crm' ); ?></option>
											<option value="1" <?php selected( $group_match, 1 ); ?>><?php _e( 'any', 'wc_crm' ); ?></option>
										</select>
										 <?php _e( 'of the following rules:', 'wc_crm' ); ?> 
									</td>
								</tr>
								<?php do_action('wc_crm_before_dynamic_group_type_fields', $group_to_edit); ?>
								<tr class="form-field dynamic_group_type">
									<td>
										<label for="group_total_spent"><?php _e( 'Total Spent', 'wc_crm' ); ?></label>
									</td>
									<td>
										<select name="group_total_spent_mark" id="group_total_spent_mark">
											<option value="equal" <?php selected( $group_total_spent_mark, 'equal' ); ?>><?php _e( '=', 'wc_crm' ) ?></option>
											<option value="greater" <?php selected( $group_total_spent_mark, 'greater' ); ?>><?php _e( '&gt;', 'wc_crm' ) ?></option>
											<option value="less" <?php selected( $group_total_spent_mark, 'less' ); ?>><?php _e( '&lt;', 'wc_crm' ) ?></option>
											<option value="greater_or_equal" <?php selected( $group_total_spent_mark, 'greater_or_equal' ); ?>><?php _e( '&ge;', 'wc_crm' ) ?></option>
											<option value="less_or_equal" <?php selected( $group_total_spent_mark, 'less_or_equal' ); ?>><?php _e( '&le;', 'wc_crm' ) ?></option>
										</select>
										<input type="number" step="any" id="group_total_spent" name="group_total_spent" value="<?php echo  $group_total_spent; ?>">
									</td>
								</tr>

								<tr class="form-field dynamic_group_type">
									<td>
										<label for="group_user_role"><?php _e( 'User Role', 'wc_crm' ); ?></label>
									</td>
									<td>
										<select name="group_user_role" id="group_user_role" class="wc-enhanced-select">
											<option value="any">
						            		<?php _e( 'Any', 'wc_crm' ); ?>
					            			</option>
											<option value="guest" <?php selected( $group_user_role, 'guest', true); ?>>
						            		<?php _e( 'Guest', 'wc_crm' ); ?>
				            				</option>
											<?php
									          global $wp_roles;
									          foreach ( $wp_roles->role_names as $role => $name ) : ?>
									            <option value="<?php echo strtolower($name); ?>" <?php selected( $group_user_role, strtolower($name) ); ?>>
									            	<?php _e( $name, 'wc_crm' ); ?>
									            </option>
									          <?php
									          endforeach;
									          ?>
										</select>
									</td>
								</tr>
								<tr class="form-field dynamic_group_type">
									<td>
										<label for="group_customer_status"><?php _e( 'Customer Status', 'wc_crm' ); ?></label>
									</td>
									<td>
										<select name="group_customer_status[]" id="group_customer_status" multiple="multiple" data-placeholder="<?php _e( 'Choose a Customer Status...', 'wc_crm' ); ?>"  class="wc-enhanced-select">
											<?php
												$statuses = wc_crm_get_statuses();
									          foreach ( $statuses as $status ) : ?>
									            <option value="<?php echo strtolower($status->status_slug); ?>" <?php echo in_array(strtolower($status->status_slug), $group_customer_status) ? 'selected="selected"' : ''; ?> >
									            	<?php echo $status->status_name; ?>
									            </option>
									          <?php
									          endforeach;
									          ?>
										</select>
									</td>
								</tr>
								<tr class="form-field dynamic_group_type">
									<td>
										<label for="group_product_categories"><?php _e( 'Product Category', 'wc_crm' ); ?></label>
									</td>
									<td>
										<select name="group_product_categories[]" id="group_product_categories" multiple="multiple" data-placeholder="<?php _e( 'Choose a Product Category...', 'wc_crm' ); ?>"  class="wc-enhanced-select">
										<?php
								          $all_cat = get_terms( array('product_cat'),  array( 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false, )  );
								          if(!empty($all_cat)){
								            foreach ($all_cat as $cat) {?>
								            	<option value="<?php echo $cat->term_id; ?>" <?php echo in_array($cat->term_id, $group_product_categories) ? 'selected="selected"' : ''; ?>>
								            		<?php echo $cat->name; ?>
								            	</option>
								          	<?php
								            }
								          }
								          ?>
									</select>
									</td>
								</tr>
								<tr class="form-field dynamic_group_type">
									<td>
										<label for="group_order_status"><?php _e( 'Order Status', 'wc_crm' ); ?></label>
									</td>
									<td>
									<select name="group_order_status[]" id="group_order_status" multiple="multiple" data-placeholder="<?php _e( 'Choose a Product Category...', 'wc_crm' ); ?>"  class="wc-enhanced-select">
										<?php
								          $wc_statuses = wc_get_order_statuses();
								          if(!empty($wc_statuses)){
								            foreach ($wc_statuses as $key => $status_name) {?>
								            	<option value="<?php echo $key; ?>" <?php echo in_array($key, $group_order_status) ? 'selected="selected"' : ''; ?>>
								            		<?php echo $status_name; ?>
								            	</option>
								          	<?php
								            }
								          }
								          ?>
									</select>
									</td>
								</tr>
								<tr class="form-field dynamic_group_type">
									<td>
										<label for="group_last_order"><?php _e( 'Last Order', 'wc_crm' ); ?></label>
									</td>
									<td>
										<div class="wrap_date">
											<select name="group_last_order" id="group_last_order">
												<option value="between" <?php selected( $group_last_order, 'between' ); ?>><?php _e( 'Between', 'wc_crm' ); ?></option>
												<option value="before"  <?php selected( $group_last_order, 'before' ); ?>><?php _e( 'Before', 'wc_crm' ); ?></option>
												<option value="after"   <?php selected( $group_last_order, 'after' ); ?>><?php _e( 'After', 'wc_crm' ); ?></option>
											</select>
										</div>
										<div class="wrap_date">
											<input type="text" id="group_last_order_from" name="group_last_order_from" value="<?php echo $group_last_order_from; ?>">
											<i class="ico_calendar"></i>
										</div>
										<div class="wrap_date group_last_order_between" style="height: 30px; line-height: 30px; padding: 0 10px;">
											to
										</div>
										<div class="wrap_date group_last_order_between">
											<input type="text" id="group_last_order_to" name="group_last_order_to" value="<?php echo $group_last_order_to; ?>">
											<i class="ico_calendar"></i>
										</div>
										<div class="clear"></div>
									</td>
								</tr>
								<?php do_action('wc_crm_after_dynamic_group_type_fields', $group_to_edit); ?>								
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="wc_crm_save_group" id="submit" class="button-primary" value="<?php _e( 'Update', 'woocommerce' ); ?>"></p>

			</form>
		</div>
		<?php
	}
	/**
	 * Add group admin panel
	 *
	 * Shows the interface for adding new groups
	 */
	public  static function add_group() {
		if( wc_crm_notice_count('error') == 0){
			$_POST = array();
		}
		// Grab the submitted data		
		$group_name               = ( isset( $_POST['group_name'] ) ) ? (string) stripslashes( $_POST['group_name'] ) : '';
		$group_slug               = ( isset( $_POST['group_slug'] ) ) ? wc_sanitize_taxonomy_name( stripslashes( (string) $_POST['group_slug'] ) ) : '';
		$group_type               = ( isset( $_POST['group_type'] ) ) ? (string) stripslashes( $_POST['group_type'] ) : '';
		$group_match              = ( isset( $_POST['group_match'] ) ) ? (int) $_POST['group_match'] : 0;
		$group_total_spent_mark   = ( isset( $_POST['group_total_spent_mark'] ) ) ? (string) stripslashes( $_POST['group_total_spent_mark'] ) : '';
		$group_total_spent        = ( isset( $_POST['group_total_spent'] ) ) ? (string) stripslashes( $_POST['group_total_spent'] ) : '';
		$group_user_role          = ( isset( $_POST['group_user_role'] ) ) ? (string) stripslashes( $_POST['group_user_role'] ) : '';
		$group_customer_status    = ( isset( $_POST['group_customer_status'] ) ) ? $_POST['group_customer_status'] : array();
		$group_product_categories = ( isset( $_POST['group_product_categories'] ) ) ? $_POST['group_product_categories'] : array();
		$group_order_status       = ( isset( $_POST['group_order_status'] ) ) ? $_POST['group_order_status'] : array();
		$group_last_order         = ( isset( $_POST['group_last_order'] ) ) ? (string) stripslashes( $_POST['group_last_order'] ) : '';
		$group_last_order_from    = ( isset( $_POST['group_last_order_from'] ) ) ? (string) stripslashes( $_POST['group_last_order_from'] ) : '';
		$group_last_order_to      = ( isset( $_POST['group_last_order_to'] ) ) ? (string) stripslashes( $_POST['group_last_order_to'] ) : '';

		?>
		<div class="wrap woocommerce">
			<div class="icon32 icon32-groups" id="icon-woocommerce"><br/></div>
		    <h2><?php _e( 'Customer Groups', 'woocommerce' ) ?></h2>
		    <?php wc_crm_print_notices(); ?>
		    <br class="clear" />
		    <div id="col-container">
		    	<div id="col-right">
		    		<div class="col-wrap">
			    		<form action="admin.php?page=wc_crm-groups" method="post">
			    			<?php
				    			WC_CRM()->tables['groups']->prepare_items();
				    			WC_CRM()->tables['groups']->display();
				    		?>
			    		</form>
		    		</div>
		    	</div>
		    	<div id="col-left">
		    		<div class="col-wrap">
		    			<div class="form-wrap">
		    				<h3><?php _e( 'Add New group', 'wc_crm' ) ?></h3>
		    				<p><?php _e( 'Groups are used to organise your customers. Please Note: you cannot rename a group later.', 'wc_crm' ) ?></p>
		    				<form action="admin.php?page=wc_crm-groups" method="post" style=" padding-bottom: 150px;">
								<div class="form-field">
									<label for="f_group_name"><?php _e( 'Name', 'woocommerce' ); ?></label>
									<input name="group_name" id="f_group_name" type="text" value="<?php echo  $group_name; ?>" />
									<p class="description"><?php _e( 'Name for the group.', 'wc_crm' ); ?></p>
								</div>

								<div class="form-field">
									<label for="f_group_slug"><?php _e( 'Slug', 'woocommerce' ); ?></label>
									<input name="group_slug" id="f_group_slug" type="text" value="<?php echo  $group_slug; ?>" maxlength="28" />
									<p class="description"><?php _e( 'Unique slug/reference for the group; must be shorter than 28 characters.', 'wc_crm' ); ?></p>
								</div>

								<div class="form-field">
									<label for="f_group_type"><?php _e( 'Type', 'wc_crm' ); ?></label>
									<select name="group_type" id="f_group_type">
                                        <option value="static" <?php selected( $group_type, 'static' ); ?> ><?php _e( 'Static', 'wc_crm' ) ?></option>
										<option value="dynamic" <?php selected( $group_type, 'dynamic' ); ?> ><?php _e( 'Dynamic', 'wc_crm' ) ?></option>
										<?php do_action('wc_crm_customer_group_types'); ?>
									</select>
									<p class="description"><?php _e( 'Determines how you select group for customers.', 'wc_crm' ); ?></p>
								</div>
								<div class="form-field dynamic_group_type">
									<?php _e( 'Match', 'wc_crm' ) ?>
										<select name="group_match" id="group_match">
											<option value="0" <?php selected( $group_match, 0 ); ?>><?php _e( 'all', 'wc_crm' ) ?></option>
											<option value="1" <?php selected( $group_match, 1 ); ?>><?php _e( 'any', 'wc_crm' ) ?></option>
										</select>
										<?php _e( 'of the following rules:', 'wc_crm' ) ?>
								</div>
								<?php do_action('wc_crm_before_dynamic_group_type_fields', 0); ?>
								<div class="form-field dynamic_group_type">
									<label for="group_total_spent"><?php _e( 'Total Spent', 'wc_crm' ); ?></label>
									<select name="group_total_spent_mark" id="group_total_spent_mark">
										<option value="equal" <?php selected( $group_total_spent_mark, 'equal' ); ?>><?php _e( '=', 'wc_crm' ) ?></option>
										<option value="greater" <?php selected( $group_total_spent_mark, 'greater' ); ?>><?php _e( '&gt;', 'wc_crm' ) ?></option>
										<option value="less" <?php selected( $group_total_spent_mark, 'less' ); ?>><?php _e( '&lt;', 'wc_crm' ) ?></option>
										<option value="greater_or_equal" <?php selected( $group_total_spent_mark, 'greater_or_equal' ); ?>><?php _e( '&ge;', 'wc_crm' ) ?></option>
										<option value="less_or_equal" <?php selected( $group_total_spent_mark, 'less_or_equal' ); ?>><?php _e( '&le;', 'wc_crm' ) ?></option>
									</select>
									<input type="number" step="any" id="group_total_spent" name="group_total_spent" value="<?php echo  $group_total_spent; ?>">
								</div>
								<div class="form-field dynamic_group_type">
									<label for="group_user_role"><?php _e( 'User Role', 'wc_crm' ); ?></label>
									<select name="group_user_role" id="group_user_role"  class="wc-enhanced-select">
										<option value="any">
								            	<?php _e( 'Any', 'wc_crm' ); ?>
							            </option>
													<option value="guest">
								            	<?php _e( 'Guest', 'wc_crm' ); ?>
							            </option>
													<?php
								          global $wp_roles;
								          foreach ( $wp_roles->role_names as $role => $name ) : ?>
								            <option value="<?php echo strtolower($name); ?>" <?php selected( $group_user_role, strtolower($name) ); ?>>
								            	<?php _e( $name, 'wc_crm' ); ?>
								            </option>
								          <?php
								          endforeach;
								          ?>
									</select>
								</div>
								<div class="form-field dynamic_group_type">
									<label for="group_customer_status"><?php _e( 'Customer Status', 'wc_crm' ); ?></label>
									<select name="group_customer_status[]" id="group_customer_status" multiple="multiple" data-placeholder="<?php _e( 'Choose a Customer Status...', 'wc_crm' ); ?>" class="wc-enhanced-select">
										<?php
								          $statuses = wc_crm_get_statuses();
								          foreach ( $statuses as $status ) : ?>
								            <option value="<?php echo strtolower($status->status_slug); ?>" <?php echo in_array(strtolower($status->status_slug), $group_customer_status) ? 'selected="selected"' : ''; ?>>
								            	<?php echo $status->status_name ?>
								            </option>
								          <?php
								          endforeach;
								          ?>
									</select>
								</div>
								<div class="form-field dynamic_group_type">
									<label for="group_product_categories"><?php _e( 'Product Category', 'wc_crm' ); ?></label>
									<select name="group_product_categories[]" id="group_product_categories" multiple="multiple" data-placeholder="<?php _e( 'Choose a Product Category...', 'wc_crm' ); ?>" class="wc-enhanced-select">
										<?php
							          $all_cat = get_terms( array('product_cat'),  array( 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false, )  );
							          if(!empty($all_cat)){
							            foreach ($all_cat as $cat) {?>
							            	<option value="<?php echo $cat->term_id; ?>" <?php echo in_array($cat->term_id, $group_product_categories) ? 'selected="selected"' : ''; ?>>
							            		<?php echo $cat->name; ?>
							            	</option>
							          	<?php
							            }
							          }
							          ?>
									</select>
								</div>
								<div class="form-field dynamic_group_type">
									<label for="group_order_status"><?php _e( 'Order Status', 'wc_crm' ); ?></label>
									<select name="group_order_status[]" id="group_order_status" multiple="multiple" data-placeholder="<?php _e( 'Choose a Product Category...', 'wc_crm' ); ?>" class="wc-enhanced-select">
										<?php
					          $wc_statuses = wc_get_order_statuses();
					          if(!empty($wc_statuses)){
					            foreach ($wc_statuses as $key => $status_name) {?>
					            	<option value="<?php echo $key; ?>" <?php echo in_array($key, $group_order_status) ? 'selected="selected"' : ''; ?>>
					            		<?php echo $status_name; ?>
					            	</option>
					          	<?php
					            }
					          }
					          ?>
									</select>
								</div>
								<div class="form-field dynamic_group_type">
									<label for="group_last_order"><?php _e( 'Last Order', 'wc_crm' ); ?></label>
									<div class="wrap_date">
										<select name="group_last_order" id="group_last_order">
											<option value="between" <?php selected( $group_last_order, 'between' ); ?>><?php _e( 'Between', 'wc_crm' ); ?></option>
											<option value="before"  <?php selected( $group_last_order, 'before' ); ?>><?php _e( 'Before', 'wc_crm' ); ?></option>
											<option value="after"   <?php selected( $group_last_order, 'after' ); ?>><?php _e( 'After', 'wc_crm' ); ?></option>
										</select>
									</div>
									<div class="wrap_date">
										<input type="text" id="group_last_order_from" name="group_last_order_from" value="<?php echo $group_last_order_from; ?>">
										<i class="ico_calendar"></i>
									</div>
									<div class="wrap_date group_last_order_between" style="height: 30px; line-height: 30px; padding: 0 10px;">
										to
									</div>
									<div class="wrap_date group_last_order_between">
										<input type="text" id="group_last_order_to" name="group_last_order_to" value="<?php echo $group_last_order_to; ?>">
										<i class="ico_calendar"></i>
									</div>
									<div class="clear"></div>
								</div>

								<p class="submit"><input type="submit" name="wc_crm_add_new_group" id="submit" class="button" value="<?php _e( 'Add group', 'wc_crm' ); ?>"></p>
								<?php wp_nonce_field( 'wc-crm-add-new-group' ); ?>
		    				</form>
		    			</div>
		    		</div>
		    	</div>
		    </div>
		    <script type="text/javascript">
			/* <![CDATA[ */

				jQuery('a.delete').click(function(){
		    		var answer = confirm ("<?php _e( 'Are you sure you want to delete this group?', 'wc_crm' ); ?>");
					if (answer) return true;
					return false;
		    	});

			/* ]]> */
			</script>
		</div>
		<?php
	}

	public static function output()
	{
		self::do_actions();
		// Show admin interface
		if ( !empty( $_GET['action'] ) && $_GET['action'] == 'edit'){
			self::edit_group();
		}
		else{
			self::add_group();
		}
	}

}
