<?php
/**
 * Post Types Admin
 *
  * @author     Adailton Machado
 * @category Admin
 * @package  WC_CRM_Admin/Admin
 * @version  1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Admin_Post_Actions')) :

    /**
     * WC_CRM_Admin_Post_Actions Class
     *
     * Handles the edit posts views and some functionality on the edit post screen for WC post types.
     */
    class WC_CRM_Admin_Post_Actions
    {

        private static $saved_meta_boxes = false;
        private static $reload_user = null;
        private static $reload_guest = null;

        /**
         * Constructor
         */
        public function __construct()
        {
            add_action('admin_init', array($this, 'customer_actions'));
            add_action('admin_init', array($this, 'activity_actions'));

            add_action('post_updated', array($this, 'reload_customers_before_update_post'), 888, 2);
            add_action('save_post', array($this, 'update_shop_order'), 888, 2);
            add_action('profile_update', array($this, 'profile_update'), 888, 1);
            add_action('set_user_role', array($this, 'set_customer_user_role'), 99, 3);
            add_action('user_register', array($this, 'user_register'), 888, 1);
            add_action('delete_user', array($this, 'delete_customer'), 888, 1);
            add_action('before_delete_post', array($this, 'delete_guest'), 888, 1);
            add_action('woocommerce_created_customer', array($this, 'user_register'), 888, 1);
            add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'), 888, 1);
            add_action('woocommerce_save_account_details', array($this, 'profile_update'), 888, 1);
            add_action('woocommerce_admin_order_data_after_order_details', array($this, 'select_customer_id'));
            add_action('restrict_manage_posts', array($this, 'tasks_status_filter'), 888, 2);
            add_action('parse_query', array($this, 'wc_crm_parse_query'));
        }

        public function customer_actions()
        {
            $pages = array(WC_CRM_TOKEN, WC_CRM_TOKEN . '-new-customer');

            if (!isset($_GET['page']) || !in_array($_GET['page'], $pages)) {
                return false;
            }

            $id = isset($_GET['c_id']) ? intval($_GET['c_id']) : 0;
            if ($id > 0) {
                $the_customer = new WC_CRM_Customer($id);
                if ($the_customer->status == 'trashed') {
                    wp_die(__('You can&#8217;t edit this item because it is in the Trash. Please restore it and try again.'));
                }
            }

            $this->customer_process_bulk_action();
            if (isset($_REQUEST['wc_crm_customer_action'])) {
                $action = $_REQUEST['wc_crm_customer_action'];
                switch ($action) {
                    case 'trash':
                        WC_CRM_Screen_Customers_Edit::move_to_trash();
                        break;
                    case 'untrash':
                        WC_CRM_Screen_Customers_Edit::untrash();
                        break;
                    case 'delete':
                        WC_CRM_Screen_Customers_Edit::delete();
                        break;
                    case 'sent_email':
                        WC_CRM_Screen_Activity::process_email_form();
                        break;
                    case 'create_customer':
                        WC_CRM_Screen_Customers_Edit::create_user();
                        wc_crm_clear_transient();
                        break;

                    default:
                        if (isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
                            WC_CRM_Screen_Customers_Edit::save($_POST['customer_id']);
                            wc_crm_clear_transient();
                        }
                        break;
                }

            }
            if (isset($_REQUEST['trashed'])) {
                $customer_ids = explode(',', $_REQUEST['trashed']);
                wc_crm_add_notice(sprintf(_n('%s customer moved to the Trash.', '%s customers moved to the Trash.', count($customer_ids), 'wc_crm'), count($customer_ids)));
            }
            if (isset($_REQUEST['untrashed'])) {
                $customer_ids = explode(',', $_REQUEST['untrashed']);
                wc_crm_add_notice(sprintf(_n('%s customer restored from the Trash.', '%s customers restored from the Trash.', count($customer_ids), 'wc_crm'), count($customer_ids)));
            }
            if (isset($_REQUEST['deleted'])) {
                $customer_ids = explode(',', $_REQUEST['deleted']);
                wc_crm_add_notice(sprintf(_n('%s customer permanently deleted.', '%s customers permanently deleted.', count($customer_ids), 'wc_crm'), count($customer_ids)));
            }
        }

        public function set_customer_user_role($id, $role, $old_role)
        {
            wc_crm_reload_customer($id);
            wc_crm_clear_transient();
        }

        public function activity_actions()
        {
            $pages = array(WC_CRM_TOKEN . '-logs', WC_CRM_TOKEN, WC_CRM_TOKEN . '-new-customer');

            if (!isset($_GET['page']) || !in_array($_GET['page'], $pages)) {
                return false;
            }

            if (isset($_POST['delete_all'])) {
                $this->empty_trash();
            }
            $this->activity_process_bulk_action();
        }

        public function reload_customers_before_update_post($post_id, $post)
        {
            // $post_id and $post are required
            if (empty($post_id) || empty($post)) {
                return;
            }

            // Dont' save meta boxes for revisions or autosaves
            if (defined('DOING_AUTOSAVE') || is_int(wp_is_post_revision($post)) || is_int(wp_is_post_autosave($post))) {
                return;
            }

            // Check the nonce
            if (empty($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce($_POST['woocommerce_meta_nonce'], 'woocommerce_save_data')) {
                return;
            }

            // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
            if (empty($_POST['post_ID']) || $_POST['post_ID'] != $post_id) {
                return;
            }

            $user_id = (int)get_post_meta($post_id, '_customer_user', true);
            $user_email = get_post_meta($post_id, '_billing_email', true);

            global $wpdb;
            if ($user_id > 0 && $user_id != $_POST['customer_user']) {

                self::$reload_user = $user_id;

            } else if (!empty($user_email) && $user_email != $_POST['_billing_email']) {

                self::$reload_guest = $user_email;

            }

            wc_crm_clear_transient();

        }

        public function update_shop_order($post_id, $post)
        {

            // $post_id and $post are required
            if (empty($post_id) || empty($post) || self::$saved_meta_boxes) {
                return;
            }

            // Dont' save meta boxes for revisions or autosaves
            if (defined('DOING_AUTOSAVE') || is_int(wp_is_post_revision($post)) || is_int(wp_is_post_autosave($post))) {
                return;
            }

            // Check the nonce
            if (empty($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce($_POST['woocommerce_meta_nonce'], 'woocommerce_save_data')) {
                return;
            }

            // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
            if (empty($_POST['post_ID']) || $_POST['post_ID'] != $post_id) {
                return;
            }

            self::$saved_meta_boxes = true;

            global $wpdb;
            $user_id = (int)get_post_meta($post_id, '_customer_user', true);
            $user_email = get_post_meta($post_id, '_billing_email', true);

            if ($user_id > 0) {

                wc_crm_reload_customer($user_id);

            } else if (!empty($user_email)) {

                wc_crm_reload_guest($user_email);

            }

            if (!is_null(self::$reload_user)) {

                wc_crm_reload_customer(self::$reload_user);

            } else if (!is_null(self::$reload_guest)) {

                $em = self::$reload_guest;

                $count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->postmeta}
                WHERE meta_key   = '_billing_email'
                AND   meta_value = '{$em}'");

                if ($count > 0) {
                    wc_crm_reload_guest(self::$reload_guest, true);
                } else {
                    $sql = "DELETE FROM {$wpdb->prefix}wc_crm_customer_list
                    WHERE email = '{$em}' AND user_id = 0";
                    $wpdb->query($sql);
                }


            }

            if(isset($_POST['customer_user_status']) && !empty($_POST['customer_user_status'])){
                $order = wc_get_order($post_id);
                $customer = wc_crm_get_customer($order->get_user_id(), 'user_id');
                if($order->get_user_id() > 0){
                    $wpdb->update("{$wpdb->prefix}wc_crm_customer_list", array('status' => $_POST['customer_user_status']), array('c_id' => $customer->c_id));
                }
            }

            wc_crm_clear_transient();

        }

        public function order_status_changed($post_id)
        {

            // $post_id and $post are required
            if (empty($post_id)) {
                return;
            }

            global $wpdb;
            $user_id = (int)get_post_meta($post_id, '_customer_user', true);
            $user_email = get_post_meta($post_id, '_billing_email', true);

            if ($user_id > 0) {

                wc_crm_reload_customer($user_id);

            } else if (!empty($user_email)) {

                wc_crm_reload_guest($user_email);

            }

            wc_crm_clear_transient();

        }

        public function profile_update($user_id = '')
        {
            if (!$user_id || empty($user_id)) return;
            wc_crm_reload_customer($user_id);
            wc_crm_clear_transient();
        }

        public function user_register($user_id = '')
        {
            if (!$user_id || empty($user_id)) return;
            wc_crm_reload_customer($user_id);
            wc_crm_clear_transient();
        }

        public function delete_customer($user_id = 0)
        {
            if (!$user_id) return;
            global $wpdb;
            $sql = "DELETE FROM {$wpdb->prefix}wc_crm_customer_list
                WHERE user_id = {$user_id}
        ";
            $wpdb->query($sql);
            wc_crm_clear_transient();
        }

        public function delete_guest($postid)
        {
            global $post_type, $wpdb;
            if (!$postid) return;

            $order_types = wc_get_order_types('order-count');

            if (!in_array($post_type, $order_types)) return;

            $email = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta}
              WHERE post_id  = $postid
              AND   meta_key = '_billing_email'
              LIMIT 1
      ");


            if (!empty($email)) {
                $count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->postmeta}
                WHERE meta_key   = '_billing_email'
                AND   meta_value = '{$email}'
        ");

                if ($count == 1) {
                    $sql = "DELETE FROM {$wpdb->prefix}wc_crm_customer_list
                  WHERE email = '{$email}' AND user_id = 0
          ";
                    $wpdb->query($sql);
                }

            }
            wc_crm_clear_transient();
        }

        public function select_customer_id()
        {
            if (isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order' && isset($_GET['c_id']) && !empty($_GET['c_id'])) {
                global $post;
                $c_id = $_GET['c_id'];
                $the_customer = new WC_CRM_Customer($c_id);
                $favourites = get_user_meta($the_customer->user_id, 'favourite_products', true) ?: array() ;

                ob_start();
                if ($the_customer->user_id > 0) {
                    $c_name = $the_customer->first_name . ' ' . $the_customer->last_name . ' (#' . $the_customer->c_id . ' - ' . $the_customer->user_email . ')';
                    ?>
                    var data = {
                    id: <?php echo $the_customer->user_id; ?>,
                    text: "<?php echo $c_name; ?>"
                    };
                    var newOption = new Option(data.text, data.id, false, false);
                    jQuery("#customer_user").append(newOption);
                    $('#customer_user').val('<?php echo $the_customer->user_id; ?>');
                    $('#customer_user').trigger('change');
                    <?php
                }

                $the_customer->init_address_fields();

                $formatted_shipping_address = wp_kses($the_customer->get_formatted_shipping_address(), array("br" => array()));
                $formatted_billing_address = wp_kses($the_customer->get_formatted_billing_address(), array("br" => array()));

                $__b_address = $the_customer->billing_fields;
                $__s_address = $the_customer->shipping_fields;

                foreach ($__b_address as $key => $field) {
                    $var_name = 'billing_' . $key;
                    ?>
                    jQuery('#_billing_<?php echo $key; ?>').val( "<?php echo addslashes($the_customer->$var_name); ?>" );
                    <?php
                }
                foreach ($__s_address as $key => $field) {
                    $var_name = 'shipping_' . $key;
                    ?>
                    jQuery('#_shipping_<?php echo $key; ?>').val( "<?php echo addslashes($the_customer->$var_name); ?>" );
                    <?php
                }
                ?>
                jQuery('.order_data_column_container .order_data_column').last().find('.address')
                .html("<?php echo "<p><strong>" . __("Address", "woocommerce") . ":</strong>" . addslashes($formatted_shipping_address) . "</p>"; ?>");

                <?php if(!empty($favourites)) : ?>
                    var data = {
                    action     : 'woocommerce_add_order_item',
                    item_to_add: <?php echo  json_encode($favourites) ?>,
                    dataType   : 'json',
                    order_id   : woocommerce_admin_meta_boxes.post_id,
                    security   : woocommerce_admin_meta_boxes.order_item_nonce,
                    data       : jQuery( '#wc-backbone-modal-dialog form' ).serialize()
                    };

                    jQuery.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {
                    if ( response.success ) {
                    jQuery( '#woocommerce-order-items' ).find( '.inside' ).empty();
                    jQuery( '#woocommerce-order-items' ).find( '.inside' ).append( response.data.html );
                        //wc_meta_boxes_order_items.reloaded_items();
                    } else {
                        window.alert( response.data.error );
                    }
                        //wc_meta_boxes_order_items.unblock();
                    });
                <?php endif; ?>

                jQuery('.order_data_column_container .order_data_column').first().next().find('.address')
                .html("<?php echo "<p><strong>" . __("Address", "woocommerce") . ":</strong>" . addslashes($formatted_billing_address) . "</p>"; ?>");

                jQuery('.js_field-country').trigger('change');
                <?php

                $js_string = ob_get_contents();

                ob_end_clean();
                wc_enqueue_js($js_string);
            }
        }

        public function customer_process_bulk_action()
        {
            $wp_list_table = new WP_List_Table;
            $action = $wp_list_table->current_action();
            if ($action === false) return;
            switch ($action) {
                case 'trash':
                    if (!empty($_REQUEST['customer_id'])) {
                        WC_CRM_Screen_Customers_Edit::move_to_trash();
                    }
                    break;
                case 'untrash':
                    if (!empty($_REQUEST['customer_id'])) {
                        WC_CRM_Screen_Customers_Edit::untrash();
                    }
                    break;
                case 'delete':
                    if (!empty($_REQUEST['customer_id'])) {
                        WC_CRM_Screen_Customers_Edit::delete();
                    }
                    break;
                case 'email':
                    if (!empty($_REQUEST['customer_id'])) {
                        $ids = array();
                        foreach ($_REQUEST['customer_id'] as $key => $c_id) {
                            $ids[] = 'c_id[' . $key . ']=' . $c_id;
                        }

                        wp_redirect('admin.php?page=wc_crm&screen=email&' . implode('&', $ids));
                    }
                    break;
                case 'export_csv':
                    $ids = array();
                    if (!empty($_REQUEST['customer_id'])) {
                        $ids = $_REQUEST['customer_id'];
                    }
                    WC_CRM_Export::init($ids);
                    #wp_redirect( 'admin.php?page=wc_crm&screen=email&'.implode('&', $ids) );
                    break;

                default:
                    global $wpdb;
                    if (strrpos($action, 'mark_as_') === 0) {
                        if (!empty($_REQUEST['customer_id'])) {
                            $ids = $_REQUEST['customer_id'];
                            $statuses = wc_crm_get_statuses_slug();
                            $status = substr($action, 8);
                            foreach ($ids as $c_id) {
                                $user_id = $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE c_id = {$c_id} LIMIT 1 ");

                                if (array_key_exists($status, $statuses) && $user_id > 0) {
                                    update_user_meta($user_id, 'customer_status', $status);
                                }
                            }
                            if (array_key_exists($status, $statuses)) {
                                global $wpdb;
                                $sql = "UPDATE {$wpdb->prefix}wc_crm_customer_list
                        SET status = '{$status}'
                        WHERE c_id IN (" . implode(',', $ids) . ")
              ";
                                $wpdb->query($sql);
                                wc_crm_add_notice(__('Customer status updated.', 'wc_crm'), 'success');
                            }
                        }

                    } else if (strrpos($action, 'crm_add_to_group_') === 0) {
                        $ids = $_REQUEST['customer_id'];
                        if (count($ids) > 0) {
                            $group_id = substr($action, strlen('crm_add_to_group_'));
                            wc_crm_add_to_group($group_id, $ids);
                            $count = count($ids);
                            wc_crm_add_notice(sprintf(_n('%d customer added to the group.', '%d customers added to the group.', 'wc_crm'), $count), 'success');
                        }
                    }
                    wc_crm_clear_transient();
                    break;
            }
        }

        public function activity_process_bulk_action()
        {
            $wp_list_table = new WP_List_Table;
            $action = $wp_list_table->current_action();
            if ($action === false) return;

            switch ($action) {
                case 'trash':
                    if (!empty($_REQUEST['log'])) {
                        $ids = $_REQUEST['log'];
                        self::move_to_trash_activity($ids);
                    }
                    break;
                case 'untrash':
                    if (!empty($_REQUEST['log'])) {
                        $ids = $_REQUEST['log'];
                        self::untrash_data_activity($ids);
                    }
                    break;
                case 'delete':
                    if (!empty($_REQUEST['log'])) {
                        $ids = $_REQUEST['log'];
                        self::delete_data_activity($ids);
                    }
                    break;
            }
        }

        public function empty_trash()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "wc_crm_log";
            $count = (int)$wpdb->get_var("SELECT count(ID) FROM $table_name WHERE log_status = 'trash' ");
            if ($count) {
                $wpdb->query("DELETE FROM $table_name WHERE log_status = 'trash' ");
            }
            wc_crm_add_notice(sprintf(_n('%d post permanently deleted.', '%d posts permanently deleted.', $count, 'wc_crm'), $count), 'success');
        }

        public static function delete_data_activity($ids)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "wc_crm_log";
            if (is_array($ids)) {
                $count = count($ids);
                $id = implode(',', $ids);
                $wpdb->query("DELETE FROM $table_name WHERE ID IN({$id})");
            } else {
                $n_ids = explode(',', $ids);
                $count = count($n_ids);
                $wpdb->query("DELETE FROM $table_name WHERE ID IN({$ids})");
            }
            wc_crm_add_notice(sprintf(_n('%d post permanently deleted.', '%d posts permanently deleted.', $count, 'wc_crm'), $count), 'success');
        }

        public static function untrash_data_activity($ids)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "wc_crm_log";
            if (is_array($ids)) {
                $count = count($ids);
                $id = implode(',', $ids);
                $wpdb->query("UPDATE $table_name SET log_status = 'publish' WHERE  ID IN({$id})");
            } else {
                $n_ids = explode(',', $ids);
                $count = count($n_ids);
                $wpdb->query("UPDATE $table_name SET log_status = 'publish' WHERE ID IN({$ids})");
            }
            wc_crm_add_notice(sprintf(_n('%d post restored from the Trash.', '%d posts restored from the Trash.', $count, 'wc_crm'), $count), 'success');
        }

        public static function move_to_trash_activity($ids)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . "wc_crm_log";
            $logs_string = '';
            if (is_array($ids)) {
                $count = count($ids);
                $logs_string = $id = implode(',', $ids);
                $wpdb->query("UPDATE $table_name SET log_status = 'trash' WHERE ID IN({$id}) ");
            } else {
                $logs_string = $ids;
                $n_ids = explode(',', $ids);
                $count = count($n_ids);
                $wpdb->query("UPDATE $table_name SET log_status = 'trash' WHERE ID IN({$ids}) ");
            }
            $undo_url = sprintf('<a href="?page=%s&action=untrash&log=%s' . (isset($_GET['c_id']) ? '&c_id=' . $_GET['c_id'] : '') . '">' . __('Undo', 'wc_crm') . '</a>', $_GET['page'], $logs_string);
            wc_crm_add_notice(sprintf(_n('%d post moved to the Trash.', '%d posts moved to the Trash.', $count, 'wc_crm'), $count) . ' ' . $undo_url, 'success');

        }

        public static function get_sendback()
        {
            $wp_list_table = new WP_List_Table;
            $pagenum = $wp_list_table->get_pagenum();
            $parent_file = 'admin.php?page=' . $_GET['page'];

            $sendback = remove_query_arg(array('action', 'log'), wp_get_referer());
            if (!$sendback)
                $sendback = admin_url($parent_file);
            $sendback = add_query_arg('paged', $pagenum, $sendback);

            return $sendback;
        }

        public function tasks_status_filter($post_type, $which)
        {
            //Todo create Statuses filters class
            if ($post_type == 'wc_crm_tasks') {
                $statuses = wc_crm_get_task_statuses();
                $priorities = wc_crm_get_task_priorities(); ?>
                <select name="wc_crm_task_status">
                    <option value="all"><?php _e('All statuses', 'wc_crm') ?></option>
                    <?php foreach ($statuses as $key => $value) { ?>
                        <?php if(isset($_GET['wc_crm_task_status'])): ?>
                        <option value="<?php echo $key ?>" <?php echo ($_GET['wc_crm_task_status'] == $key) ? 'selected' : '' ?>><?php echo $value ?></option>
                        <?php endif; ?>
                    <?php } ?>
                </select>
                <select name="wc_crm_task_priority">
                    <option value="all"><?php _e('All priorities', 'wc_crm') ?></option>
                    <?php foreach ($priorities as $key => $value) { ?>
                        <?php if(isset($_GET['wc_crm_task_priority'])): ?>
                        <option value="<?php echo $key ?>" <?php echo ($_GET['wc_crm_task_priority'] == $key) ? 'selected' : '' ?>><?php echo $value ?></option>
                        <?php endif; ?>
                    <?php } ?>
                </select>
            <?php }
            //Todo create Calls filters class
            if ($post_type == 'wc_crm_calls') { ?>
                <select name="wc_crm_call_type">
                    <option value="all"><?php _e('All types', 'wc_crm') ?></option>
                    <?php foreach (wc_crm_get_call_types() as $key => $type) { ?>
                        <?php if(isset($_GET['wc_crm_call_type'])): ?>
                        <option value="<?php echo $key ?>" <?php echo ($key == $_GET['wc_crm_call_type']) ? 'selected' : '' ?>><?php echo $type ?></option>
                        <?php endif; ?>
                    <?php } ?>
                </select>
                <?php
                WC_CRM_Screen_Customer_Filters::customer_name_filter();
                get_call_owner_filter();
            }
            if( $post_type == 'wc_crm_validations' ){
                $selected_type = isset($_GET['validation_type']) && $_GET['validation_type'] ? $_GET['validation_type'] : 'all';
                ?>
                <select name="validation_type" id="validation_type">
                    <option value="all" <?php selected( $selected_type, "all", true );?> ><?php _e('All File Types', 'wc_crm') ?></option>
                    <?php
                    foreach (WC_CRM_VALIDATION::get_validation_types() as $key => $type) :
                        echo '<option value="'.$key.'" '. selected( $selected_type, $key, true ) .'>'. __( $type, "wc_crm" ) .'</option>';
                    endforeach;
                    var_dump(isset($_GET['validation_type']));
                    ?>
                </select>
            <?php
            }
        }

        /**
         * @param WP_Query $query
         */
        public function wc_crm_parse_query($query)
        {
            if (isset($_GET['post_type'])) {
                if ($_GET['post_type'] == 'wc_crm_tasks' && isset($_GET['wc_crm_task_status']) && $_GET['wc_crm_task_status'] != 'all') {
                    $query->query_vars['post_status'] = $_GET['wc_crm_task_status'];
                }
                if ($_GET['post_type'] == 'wc_crm_tasks' && isset($_GET['wc_crm_task_priority']) && $_GET['wc_crm_task_priority'] != 'all') {
                    $query->query_vars['meta_key'] = '_priority';
                    $query->query_vars['meta_value'] = $_GET['wc_crm_task_priority'];
                }

                if (isset($_GET['wc_crm_call_type']) && $_GET['post_type'] == 'wc_crm_calls' && $_GET['wc_crm_call_type'] && $_GET['wc_crm_call_type'] != 'all') {
                    $query->query_vars['meta_key'] = '_type';
                    $query->query_vars['meta_value'] = $_GET['wc_crm_call_type'];
                }
                if (isset($_GET['_customer_user']) && $_GET['post_type'] == 'wc_crm_calls' && $_GET['_customer_user'] && $_GET['_customer_user'] != 'all') {
                    $query->query_vars['meta_key'] = '_customer_id';
                    $query->query_vars['meta_value'] = $_GET['_customer_user'];
                }
                if (isset($_GET['_call_owner']) && $_GET['post_type'] == 'wc_crm_calls' && $_GET['_call_owner'] && $_GET['_call_owner'] != 'all') {
                    $customer = wc_crm_get_customer($_GET['_call_owner']);
                    $query->query_vars['author'] = $customer->user_id;
                }
                if ($_GET['post_type'] == 'wc_crm_validations' && isset($_GET['validation_status']) && $_GET['validation_status'] != 'all') {
                    $query->query_vars['meta_key'] = 'validation_status';
                    $query->query_vars['meta_value'] = $_GET['validation_status'];
                }
                if ($_GET['post_type'] == 'wc_crm_validations' && isset($_GET['validation_type']) && $_GET['validation_type'] != 'all') {
                    $query->query_vars['meta_query'] = array(
                        'meta_query' => array(
                            'key' => 'validation_file',
                            'value' => $_GET['validation_type'],
                            'compare' => 'LIKE'
                        )
                    );
                }
            }
        }
    }

    new WC_CRM_Admin_Post_Actions();

endif;