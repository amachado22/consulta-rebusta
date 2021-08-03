<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Screen_Customer_Filters')) :

    /**
     * WC_CRM_Screen_Customer_Filters Class
     */
    class WC_CRM_Screen_Customer_Filters
    {

        public static function restrict_list_customers()
        {
            global $woocommerce;
            $obj = new WC_CRM_Screen_Customer_Filters();
            $filters = get_option('wc_crm_filters');
            $r_string = '';
            if (!empty($filters)) :
                ?>
                <div class="alignleft actions">
                    <?php
                    foreach ($filters as $key => $value) {
                        $method_name = $value . '_filter';
                        if (method_exists(__CLASS__, $method_name)) {
                            $obj->$method_name();
                            continue;
                        }
                        if (class_exists('acf')) {
                            $obj->acf_filter($key, $value);
                        }
                    }
                    do_action('wc_crm_add_filters');
                    ?>
                    <input type="hidden" value="yes" name="woocommerce_crm_filter">
                    <input type="submit" id="post-query-submit" class="button action" value="Filter"/>

                </div>
                <?php
            endif;
        }

        public static function get_filter_data($value = '')
        {
            global $wpdb;
            $user = get_user_by('id', get_current_user_id());
            $customer = new WC_CRM_Customer($user);
            if ($value == '') return '';
            $transient_name = 'customer_' . $value . '_filter';
            $data = get_transient($transient_name);

            if ($data === false) {
                $o_sql = '';
                $sql = '';

                $woocommerce_crm_user_roles = get_option('wc_crm_user_roles');
                $user_roles = '';
                if (!empty($woocommerce_crm_user_roles)) {
                    $user_roles = "WHERE capabilities = '' OR capabilities LIKE '%" . implode("%' OR capabilities LIKE '%", $woocommerce_crm_user_roles) . "%'";
                }
                if (!empty($user_roles)) {
                    $o_sql = "(SELECT * FROM {$wpdb->prefix}wc_crm_customer_list $user_roles) ";
                } else {
                    $o_sql = "{$wpdb->prefix}wc_crm_customer_list";
                }

                if ($value == 'order_status') {
                    $sql = "SELECT post_status, count(post_status) as count 
    				FROM (
    				SELECT posts.post_status as post_status FROM {$o_sql} as customer
    				INNER JOIN {$wpdb->postmeta} postmeta 
    				  ON ( 
    				    (customer.user_id != 0 AND customer.user_id = postmeta.meta_value AND postmeta.meta_key = '_customer_user') 
    				    OR
    				    (customer.user_id = 0 AND customer.email = postmeta.meta_value AND postmeta.meta_key = '_billing_email') 
    				    )
    				INNER JOIN {$wpdb->posts} posts ON (postmeta.post_id = posts.ID AND posts.post_status != 'trash' AND posts.post_status != 'auto-draft'  AND posts.post_type =  'shop_order')
    				) as crm_table
    				group by post_status ";
                } else {
                    $sql = "SELECT $value, count($value) as count
    				FROM {$o_sql} as crm_table";

                    $agent_visibility_type = get_option('wc_crm_agent_customer_view', 'only_allowed');
                    if( $user->has_cap('agent') &&  $agent_visibility_type !== "all"){
                        $sql .= " RIGHT JOIN {$wpdb->usermeta} user_meta ON user_meta.meta_key = 'customer_agent' and user_meta.user_id = crm_table.user_id
                                  WHERE user_meta.meta_value = ". $customer->c_id ;
                    }

                    $sql .= " GROUP by $value";
                }
                $data = $wpdb->get_results($sql);
                set_transient($transient_name, $data, DAY_IN_SECONDS * 30);
            }
            #echo "<textarea >".$sql."</textarea>";
            delete_transient($transient_name);
            return $data;
        }

        public static function customer_name_filter()
        {
            global $wpdb;
            $_customer_user = isset($_REQUEST['_customer_user']) ? (int)$_REQUEST['_customer_user'] : 0;
            $user_string = '';
            if ($_customer_user > 0) {
                $the_customer = new WC_CRM_Customer($_customer_user);
                $user_string = esc_html($the_customer->get_name()) . ' (#' . absint($the_customer->c_id) . ' - ' . esc_html($the_customer->user_email);
            }
            ?>
            <?php if (WC_VERSION >= 3) { ?>
            <select id="dropdown_customers" name="_customer_user"
                    value="<?php echo isset($_REQUEST['_customer_user']) ? $_REQUEST['_customer_user'] : ''; ?>"
                    class="wc-crm-customer-search" style="width: 200px" data-placeholder="Search for a customer..."
                    data-allow_clea="true" data-selected="<?php echo htmlspecialchars($user_string); ?>">
                <?php if (isset($_REQUEST['_customer_user'])) { ?>
                    <option value="<?php echo $_REQUEST['_customer_user'] ?>"><?php echo htmlspecialchars($user_string); ?></option>
                <?php } ?>
            </select>
        <?php } else { ?>
            <input type="text" id="dropdown_customers" name="_customer_user"
                   value="<?php echo isset($_REQUEST['_customer_user']) ? $_REQUEST['_customer_user'] : ''; ?>"
                   class="wc-customer-search" style="width: 200px" data-placeholder="Search for a customer..."
                   data-allow_clea="true" data-selected="<?php echo htmlspecialchars($user_string); ?>">
        <?php } ?>
            <?php
        }

        public static function customer_status_filter()
        {
            global $wpdb;
            $customer_status = self::get_filter_data('status');
            if (!is_array($customer_status)) {
                $customer_status = array();
            }
            ?>
            <select id="dropdown_customer_status" name="_customer_status">
                <option value=""><?php _e('Show all customer statuses', 'wc_crm') ?></option>
                <?php
                foreach ($customer_status as $status) {
                    if (!$status->status || $status->status == NULL || empty($status->status)) continue;

                    $status_text = $status->status == 'trashed' ? __(ucfirst('trash'), 'wc_crm') : __(ucfirst($status->status), 'wc_crm');
                    if (!empty($_REQUEST['_customer_status']) && $_REQUEST['_customer_status'] == $status->status) {
                        echo '<option value="' . $status_text . '" ' . selected(1, 1, false) . '>' . $status_text . ' (' . $status->count . ')</option>';
                    } else {
                        echo '<option value="' . $status_text . '" >' . $status_text . ' (' . $status->count . ')</option>';
                    }
                }
                ?>
            </select>
            <?php
        }

        public static function products_filter()
        {
            global $wpdb;
            ?>
            <select name="_customer_product" class="wc-product-search"
                    data-action="woocommerce_json_search_products" id="dropdown_product" style="width: 400px"
                    data-placeholder="Search for a product…"></select>
            <?php
        }

        public static function country_filter()
        {
            global $wpdb, $woocommerce;
            $order_countries = self::get_filter_data('country');
            if (!is_array($order_countries)) {
                $order_countries = array();
            }
            ?>
            <select name='_customer_country' id='dropdown_country'>
                <option value=""><?php _e('Show all countries', 'wc_crm'); ?></option>
                <?php

                foreach ($order_countries as $country) {
                    if (!$country->country || $country->country == NULL || $country->country == '') continue;
                    echo '<option value="' . $country->country . '" ';
                    if (!empty($_REQUEST['_customer_country']) && $_REQUEST['_customer_country'] == $country->country) {
                        echo 'selected';
                    }
                    if (isset($woocommerce->countries->countries[$country->country]))
                        echo '>' . esc_html__($country->country) . ' - ' . $woocommerce->countries->countries[$country->country] . ' (' . absint($country->count) . ')</option>';
                    else
                        echo '>' . esc_html__($country->country) . ' (' . absint($country->count) . ')</option>';
                }
                ?>
            </select>
            <?php
        }

        public static function state_filter()
        {
            global $wpdb;
            $order_states = self::get_filter_data('state');
            if (!is_array($order_states)) {
                $order_states = array();
            }
            ?>
            <select name='_customer_state' id='dropdown_state'>
                <option value=""><?php _e('Show all states', 'wc_crm'); ?></option>
                <?php

                foreach ($order_states as $state) {
                    if (!$state->state || $state->state == NULL || $state->state == '') continue;
                    echo '<option value="' . $state->state . '" ';
                    if (!empty($_REQUEST['_customer_state']) && $_REQUEST['_customer_state'] == $state->state) {
                        echo 'selected';
                    }
                    echo '>' . esc_html__($state->state) . ' (' . absint($state->count) . ')</option>';
                }
                ?>
            </select>
            <?php
        }

        public static function city_filter()
        {
            global $wpdb;
            $order_city = self::get_filter_data('city');
            if (!is_array($order_city)) {
                $order_city = array();
            }
            ?>
            <select name='_customer_city' id='dropdown_city'>
                <option value=""><?php _e('Show all cities', 'wc_crm'); ?></option>
                <?php

                foreach ($order_city as $city) {
                    if (!$city->city || $city->city == NULL || $city->city == '') continue;
                    echo '<option value="' . $city->city . '" ';
                    if (!empty($_REQUEST['_customer_city']) && $_REQUEST['_customer_city'] == $city->city) {
                        echo 'selected';
                    }
                    echo '>' . esc_html__($city->city) . ' (' . absint($city->count) . ')</option>';
                }
                ?>
            </select>
            <?php
        }

        public static function last_order_filter()
        {
            ?>
            <select name='_customer_date_from' id='dropdown_date_from'>
                <option value=""><?php _e('All time results', 'wc_crm'); ?></option>

                <option
                        value="<?php echo date('Y-m-d H:00:00', strtotime('-24 hours')); ?>" <?php if (!empty($_REQUEST['_customer_date_from']) && date('Y-m-d H:00:00', strtotime('-24 hours')) == $_REQUEST['_customer_date_from']) {
                    echo "selected";
                } ?> ><?php _e('Last 24 hours', 'wc_crm'); ?></option>

                <option
                        value="<?php echo date('Y-m-01 00:00:00', strtotime('this month')); ?>" <?php if (!empty($_REQUEST['_customer_date_from']) && date('Y-m-01 00:00:00', strtotime('this month')) == $_REQUEST['_customer_date_from']) {
                    echo "selected";
                } ?> ><?php _e('This month', 'wc_crm'); ?></option>
                <option
                        value="<?php echo date('Y-m-d 00:00:00', strtotime('-30 days')); ?>" <?php if (!empty($_REQUEST['_customer_date_from']) && date('Y-m-d 00:00:00', strtotime('-30 days')) == $_REQUEST['_customer_date_from']) {
                    echo "selected";
                } ?> ><?php _e('Last 30 days', 'wc_crm'); ?></option>
                <option
                        value="<?php echo date('Y-m-d 00:00:00', strtotime('-6 months')); ?>" <?php if (!empty($_REQUEST['_customer_date_from']) && date('Y-m-d 00:00:00', strtotime('-6 months')) == $_REQUEST['_customer_date_from']) {
                    echo "selected";
                } ?> ><?php _e('Last 6 months', 'wc_crm'); ?></option>
                <option
                        value="<?php echo date('Y-m-d 00:00:00', strtotime('-12 months')); ?>" <?php if (!empty($_REQUEST['_customer_date_from']) && date('Y-m-d 00:00:00', strtotime('-12 months')) == $_REQUEST['_customer_date_from']) {
                    echo "selected";
                } ?>><?php _e('Last 12 months', 'wc_crm'); ?></option>
            </select>
            <?php
        }

        public static function user_roles_filter()
        {
            global $wp_roles;
            ?>
            <select name='_user_type' id='dropdown_user_type'>
                <option value=""><?php _e('Show all user roles', 'wc_crm'); ?></option>
                <?php
                $woocommerce_crm_user_roles = get_option('wc_crm_user_roles');

                if (!$woocommerce_crm_user_roles || empty($woocommerce_crm_user_roles)) {
                    $woocommerce_crm_user_roles[] = 'customer';
                }
                $_user_type = isset($_REQUEST['_user_type']) ? $_REQUEST['_user_type'] : '';

                foreach ($wp_roles->role_names as $role => $name) :
                    $slug_name = strtolower($name);
                    if (!in_array($slug_name, $woocommerce_crm_user_roles)) continue;
                    ?>
                    <option value="<?php echo $slug_name; ?>" <?php selected($slug_name, $_user_type, true); ?> ><?php echo $name; ?></option>
                    <?php
                endforeach;

                $add_guest_customers = WC_Admin_Settings::get_option('wc_crm_guest_customers', 'no');
                if ($add_guest_customers == 'yes') {
                    ?>
                    <option value="guest_user" <?php selected('guest_user', $_user_type, true); ?> >
                        <?php _e('Guest', 'wc_crm'); ?>
                    </option>
                <?php } ?>
            </select>
            <?php
        }

        public static function products_variations_filter()
        {
            ?>
            <select name="_products_variations" class="wc-product-search"
                    data-action="wc_crm_json_search_variations" id="dropdown_products_and_variations"
                    data-multiple="true" data-placeholder="Search for a variations…"></select>
            <?php
        }

        public function order_status_filter()
        {
            global $wpdb;
            $wc_statuses = wc_get_order_statuses();

            $order_statuses = self::get_filter_data('order_status');

            if (!is_array($order_statuses)) {
                $order_statuses = array();
            }
            ?>
            <select name='_order_status' id='dropdown_order_status'>
                <option value=""><?php _e('Show all statuses', 'woocommerce'); ?></option>
                <?php
                if (!empty($order_statuses)) {
                    foreach ($order_statuses as $status) {

                        if (!isset($wc_statuses[$status->post_status])) continue;

                        echo '<option value="' . $status->post_status . '"';

                        if (isset($_REQUEST['_order_status'])) {
                            selected($status->post_status, $_REQUEST['_order_status']);
                        }

                        echo '>' . $wc_statuses[$status->post_status] . '</option>';
                    }
                }
                ?>
            </select>

            <?php
        }

        /****************/
        public static function products_categories_filter()
        {
            ?>
            <select name='_products_categories[]' id='dropdown_products_categories' class="wc-enhanced-select"
                    multiple="multiple" data-placeholder="<?php _e('Search for a category&hellip;', 'woocommerce'); ?>"
                    style="width: 400px">
                <?php
                $cat = array();
                if (isset($_REQUEST['_products_categories'])) {
                    $cat = $_REQUEST['_products_categories'];
                }
                $all_cat = get_terms(array('product_cat'), array('orderby' => 'name', 'order' => 'ASC'));
                if (!empty($all_cat)) {
                    foreach ($all_cat as $key => $value) {
                        echo '<option value="' . $value->term_id . '" ' . (in_array($value->term_id, $cat) ? 'selected="selected"' : '') . '>' . $value->name . '</option>';
                    }
                }
                ?>
            </select>

            <?php
        }

        public static function woocommerce_crm_products_brands_filter()
        {
            if (class_exists('WC_Brands_Admin')) {
                ?>
                <select name='_products_brands[]' id='dropdown_products_brands' multiple="multiple"
                        data-placeholder="<?php _e('Search for a brand&hellip;', 'woocommerce'); ?>">
                    <?php $brand = array();
                    if (isset($_REQUEST['_products_brands'])) {
                        $brand = $_REQUEST['_products_brands'];
                    }
                    $all_brands = get_terms(array('product_brand'), array('orderby' => 'name', 'order' => 'ASC'));
                    if (!empty($all_brands)) {
                        foreach ($all_brands as $key => $value) {
                            echo '<option value="' . $value->term_id . '" ' . (in_array($value->term_id, $brand) ? 'selected="selected"' : '') . '>' . $value->name . '</option>';
                        }
                    }
                    ?>
                </select>

                <?php
            }
        }

        public static function user_agents_filter()
        {
            global $wpdb;
            $_customer_user = isset($_REQUEST['agent']) ? (int)$_REQUEST['agent'] : 0;
            $user_string = '';
            if ($_customer_user > 0) {
                $user = get_user_by('id', $_customer_user);
                $user_string = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' - ' . esc_html($user->user_email);
            }
            ?>
            <select type="text" id="dropdown_agents" name="agent"
                    value="<?php echo isset($_REQUEST['agent']) ? $_REQUEST['agent'] : ''; ?>"
                    class="wc-crm-agent-search" data-placeholder="Search for an agent..."
                    data-allow_clea="true" data-selected="<?php echo htmlspecialchars($user_string); ?>">
                <?php if (isset($_REQUEST['agent'])) { ?>
                    <option value="<?php echo $_REQUEST['agent'] ?>"><?php echo htmlspecialchars($user_string); ?></option>
                <?php } ?>
            </select>
            <?php
        }

        public static function acf_filter($key, $value)
        {
            $acf_options = get_acf_fields_array();
            ?>
            <input type="text" name="<?php echo $value ?>"
                   value="<?php echo (isset($_GET[$value]) && !empty($_GET[$value])) ? $_GET[$value] : '' ?>"
                   placeholder="<?php echo $acf_options[$value] ?>">
        <?php }
    }

endif;