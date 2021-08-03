<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Screen_Customers_Edit')) :

    /**
     * WC_CRM_Screen_Customers_Edit Class
     */
    class WC_CRM_Screen_Customers_Edit
    {

        public static function output($id = 0)
        {
            global $the_customer, $thepostid, $post, $wp_meta_boxes;

            if ($id > 0) {
                $thepostid = $id;
            } else {
                $thepostid = 'new';
            }
            $the_customer = new WC_CRM_Customer($id);
            $current_user = wp_get_current_user();

            if (!$current_user->has_cap('edit_posts') && ($the_customer->customer_agent && $current_user->ID != $the_customer->customer_agent)) {
                echo '<div class="access-error"><p>Sorry, you are not allowed to access this page as you are not the assigned agent for this customer.</p></div>';
                exit();
            }

            if ($thepostid != 'new' && $the_customer->status == 'trashed') {
                wp_die(__('You can&#8217;t edit this item because it is in the Trash. Please restore it and try again.'));
            }

            $post_type = 'crm_customers';
            do_action('add_meta_boxes', $post_type, $the_customer);
            do_action('add_meta_boxes_' . $post_type, $the_customer);

            do_action('do_meta_boxes', $post_type, 'normal', $the_customer);
            /** This action is documented in wp-admin/edit-form-advanced.php */
            do_action('do_meta_boxes', $post_type, 'advanced', $the_customer);
            /** This action is documented in wp-admin/edit-form-advanced.php */
            do_action('do_meta_boxes', $post_type, 'side', $the_customer);

            $title = __('Edit Customer ', 'wc_crm');
            if ($the_customer->user_id == 0) {
                $title .= ' (' . __('Guest', 'wc_crm') . ')';
            }
            if ($id == 0) {
                $title = __('Add New Customer', 'wc_crm');
            }

            ?>
            <div class="wrap">
                <h1><?php echo $title;; ?><a class="add-new-h2"
                                             href="admin.php?page=<?php echo WC_CRM_TOKEN; ?>-new-customer"><?php _e('Add Customer', 'wc_crm'); ?></a>
                </h1>
                <?php
                wc_crm_print_notices(); ?>
                <form id="wc_crm_edit_customer_form" method="post">
                    <?php if ($id > 0) { ?>
                        <input type="hidden" id="customer_id" name="customer_id"
                               value="<?php echo $the_customer->customer_id; ?>">
                        <input type="hidden" id="user_id" name="user_id" value="<?php echo $the_customer->user_id; ?>">
                        <input type="hidden" id="order_id" name="order_id"
                               value="<?php echo $the_customer->order_id; ?>">
                    <?php } ?>
                    <div id="poststuff">
                        <div id="post-body" class="metabox-holder columns-2">
                            <div id="postbox-container-1" class="postbox-container">
                                <div class="meta-box-sortables">
                                    <?php include_once 'views/html-customer-actions.php'; ?>
                                    <?php include_once 'views/html-customer-map.php'; ?>
                                    <?php include_once 'views/html-customer-groups.php'; ?>
                                    <?php
                                    if ($the_customer->customer_id > 0) {
                                        include_once 'views/html-customer-notes.php';
                                    }
                                    ?>
                                    <?php
                                    if(get_option('wc_crm_enable_validation') == 'yes')
                                        include_once 'views/html-customer-validations.php';
                                    ?>
                                </div>
                                <?php do_meta_boxes($post_type, 'side', $the_customer); ?>
                            </div>
                            <div id="postbox-container-2" class="postbox-container">
                                <div class="meta-box-sortables">
                                    <?php include_once 'views/html-customer-details.php'; ?>
                                    <?php if ($id > 0) { ?>
                                        <?php include_once 'views/html-customer-orders.php'; ?>
                                        <?php include_once 'views/html-customer-tasks.php'; ?>
                                        <?php include_once 'views/html-customer-calls.php'; ?>
                                        <?php include_once 'views/html-customer-activity.php'; ?>
                                        <?php include_once 'views/html-customer-products-purchased.php'; ?>
                                        <?php include_once 'views/html-customer-favourite-products.php'; ?>
                                    <?php } ?>
                                </div>
                                <?php
                                do_meta_boxes($post_type, 'normal', $the_customer);
                                do_meta_boxes($post_type, 'advanced', $the_customer);
                                ?>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
            <?php
        }

        public static function save($customer_id, $new = false)
        {
            global $the_customer, $wpdb;
            $the_customer = new WC_CRM_Customer($customer_id);
            wc_crm_clear_notices();
            $data = array(
                'status' => $_POST['customer_status']
            );
            $old_user_data = array();
            if ($the_customer->user_id > 0) {


                if (empty($_POST['user_email'])) {

                    wc_crm_add_notice(__('Please enter an e-mail address.', 'wc_crm'), 'error');

                } elseif (!is_email($_POST['user_email'])) {

                    wc_crm_add_notice(__("The email address isn't correct.", 'wc_crm'), 'error');

                } elseif (($owner_id = email_exists($_POST['user_email'])) && $owner_id != $the_customer->user_id) {

                    wc_crm_add_notice(__("This email is already registered, please choose another one.", 'wc_crm'), 'error');

                }

                if (wc_crm_notice_count('error') > 0) {
                    return;
                }

                $old_user_data = WP_User::get_data_by('id', $the_customer->user_id);

                $user_data_up = array(
                    'ID' => $the_customer->user_id,
                    'user_url' => $_POST['customer_site'],
                    'user_email' => $_POST['user_email']
                );

                wp_update_user($user_data_up);

                $the_customer->init_general_fields();
                $the_customer->init_address_fields();
                $_POST['first_name'] = ucfirst($_POST['first_name']);
                $_POST['last_name'] = ucfirst($_POST['last_name']);
                $agent = $the_customer->general_fields['customer_agent']['value'];
                if ($the_customer->general_fields) {
                    foreach ($the_customer->general_fields as $key => $field) {
                        if (!isset($field['type']))
                            $field['type'] = 'text';
                        if (!isset($field['meta_key']))
                            $field['meta_key'] = $key;

                        if (!isset($_POST[$key])) {
                            if ($field['type'] == 'multiselect') {
                                update_user_meta($the_customer->user_id, $field['meta_key'], array());
                            }
                            if($field['type'] == 'select')
                                update_user_meta($the_customer->user_id, $field['meta_key'], null);
                            continue;
                        }

                        switch ($key) {
                            case "customer_twitter" :
                                $post_value = str_replace('@', '', $_POST['customer_twitter']);
                                break;
                            default :
                                $post_value = $_POST[$key];
                                break;
                        }
                        update_user_meta($the_customer->user_id, $field['meta_key'], $post_value);
                    }
                }

                if ($the_customer->billing_fields) {
                    foreach ($the_customer->billing_fields as $key => $field) {
                        if (!isset($_POST['_billing_' . $key])) {
                            continue;
                        }
                        $post_value = $_POST['_billing_' . $key];
                        update_user_meta($the_customer->user_id, 'billing_' . $key, $post_value);
                    }
                }

                if ($the_customer->shipping_fields) {
                    foreach ($the_customer->shipping_fields as $key => $field) {
                        if (!isset($_POST['_shipping_' . $key])) {
                            continue;
                        }
                        $post_value = $_POST['_shipping_' . $key];
                        update_user_meta($the_customer->user_id, 'shipping_' . $key, $post_value);
                    }
                }

                update_user_meta($the_customer->user_id, 'favourite_products', $_POST['favourite_products']);
                update_user_meta($the_customer->user_id, 'preferred_payment_method', $_POST['_payment_method']);
                update_user_meta($the_customer->user_id, 'payment_method', $_POST['_payment_method']);

//                $user_agent = wc_crm_get_customer(get_current_user_id(), 'user_id');
//                if($user_agent->c_id){
//                    update_user_meta($the_customer->user_id, 'customer_agent', $user_agent->c_id);
//                }

                $customer_watching = isset($_POST['customer_watching']) ? true : false;
                update_user_meta($the_customer->user_id, 'customer_watching', $customer_watching);

                $group_ids = array();
                if (isset($_POST['wc_crm_customer_groups'])) {
                    $group_ids = $_POST['wc_crm_customer_groups'];
                }

                $the_customer->update_groups($group_ids);

                if (isset($_POST['account_name'])) {
                    if (!empty($_POST['account_name'])) {
                        $account_id = $_POST['account_name'];
                        add_post_meta($account_id, '_wc_crm_customer_id', $customer_id);
                    } else {
                        $the_customer = new WC_CRM_Customer($customer_id);
                        $account_id = $the_customer->get_account();
                        delete_post_meta($account_id, '_wc_crm_customer_id');
                    }
                }

                $data = array(
                    'email' => $_POST['user_email'],
                    'first_name' => ucfirst($_POST['first_name']),
                    'last_name' => ucfirst($_POST['last_name']),
                    'state' => isset($_POST['_billing_state']) ? $_POST['_billing_state'] : '',
                    'city' => isset($_POST['_billing_city']) ? $_POST['_billing_city'] : '',
                    'country' => $_POST['_billing_country'],
                    'status' => $_POST['customer_status'],
                    'cpf' => $_POST['_billing_cpf'],
                    'cnpj' => $_POST['_billing_cnpj'],

                );

                $res = $wpdb->update("{$wpdb->prefix}wc_crm_customer_list", $data, array('c_id' => $the_customer->customer_id));

                //do_action('profile_update', $the_customer->user_id, $old_user_data);
                // update the post (may even be a revision / autosave preview)
                do_action('acf/save_post', 'user_' . $the_customer->user_id);
                do_action('acf/save_post', 'user_' . $the_customer->user_id);

                do_action('wc_crm_save_customer', $the_customer->customer_id, $the_customer, $old_user_data);

            } else if ($the_customer->customer_id > 0) {
                $the_customer->init_general_fields();
                $disabled = array('first_name', 'last_name', 'user_email', 'customer_status');
                if ($the_customer->general_fields) {
                    foreach ($the_customer->general_fields as $key => $field) {
                        if (in_array($key, $disabled)) {
                            continue;
                        }

                        if (!isset($field['type']))
                            $field['type'] = 'text';
                        if (!isset($field['meta_key']))
                            $field['meta_key'] = $key;

                        if (!isset($_POST[$key])) {
                            if ($field['type'] == 'multiselect') {
                                wc_crm_update_cmeta($the_customer->customer_id, $field['meta_key'], array());
                            }
                            continue;
                        }

                        switch ($key) {
                            case "customer_twitter" :
                                $post_value = str_replace('@', '', $_POST['customer_twitter']);
                                break;
                            default :
                                $post_value = $_POST[$key];
                                break;
                        }
                        wc_crm_update_cmeta($the_customer->customer_id, $field['meta_key'], $post_value);
                    }
                }

                if (isset($_POST['account_name'])) {
                    if (!empty($_POST['account_name'])) {
                        $account_id = $_POST['account_name'];
                        add_post_meta($account_id, '_wc_crm_customer_email', $customer_id);
                    } else {
                        $the_customer = new WC_CRM_Customer($customer_id);
                        $account_id = $the_customer->get_account();
                        delete_post_meta($account_id, '_wc_crm_customer_email');
                    }
                }

                $res = $wpdb->update("{$wpdb->prefix}wc_crm_customer_list", $data, array('c_id' => $the_customer->customer_id));

                do_action('guest_update', $the_customer->customer_id, $the_customer->email);

            }
            if ($new === false)
                wc_crm_add_notice(__("Customer updated.", 'wc_crm'), 'success');

            if( !empty($_POST['customer_agent']) && ($agent != $_POST['customer_agent']) ){
                $mailer = WC()->mailer();
                do_action('wc_crm_send_agent_assignment_notification', array('customer' => $the_customer));
            }
        }

        public static function create_user()
        {
            if (empty($_POST['user_email'])) {

                wc_crm_add_notice(__('Please enter an e-mail address.', 'wc_crm'), 'error');

            } elseif (!is_email($_POST['user_email'])) {

                wc_crm_add_notice(__("The email address isn't correct.", 'wc_crm'), 'error');

            } elseif (email_exists($_POST['user_email'])) {

                wc_crm_add_notice(__("This email is already registered, please choose another one.", 'wc_crm'), 'error');

            }

            if (wc_crm_notice_count('error') > 0) {
                return;
            }

            global $wpdb;
            $nickname = str_replace(' ', '', ucfirst(strtolower($_POST['first_name']))) . str_replace(' ', '', ucfirst(strtolower($_POST['last_name'])));

            $username_opt = get_option('wc_crm_username_add_customer');
            switch ($username_opt) {
                case 2:
                    $username = str_replace(' ', '', strtolower($_POST['first_name'])) . '-' . str_replace(' ', '', strtolower($_POST['last_name']));
                    break;
                case 3:
                    $username = $_POST['user_email'];
                    break;
                default:
                    $username = strtolower($nickname);
                    break;
            }
            $username = _truncate_post_slug($username, 60);
            $check_sql = "SELECT user_login FROM {$wpdb->users} WHERE user_login = '%s' LIMIT 1";

            $user_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $username));

            if ($user_name_check) {
                $suffix = 1;
                do {
                    $alt_user_name = _truncate_post_slug($username, 60 - (strlen($suffix) + 1)) . "-$suffix";
                    $user_name_check = $wpdb->get_var($wpdb->prepare($check_sql, $alt_user_name));
                    $suffix++;
                } while ($user_name_check);
                $username = $alt_user_name;
            }

            add_filter('pre_option_woocommerce_registration_generate_password', 'wcrm_enable_generate_password');
            $user_id = wc_create_new_customer($_POST['user_email'], $username);
            remove_filter('pre_option_woocommerce_registration_generate_password', 'wcrm_enable_generate_password');

            do_action('wc_crm_create_customer', $user_id);

            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'nickname', $nickname);
                wp_update_user(array('ID' => $user_id, 'role' => 'customer'));

                $customer_id = $wpdb->get_var("SELECT c_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE user_id = {$user_id} ");
                if ($customer_id) {
                    WC_CRM_Screen_Customers_Edit::save($customer_id, true);
                }

                wc_crm_add_notice(__("Customer created.", 'wc_crm'), 'success');
                wp_safe_redirect(admin_url() . 'admin.php?page=' . WC_CRM_TOKEN);
            } else {
                wc_crm_add_notice($user_id->get_error_message(), 'error');
            }

        }

        public static function get_customers($email = '')
        {
            global $wpdb;

            $sql = self::get_sql($email);

            $result = $wpdb->get_results($sql, ARRAY_A);

            return $result;
        }

        public static function get_customers_count()
        {

            global $wpdb;

            $sql = self::get_sql();

            $result = count($wpdb->get_results($sql));

            return $result;
        }

        public static function get_sql()
        {
            global $wpdb;

            $options = array(
                'total_value' => get_option('wc_crm_total_value', array('wc-completed')),
                'user_roles' => get_option('wc_crm_user_roles', array('customer')),
                'guest_customers' => get_option('wc_crm_guest_customers', 'no'),
                'customer_name' => get_option('wc_crm_customer_name', 'fl'),
            );

            $options = apply_filters('wc_crm_customers_list_options', $options);

            $user_role_filter = '';
            if (empty($options['user_roles']) || !is_array($options['user_roles'])) {
                $options['user_roles'] = array('customer');
            }
            foreach ($options['user_roles'] as $value) {
                if (!empty($user_role_filter)) $user_role_filter .= ' OR ';
                $user_role_filter .= "customer.capabilities LIKE '%{$value}%'";
            }

            $user_role_filter = apply_filters('wc_crm_customers_list_user_role_filter', $user_role_filter);

            $filter = array();
            $join = array();
            $inner = array();
            $select = array();

            $sql = "SELECT customer.* FROM {$wpdb->prefix}wc_crm_customer_list as customer
		        ";
            /*$sql = "SELECT customer.*, posts.post_date as last_purchase {$select} FROM {$wpdb->prefix}wc_crm_customer_list as customer
                    LEFT JOIN {$wpdb->posts} posts ON (customer.order_id = posts.ID)
                    {$join}
                    {$inner}
                    WHERE 1=1
                    {$filter}
                    GROUP BY customer.email
                    ";*/
            #echo '<textarea name="" id="" style="width: 100%; height: 200px; ">'.$sql.'</textarea>';die;
            return $sql;
        }

        /**
         * List customer notes (public)
         *
         * @access public
         * @return array
         */
        public static function display_notes($customer_id = 0)
        {
            ?>
            <script>
                jQuery('document').ready(function ($) {
                    var parentBody = window.parent.document.body;
                    $('#customer_notes_popup > .media-modal', parentBody).unblock();
                });
            </script>
            <style>
                html {
                    padding-top: 0 !important;
                }

                #wpbody-content {
                    padding: 0 !important;
                }

                #message,
                #adminmenuwrap,
                #screen-meta,
                #screen-meta-links,
                #adminmenuback,
                #wpfooter,
                #wpadminbar {
                    display: none !important;
                }

                #wpcontent {
                    margin: 0 !important;
                    padding: 0 !important;
                }

                #wc-crm-page {
                    margin: 1.5em !important;
                }

                .media-frame-title h1 {
                    text-transform: capitalize;
                }

                #wc-crm-page > h2 {
                    display: none;
                }
            </style>
            <?php
            global $the_customer;
            $the_customer = new WC_CRM_Customer($customer_id);
            ?>
            <?php if ($customer_id > 0) { ?>
            <input type="hidden" id="customer_id" name="customer_id" value="<?php echo $the_customer->customer_id; ?>">
            <input type="hidden" id="user_id" name="user_id" value="<?php echo $the_customer->user_id; ?>">
            <input type="hidden" id="order_id" name="order_id" value="<?php echo $the_customer->order_id; ?>">
        <?php } ?>
            <div id="side-sortables" class="meta-box-sortables">
                <div class="postbox " id="woocommerce-customer-notes">
                    <div class="inside">
                        <ul class="order_notes">
                            <?php
                            $notes = $the_customer->get_customer_notes(); ?>
                            <?php if ($notes) {
                                foreach ($notes as $note) {
                                    ?>
                                    <li rel="<?php echo absint($note->comment_ID); ?>">
                                        <div class="note_content">
                                            <?php echo wpautop(wptexturize(wp_kses_post($note->comment_content))); ?>
                                        </div>
                                        <p class="meta">
                                            <abbr class="exact-date"
                                                  title="<?php echo $note->comment_date_gmt; ?> GMT"><?php printf(__('added %s ago', 'wc_crm'), human_time_diff(strtotime($note->comment_date_gmt), current_time('timestamp', 1))); ?></abbr>
                                            <?php if ($note->comment_author !== __('WooCommerce', 'wc_crm')) printf(' ' . __('by %s', 'wc_crm'), $note->comment_author); ?>
                                            <a href="#"
                                               class="delete_customer_note"><?php _e('Delete note', 'wc_crm'); ?></a>
                                        </p>
                                    </li>
                                    <?php
                                }
                            } else {
                                echo '<li>' . __('There are no notes for this customer yet.', 'wc_crm') . '</li>';
                            } ?>
                        </ul>
                        <div class="add_note">
                            <h4>Add note</h4>
                            <p>
                                <textarea rows="5" cols="20" class="input-text" id="add_order_note" name="order_note"
                                          type="text"></textarea>
                            </p>
                            <p>
                                <a class="add_note_customer button" href="#">Add</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        public static function move_to_trash()
        {
            global $wpdb;
            $customer_ids = array();
            if (isset($_REQUEST['c_id'])) {
                $customer_ids[] = $_REQUEST['c_id'];
            } else if (isset($_REQUEST['customer_id']) && is_array($_REQUEST['customer_id'])) {
                $customer_ids = $_REQUEST['customer_id'];
            }
            if (!empty($customer_ids)) {
                $sql = "UPDATE {$wpdb->prefix}wc_crm_customer_list SET status = 'trashed' WHERE c_id IN (" . implode(',', $customer_ids) . ")";
                $wpdb->query($sql);
            }
            wp_redirect('admin.php?page=wc_crm&trashed=' . implode(',', $customer_ids));
        }

        public static function untrash()
        {
            global $wpdb;
            $customer_ids = '';
            if (isset($_REQUEST['ids'])) {
                $customer_ids = $_REQUEST['ids'];
            } else if (isset($_REQUEST['customer_id']) && is_array($_REQUEST['customer_id'])) {
                $customer_ids = implode(',', $_REQUEST['customer_id']);
            }
            if (!empty($customer_ids)) {
                $sql = "UPDATE {$wpdb->prefix}wc_crm_customer_list SET status = 'Customer' WHERE c_id IN (" . $customer_ids . ")";
                $wpdb->query($sql);
            }
            wp_redirect('admin.php?page=wc_crm&untrashed=' . $customer_ids);
        }

        public static function delete()
        {
            global $wpdb;
            $customer_ids = array();
            if (isset($_REQUEST['ids'])) {
                $customer_ids = explode(',', $_REQUEST['ids']);
            } else if (isset($_REQUEST['customer_id']) && is_array($_REQUEST['customer_id'])) {
                $customer_ids = $_REQUEST['customer_id'];
            }

            $delete_type = intval(isset($_REQUEST['delete_type']) ? $_REQUEST['delete_type'] : 1);
            $current_user_id = get_current_user_id();
            if (!empty($customer_ids)) {
                $customer_ids = array_map('intval', $customer_ids);
                foreach ($customer_ids as $c_id) {
                    $the_customer = new WC_CRM_Customer($c_id);
                    if ($the_customer->user_id > 0 && $current_user_id == $the_customer->user_id) {
                        continue;
                    }
                    switch ($delete_type) {
                        case 2:
                            self::delete_orders($the_customer);
                            break;
                        default:
                            self::retain_orders($the_customer);
                            break;
                    }
                }
            }
            wp_redirect('admin.php?page=wc_crm&deleted=' . implode(',', $customer_ids));
        }

        public static function retain_orders($the_customer = null)
        {
            global $wpdb;
            if (!($the_customer instanceof WC_CRM_Customer)) return false;
            $sql = "UPDATE {$wpdb->prefix}wc_crm_customer_list SET user_id = 0, status = 'Customer' WHERE c_id = {$the_customer->id}";
            $wpdb->query($sql);
            if ($the_customer->user_id > 0) {
                wp_delete_user($the_customer->user_id);
            }

        }

        public static function delete_orders($the_customer = null)
        {
            global $wpdb;
            if (!($the_customer instanceof WC_CRM_Customer)) return false;
            $result = false;
            if ($the_customer->user_id > 0) {
                $sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_customer_user' AND meta_value = {$the_customer->user_id}";
                $result = $wpdb->get_results($sql);
                wp_delete_user($the_customer->user_id);
            } else {
                $sql = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_billing_email' AND meta_value = '{$the_customer->email}'";
                $result = $wpdb->get_results($sql);

                $sql = "DELETE FROM {$wpdb->prefix}wc_crm_customer_list WHERE c_id = {$the_customer->id}";
                $wpdb->query($sql);
            }
            if ($result) {
                foreach ($result as $value) {
                    wp_delete_post($value->post_id, true);
                }
            }
        }

    }

endif;