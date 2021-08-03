<?php
/**
 *
  * @author     Adailton Machado
 * @category Admin
 * @package  WC_CRM_Admin/Admin
 * @version  1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Admin_Orders_Page')) :

    /**
     * WC_CRM_Admin_Orders_Page Class
     *
     * Handles the edit posts views and some functionality on the edit post screen for WC post types.
     */
    class WC_CRM_Admin_Orders_Page
    {

        /**
         * Hook into ajax events
         */
        public function __construct()
        {
            add_action('admin_head', array($this, 'view_customer_button'));
            add_action('admin_head', array($this, 'view_customer_link'));
            add_action('manage_shop_order_posts_custom_column', array($this, 'render_shop_order_columns'), 50);
            add_filter('manage_edit-shop_order_columns', array($this, 'indication_shop_order_column'), 11);
            add_action('woocommerce_admin_order_data_after_order_details', array($this, 'customer_status_options'));
        }

        public function view_customer_button()
        {
            $screen = get_current_screen();
            if (!$screen) return;
            if ($screen->id != 'shop_order' || !isset($_GET['post']) || empty($_GET['post'])) return;
            $crm_customer_link = get_option('wc_crm_customer_link', 'customer');

            $url = '';

            $user_id = get_post_meta($_GET['post'], '_customer_user', true);
            $email = get_post_meta($_GET['post'], '_billing_email', true);
            if ($crm_customer_link == 'customer') {
                if ($user_id) {
                    $user = wc_crm_get_customer($user_id, 'user_id');
                    if ($user) {
                        $url = get_admin_url() . 'admin.php?page=' . WC_CRM_TOKEN . '&c_id=' . $user->c_id;
                    }
                } else if ($email) {
                    $user = wc_crm_get_customer($email, 'email');
                    if ($user) {
                        $url = get_admin_url() . 'admin.php?page=' . WC_CRM_TOKEN . '&c_id=' . $user->c_id;
                    }
                }
            } else if ($user_id) {
                $user = wc_crm_get_customer($user_id, 'user_id');
                if ($user) {
                    $url = get_admin_url() . 'user-edit.php?user_id=' . $user_id;
                }
            }
            if (empty($url)) return false;
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('.page-title-action').after('<a class="add-new-h2 add-new-view-customer" href="<?php echo $url; ?>"><?php _e("View Customer", "wc_crm"); ?></a>');
                });
            </script>
            <style>
                .wrap .add-new-h2.add-new-view-customer, .wrap .add-new-h2.add-new-view-customer:active {
                    margin-left: 10px;
                }

                table td.order_title div.tips:not(.wc_crm_customer_link) {
                    display: none;
                }
            </style>
            <?php
        }

        public function view_customer_link()
        {
            $crm_customer_link = get_option('wc_crm_customer_link', 'customer');
            if ($crm_customer_link == 'customer') {

                ?>
                <style>
                    table td.order_title div.wc_crm_customer_link {
                        display: none;
                    }
                </style>
                <script>
                    jQuery('document').ready(function ($) {
                        $('table td.order_title').each(function (index, el) {
                            var html = $(this).find('div.wc_crm_customer_link').html();
                            $(this).html(html);
                        });
                    });
                </script>
                <?php
            }
        }

        public function render_shop_order_columns($column)
        {
            global $post, $woocommerce, $the_order;

            if (empty($the_order) || $the_order->get_id() != $post->ID) {
                $the_order = wc_get_order($post->ID);
            }
            $user_id = $the_order->get_user_id();
            $email = $the_order->get_billing_email();
            if ($user_id) {
                $user = wc_crm_get_customer($user_id, 'user_id');
            } else if ($email) {
                $user = wc_crm_get_customer($email, 'email');
            }
            switch ($column) {
                case 'order_title' :
                    $crm_customer_link = get_option('wc_crm_customer_link', 'customer');
                    if ($crm_customer_link == 'customer') {

                        $url = '';

                        echo '<div class="wc_crm_customer_link">';

                        if ($user) {
                            $url = get_admin_url() . 'admin.php?page=' . WC_CRM_TOKEN . '&c_id=' . $user->c_id;
                        }

                        if ($the_order->get_user_id()) {
                            $user_info = get_userdata($the_order->get_user_id());
                        }

                        $username = '';
                        if (!empty($url)) {
                            $username = '<a href="' . $url . '">';
                        }
                        if (!empty($user_info)) {


                            if ($user_info->first_name || $user_info->last_name) {
                                $username .= esc_html(sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), ucfirst($user_info->first_name), ucfirst($user_info->last_name)));
                            } else {
                                $username .= esc_html(ucfirst($user_info->display_name));
                            }


                        } else {
                            if ($the_order->get_billing_first_name() || $the_order->get_billing_last_name()) {
                                $username .= trim(sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), $the_order->get_billing_first_name(), $the_order->get_billing_last_name()));
                            } else {
                                $username .= __('Guest', 'woocommerce');
                            }
                        }
                        if (!empty($url)) {
                            $username .= '</a>';
                        }

                        printf(_x('%s by %s', 'Order number by X', 'woocommerce'), '<a href="' . admin_url('post.php?post=' . absint($post->ID) . '&action=edit') . '" class="row-title"><strong>#' . esc_attr($the_order->get_order_number()) . '</strong></a>', $username);

                        if ($the_order->get_billing_email()) {
                            echo '<small class="meta email"><a href="' . esc_url('mailto:' . $the_order->get_billing_email()) . '">' . esc_html($the_order->get_billing_email()) . '</a></small>';
                        }
                        echo '<div class="row-actions"><span class="edit"><a href="' . admin_url('post.php?post=' . absint($post->ID) . '&action=edit') . '">Edit</a> | </span><span class="trash"><a href="' . wp_nonce_url("post.php?action=trash&amp;post=$post->ID", 'trash-post_' . $post->ID) . '" class="submitdelete" >Bin</a></span></div>';
                        echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __('Show more details', 'woocommerce') . '</span></button>';
                        echo '</div>';
                    }
                    break;
                case 'indication':
                    $user_id = $the_order->get_user_id();
                    $email = $the_order->get_billing_email();
                    if ($user_id) {
                        $user = wc_crm_get_customer($user_id, 'user_id');
                    } else if ($email) {
                        $user = wc_crm_get_customer($email, 'email');
                    }
                    if (isset($user) && $user->c_id > 0 ) {
                        $the_customer = new WC_CRM_Customer($user->c_id);
                    }
                    if (isset($the_customer)) {
                        if ($the_customer->get_groups()) {
                            $groups_id = array();
                            foreach ($the_customer->groups as $group) {
                                if (is_array($group)) {
                                    continue;
                                }
                                $groups_id[] = $group;
                            }
                            $customer_groups = wc_crm_get_groups_by_id($groups_id);
                            $tip = array();
                            foreach ($customer_groups as $c_gr) {
                                $tip[] = $c_gr->group_name;
                            }
                            echo '<span class="ico_groups tips" data-tip="' . implode(", ", $tip) . '"></span>';
                        }
                        $customer_orders = array();
                        if($the_customer->user_id > 0){
                            $customer_orders = get_posts(array(
                                'numberposts' => -1,
                                'meta_key' => '_customer_user',
                                'meta_value' => $the_customer->user_id,
                                'post_type' => wc_get_order_types(),
                                'post_status' => array_keys(wc_get_order_statuses()),
                            ));
                        }
                        if (count($customer_orders) > 1) {
                            echo '<a href="' . admin_url("edit.php?post_status=all&post_type=shop_order&_customer_user=") . $the_customer->user_id . '"><span class="ico_repeat tips" data-tip="Repeat Customer. Click to view the other ' . count($customer_orders) . ' orders from this customer."></span></a>';
                        }
                        switch ($the_customer->status) {
                            case 'Flagged':
                                echo '<span class="ico_flagged tips" data-tip="' . $the_customer->status . '"></span>';
                                break;
                            case 'Blocked':
                                echo '<span class="ico_attention tips" data-tip="' . $the_customer->status . '"></span>';
                                break;
                            case 'Favourite':
                                echo '<span class="ico_favourite tips" data-tip="' . $the_customer->status . '"></span>';
                                break;
                        }
                        $total_spent_sum = get_option('wc_crm_total_spent_indication');
                        if ($total_spent_sum) {
                            $user_total_spent = wc_get_customer_total_spent($the_customer->user_id);
                            if ($user_total_spent >= $total_spent_sum) {
                                echo '<span class="ico_total_spent tips" data-tip="' . get_woocommerce_currency_symbol() . $user_total_spent . '"></span>';
                            }
                        }
                    }
                    break;
            }
        }

        public function indication_shop_order_column($columns)
        {
            $new_columns = array();
            foreach ($columns as $key => $column) {
                if ($key == 'wc_actions') {
                    $new_columns['indication'] = __('Indicators', 'wc_crm');
                }
                $new_columns[$key] = $column;
            }
            return $new_columns;
        }

        /**
         * @param WC_Order $order
         */
        public function customer_status_options($order)
        {
            if($order->get_user_id() < 1){
                return;
            }
            if(get_option("wc_crm_orders_customer", "no") != "yes" ){
                return;
            }
            $customer = wc_crm_get_customer($order->get_user_id(), 'user_id');
            ?>
            <p class="form-field form-field-wide wc-customer-user">
                <label for="customer_user_status"><?php _e("Customer Status: ") ?></label>
                <?php
                $statuses = wc_crm_get_statuses(true);
                if(count($statuses)) : ?>
                <select class="wc-customer-status" id="customer_user_status" name="customer_user_status" data-placeholder="<?php _e( 'Update customer status', 'wc_point_of_sale' ); ?>" data-allow_clear="false">
                    <?php foreach ($statuses as $status) : ?>
                        <option value="<?php echo esc_attr( $status['status_slug'] ); ?>" <?php selected($customer->status, $status['status_slug'], true) ?>><?php echo $status['status_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </p>
            <script>
                jQuery(document).ready(function($){
                    $("#customer_user_status").select2();
                });
            </script>
            <?php
        }

    }

    new WC_CRM_Admin_Orders_Page();

endif;