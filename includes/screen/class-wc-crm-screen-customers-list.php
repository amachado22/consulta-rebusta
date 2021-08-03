<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Screen_Customers_List')) :

    /**
     * WC_CRM_Screen_Customers_List Class
     */
    class WC_CRM_Screen_Customers_List
    {

        public static function output()
        {
            add_action('wc_crm_restrict_list_customers', 'WC_CRM_Screen_Customer_Filters::restrict_list_customers');

            echo '<div class="wrap" id="wc-crm-page">';
            self::page_title();

            $group = '';
            if (isset($_REQUEST['group']) && !empty($_REQUEST['group']) && $_REQUEST['group'] > 0)
                $group = '&group=' . $_REQUEST['group'];
            ?>
            <?php wc_crm_print_notices(); ?>
            <form method="get" id="wc_crm_customers_form" action="">
                <input type="hidden" name="page" value="<?php echo WC_CRM_TOKEN; ?>">
                <?php if (isset($_REQUEST['group']) && !empty($_REQUEST['group']) && $_REQUEST['group'] > 0) { ?>
                    <input type="hidden" name="group" value="<?php echo $_REQUEST['group']; ?>">
                <?php } ?>
                <?php
                $customers_table = WC_CRM()->tables['customers'];
                $customers_table->views();
                ?>
                <p class="search-box">
                    <?php
                    $ss = '';
                    if (!empty($_GET['s']))
                        $ss = $_GET['s'];
                    ?>
                    <label for="post-search-input" class="screen-reader-text"><?php _e('Search', 'wc_crm'); ?></label>
                    <input type="search" value="<?php echo $ss; ?>" name="s" id="post-search-input">
                    <input type="submit" value="<?php _e('Search Customers', 'wc_crm'); ?>" class="button"
                           id="search-submit" name="">
                </p>
                <?php
                $customers_table->prepare_items();
                $customers_table->display();
                ?>
            </form>
            <div id="customer_notes_popup" class="overlay_media_popup">
                <div class="media-modal wp-core-ui">
                    <a href="#" class="media-modal-close"><span class="media-modal-icon"><span
                                    class="screen-reader-text">Close</span></span></a>
                    <div class="media-modal-content">
                        <div class="media-frame mode-select wp-core-ui hide-menu">
                            <div class="media-frame-title"><h1>Customer Notes</h1></div>
                            <div class="media-frame-content">
                                <iframe src="" frameborder="0"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="media-modal-backdrop"></div>
            </div>
            <?php include_once 'views/html-customer-delete-form.php'; ?>
            </div>
            <?php
        }

        public static function page_title()
        {
            $title = __('Clientes', 'wc_crm');
            $s = '';
            if (isset($_REQUEST['s']) && !empty($_REQUEST['s']))
                $s = '<span class="subtitle">Resultados da busca por "' . $_GET['s'] . '"</span>';

            if ((isset($_REQUEST['group']) && !empty($_REQUEST['group']))) {
                $group_name = '';
                global $wpdb;
                $table_name = $wpdb->prefix . "wc_crm_groups";
                $id = $_REQUEST['group'];
                $db_data = $wpdb->get_results("SELECT * FROM $table_name WHERE ID = $id");
                $group_name = ' <i>"' . $db_data[0]->group_name . '"</i>';
                $title = __('Grupo de clientes ', 'wc_crm') . $group_name;
            }
            ?>
            <h2>
                <?php echo $title; ?>
                <a href="<?php echo admin_url(); ?>admin.php?page=<?php echo WC_CRM_TOKEN; ?>-new-customer"
                   class="add-new-h2"><?php _e('Adicionar Cliente', 'wc_crm'); ?></a>
                <a href="<?php echo admin_url(); ?>admin.php?page=<?php echo WC_CRM_TOKEN; ?>_import"
                   class="add-new-h2"><?php _e('Importar', 'wc_crm'); ?></a>
                <a href="<?php echo admin_url(); ?>index.php?page=<?php echo WC_CRM_TOKEN; ?>-setup&step=update_customers"
                   class="add-new-h2"><?php _e('Atualizar Cliente', 'wc_crm'); ?></a>
                <?php echo $s; ?>
            </h2>
            <?php

        }

        public static function messages()
        {
            if (isset($_GET['message']) && $_GET['message'] == 2 && isset($_GET['added_rows']) && !empty($_GET['added_rows'])) {
                echo '<div id="message" class="updated fade"><p>' . $_GET['added_rows'] . ' clientes foram importados com sucesso.</p></div>';
            }
        }

        public static function get_customers($args = array())
        {
            global $wpdb;

            $sql = self::get_sql($args);

            /*$transient_name = '_transient_wc_crm_get_customers'.md5($sql);


            if ( false === ( $result = get_transient( $transient_name ) ) ){*/
            $wpdb->query('SET SQL_BIG_SELECTS=1');
            $result = $wpdb->get_results($sql, ARRAY_A);

            /*			set_transient( $transient_name, $result, 0 );

                    }
            */
            //return $sql;
            return $result;
        }

        public static function get_customers_count()
        {

            global $wpdb;

            $sql = self::get_sql();

            /*$transient_name = '_transient_wc_crm_customers_count'.md5($sql);

            if ( false === ( $result = get_transient( $transient_name ) ) ){*/

            $result = count($wpdb->get_results($sql));

            /*			set_transient( $transient_name, $result, 0 );

                    }*/
            return $result;
        }

        public static function get_sql($args = array())
        {
            global $wpdb;
            $current_user = wp_get_current_user();

            $options = array(
                'total_value' => get_option('wc_crm_total_value', array('wc-completed')),
                'user_roles' => get_option('wc_crm_user_roles', array('customer')),
                'guest_customers' => get_option('wc_crm_guest_customers', 'no'),
                'customer_name' => get_option('wc_crm_customer_name', 'fl'),
            );

            $options = apply_filters('wc_crm_customers_list_options', $options);

            $user_role_filter = '';

            $arg_role = isset($args['role']) ? $args['role'] : null;

            if( $arg_role == 'agent'){
                $options['user_roles'] = array('agent');
            }

            foreach ($options['user_roles'] as $value) {
                if (!empty($user_role_filter)) $user_role_filter .= ' OR ';
                $user_role_filter .= "usm6.meta_value LIKE '%{$value}%'";
            }

            $user_role_filter = apply_filters('wc_crm_customers_list_user_role_filter', $user_role_filter);

            if (!empty($user_role_filter)) {
                $user_role_filter .= " OR customer.capabilities = '' ";
                $user_role_filter = ' AND (' . $user_role_filter . ' ) ';
            }

            $filter = array();
            $join = array();
            $inner = array();
            $select = array();

            if ((isset($_REQUEST['group']) && !empty($_REQUEST['group']))) {
                $group_id = $_REQUEST['group'];
                $c = self::get_customers_on_group($group_id);
                if (!empty($c)) {
                    $c = implode(',', $c);
                    $filter[] = " AND customer.c_id IN({$c}) ";
                }
            }

            if ($options['guest_customers'] != 'yes' || (isset($args['guest_customers']) && $args['guest_customers'] != 'yes')) {
                $filter[] = ' AND customer.user_id > 0';
            }
            if (isset($_REQUEST['_user_type']) && !empty($_REQUEST['_user_type'])) {
                $_user_type = $_REQUEST['_user_type'];
                if ($_user_type == 'guest_user') {
                    $filter[] = " AND customer.capabilities = '' ";
                } else {
                    $filter[] = " AND customer.capabilities LIKE '%{$_user_type}%' ";
                }
            }
            if (isset($_REQUEST['_customer_date_from']) && !empty($_REQUEST['_customer_date_from'])) {
                $filter[] = " AND  DATE(customer.last_purchase) >= '" . date('Y-m-d', strtotime($_REQUEST['_customer_date_from'])) . "' ";
            }
            if (isset($_REQUEST['_customer_state']) && !empty($_REQUEST['_customer_state'])) {
                $filter[] = " AND customer.state = '" . $_REQUEST['_customer_state'] . "' ";
            }
            if (isset($_REQUEST['_customer_city']) && !empty($_REQUEST['_customer_city'])) {
                $filter[] = " AND customer.city = '" . $_REQUEST['_customer_city'] . "' ";
            }
            if (isset($_REQUEST['_customer_country']) && !empty($_REQUEST['_customer_country'])) {
                $filter[] = " AND customer.country = '" . $_REQUEST['_customer_country'] . "' ";
            }

            if( $arg_role == 'agent'){
                $filter[] = "";
            }elseif (isset($_REQUEST['_customer_status']) && !empty($_REQUEST['_customer_status'])) {
                $filter[] = " AND customer.status = '" . $_REQUEST['_customer_status'] . "' ";
            } else {
                $filter[] = " AND customer.status != 'trashed' ";
            }
            if (isset($_REQUEST['_customer_user']) && !empty($_REQUEST['_customer_user'])) {
                $filter[] = " AND customer.c_id = " . $_REQUEST['_customer_user'] . " ";
            }

            if ((isset($_REQUEST['_customer_product']) && !empty($_REQUEST['_customer_product']))
                || (isset($_REQUEST['_products_variations']) && !empty($_REQUEST['_products_variations']))
                || (isset($_REQUEST['_order_status']) && !empty($_REQUEST['_order_status']))
                || (isset($_REQUEST['_products_categories']) && !empty($_REQUEST['_products_categories']))
                || (isset($_REQUEST['_products_brands']) && !empty($_REQUEST['_products_brands']))
            ) {
                if ($options['guest_customers'] == 'yes') {
                    $inner[] = "
		      inner join {$wpdb->postmeta} as postmeta on ( (postmeta.meta_key = '_customer_user' AND postmeta.meta_value = customer.user_id AND customer.user_id <> 0) OR (postmeta.meta_key = '_billing_email' AND postmeta.meta_value = customer.email AND  customer.user_id = 0 ) )
		      ";
                } else {
                    $inner[] = "
		      inner join {$wpdb->postmeta} as postmeta on ( postmeta.meta_key = '_customer_user' AND postmeta.meta_value = customer.user_id )
		      ";
                }
            }

            $agent_visibility_type = get_option('wc_crm_agent_customer_view', 'only_allowed');
            if (isset($_REQUEST['agent']) && !empty($_REQUEST['agent']) && $_REQUEST['agent'] != -1 && $agent_visibility_type !== "all") {
                $inner[] = "INNER JOIN
                (SELECT cust.`c_id`
                FROM {$wpdb->prefix}wc_crm_customer_list as cust
                INNER JOIN {$wpdb->usermeta} pm 
                ON pm.`user_id` = cust.`user_id` 
                AND pm.`meta_key` = 'customer_agent'
                AND pm.`meta_value` = '{$_REQUEST['agent']}'
                UNION
                SELECT cust.`c_id`
                FROM {$wpdb->prefix}wc_crm_customer_list as cust
                INNER JOIN {$wpdb->prefix}wc_crm_customermeta cm 
                ON cm.`wc_crm_customer_id` = cust.`c_id` 
                AND cm.`meta_key` = 'customer_agent'
                AND cm.`meta_value` = '{$_REQUEST['agent']}'
                ) c
                ON c.`c_id` = customer.`c_id`
                ";
            }

            if ($current_user->has_cap('agent') && $agent_visibility_type !== "all") {
                $customer = new WC_CRM_Customer($current_user);
                $inner[] = "INNER JOIN
                (SELECT cust.`c_id`
                FROM {$wpdb->prefix}wc_crm_customer_list as cust
                INNER JOIN {$wpdb->usermeta} pm 
                ON pm.`user_id` = cust.`user_id` 
                AND pm.`meta_key` = 'customer_agent'
                AND pm.`meta_value` = '{$customer->c_id}'
                UNION
                SELECT cust.`c_id`
                FROM {$wpdb->prefix}wc_crm_customer_list as cust
                INNER JOIN {$wpdb->prefix}wc_crm_customermeta cm 
                ON cm.`wc_crm_customer_id` = cust.`c_id` 
                AND cm.`meta_key` = 'customer_agent'
                AND cm.`meta_value` = '{$customer->c_id}'
                WHERE cust.`user_id` != 0
                ) c
                ON c.`c_id` = customer.`c_id`
                ";
            }

            $products_ids_sql = '';
            if ((isset($_REQUEST['_customer_product']) && !empty($_REQUEST['_customer_product']))
                || (isset($_REQUEST['_products_variations']) && !empty($_REQUEST['_products_variations']))
                || (isset($_REQUEST['_order_status']) && !empty($_REQUEST['_order_status']))
                || (isset($_REQUEST['_products_categories']) && !empty($_REQUEST['_products_categories']))
                || (isset($_REQUEST['_products_brands']) && !empty($_REQUEST['_products_brands']))
            ) {
                $products_ids_sql .= "SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
	      	inner join {$wpdb->prefix}woocommerce_order_items as order_items ON ( order_items.order_id = postmeta.post_id )
	      ";
            }
            if ((isset($_REQUEST['_customer_product']) && !empty($_REQUEST['_customer_product']))
                || (isset($_REQUEST['_products_categories']) && !empty($_REQUEST['_products_categories']))
                || (isset($_REQUEST['_products_brands']) && !empty($_REQUEST['_products_brands']))
            ) {
                $products_ids_sql .= "     
	      inner join  {$wpdb->prefix}woocommerce_order_itemmeta as product on ( product.order_item_id = order_items.order_item_id and product.meta_key = '_product_id' ) ";
            }

            if (isset($_REQUEST['_products_variations']) && !empty($_REQUEST['_products_variations'])) {
                $products_ids_sql .= "
	        inner join  {$wpdb->prefix}woocommerce_order_itemmeta as variation on (variation.order_item_id =  order_items.order_item_id and variation.meta_key = '_variation_id' ) 
	        ";
            }

            if ((isset($_REQUEST['_products_categories']) && !empty($_REQUEST['_products_categories']))
                || (isset($_REQUEST['_products_brands']) && !empty($_REQUEST['_products_brands']))
            ) {
                $tax = '';
                if (isset($_REQUEST['_products_categories'])) $tax .= "taxonomy.taxonomy = 'product_cat'";
                if (isset($_REQUEST['_products_brands'])) {
                    if (!empty($tax))
                        $tax .= ' OR ';
                    $tax .= "taxonomy.taxonomy = 'product_brand'";
                }
                $products_ids_sql .= "
	          inner join  {$wpdb->prefix}term_relationships as relationships on (relationships.object_id =  product.meta_value ) 
	          inner join  {$wpdb->prefix}term_taxonomy as taxonomy on (relationships.term_taxonomy_id = taxonomy.term_taxonomy_id AND ($tax) ) 
	          ";
            }

            if (isset($_REQUEST['_order_status']) && !empty($_REQUEST['_order_status'])) {
                $request = $_REQUEST['_order_status'];

                if (is_array($request)) {
                    $products_ids_sql .= "
	              inner JOIN {$wpdb->posts} posts_status
	              ON (postmeta.post_id= posts_status.ID AND posts_status.post_status IN( '" . implode("', '", $request) . "') AND posts_status.post_type =  'shop_order' )
	        ";
                } else if (is_string($request)) {
                    $products_ids_sql .= "
	              inner JOIN {$wpdb->posts} posts_status
	              ON (postmeta.post_id= posts_status.ID AND posts_status.post_status = '{$request}'  AND posts_status.post_type =  'shop_order' )
	          ";
                }

            }
            if (!empty($products_ids_sql)) {
                $products_ids_sql .= " WHERE postmeta.meta_key = '_order_key' ";
            }

            if (isset($_REQUEST['_products_categories']) && !empty($_REQUEST['_products_categories'])) {
                $y = '';
                $match = 'AND';
                if (isset($_REQUEST['__match']) && $_REQUEST['__match'] == 'OR') {
                    $match = 'OR';
                }
                foreach ($_REQUEST['_products_categories'] as $v) {
                    if ($y) {
                        $ff .= ' OR ';
                    } else {
                        $y = 'OR';
                        $ff = '
	            ' . $match . ' (';
                    }
                    $ff .= " (taxonomy.term_id = " . $v . " AND taxonomy.taxonomy = 'product_cat' )";
                }
                $products_ids_sql .= $ff . ')';

            }
            if (isset($_REQUEST['_products_brands']) && !empty($_REQUEST['_products_brands'])) {
                $y = '';
                foreach ($_REQUEST['_products_brands'] as $v) {
                    if ($y) {
                        $ff .= ' OR ';
                    } else {
                        $y = 'OR';
                        $ff = '
	            AND (';
                    }
                    $ff .= " (taxonomy.term_id = " . $v . " AND taxonomy.taxonomy = 'product_brand' )";
                }
                $products_ids_sql .= $ff . ')';
            }

            if (isset($_REQUEST['_customer_product']) && !empty($_REQUEST['_customer_product'])) {
                $products_ids_sql .= " AND product.meta_value = " . $_REQUEST['_customer_product'];
            }

            if (isset($_REQUEST['_products_variations']) && !empty($_REQUEST['_products_variations'])) {
                $y = '';
                $products_variations = $_REQUEST['_products_variations'];
                if (!is_array($products_variations))
                    $products_variations = explode(',', $products_variations);
                foreach ($products_variations as $v) {
                    if ($y) {
                        $ff .= ' OR ';
                    } else {
                        $y = ' OR ';
                        $ff = ' AND (';
                    }
                    $ff .= 'variation.meta_value = ' . $v;
                }
                $products_ids_sql .= $ff . ')';
            }

            if (!empty($products_ids_sql)) {
                $prod_filter = $wpdb->get_results($products_ids_sql);
                if ($prod_filter) {
                    $post_ids = array();
                    foreach ($prod_filter as $value) {
                        $post_ids[] = $value->post_id;
                    }
                    $filter[] = " AND postmeta.post_id IN(" . implode(', ', $post_ids) . ")";
                }
            }

            if ((isset($_REQUEST['s']) && !empty($_REQUEST['s'])) || (isset($_REQUEST['term']) && !empty($_REQUEST['term']))) {
                $term = isset($_REQUEST['s']) ? $_REQUEST['s'] : $_REQUEST['term'];
                $search_options = get_option('wc_crm_search_options');
                if ($search_options) {
                    $filter_str = " AND ( ";
                    $join_usermeta = false;
                    $join_cmeta = false;
                    foreach ($search_options as $key => $search_option) {
                        if ($key) {
                            $filter_str .= " OR ";
                        }
                        switch ($search_option) {
                            case 'first_name':
                                $filter_str .= "LOWER(customer.first_name) LIKE LOWER('%$term%')";
                                break;
                            case 'last_name':
                                $filter_str .= "LOWER(customer.last_name) LIKE LOWER('%$term%')";
                                break;
                            case 'email':
                                $filter_str .= "LOWER(customer.email) LIKE LOWER('%$term%')";
                                break;
                            case 'fax':
                            case 'twitter':
                            case 'skype':
                                $join_cmeta = true;
                                $filter_str .= "( LOWER(um.meta_value) LIKE LOWER('%$term%') AND um.meta_key ='$search_option')";
                                break;
                            default:
                                $join_usermeta = true;
                                $filter_str .= "( LOWER(um.meta_value) LIKE LOWER('%$term%') AND um.meta_key ='$search_option')";
                                break;
                        }
                    }
                    $filter_str .= " ) ";
                    $filter[] = $filter_str;
                    if ($join_usermeta) {
                        $inner[] = " INNER JOIN {$wpdb->usermeta} um ON customer.user_id = um.user_id";
                    }
                    if ($join_cmeta) {
                        $inner[] = " INNER JOIN {$wpdb->prefix}wc_crm_customermeta custm ON customer.user_id = custm.wc_crm_customer_id";
                    }
                } else {
                    $filter[] = " AND (
	          LOWER(customer.first_name) LIKE LOWER('%$term%') 
	          OR LOWER(customer.last_name) LIKE LOWER('%$term%') 
	          OR LOWER(customer.email) LIKE LOWER('%$term%')
	          OR concat_ws(' ',customer.first_name,customer.last_name) LIKE '%$term%'
	          OR LOWER(usm5.meta_value) LIKE LOWER('%$term%')
	        )";
                }
            }

            if (class_exists('acf')) {
                foreach ($_REQUEST as $key => $value) {
                    if (strpos($key, 'acf_') !== false && $value) {
                        $meta_key = esc_sql(str_replace('acf_', '', $key));
                        $value = esc_sql($value);
                        $inner[] = " INNER JOIN {$wpdb->usermeta} {$key} ON customer.user_id = {$key}.user_id 
                                    AND {$key}.meta_key = '{$meta_key}' AND {$key}.meta_value LIKE '%$value%'";
                    }
                }
            }

            $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'customer_name';
            $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
            $_order = "ORDER BY customer.{$orderby} {$order}";
            if ($orderby == 'customer_name') {
                if ($options['customer_name'] == 'fl') {
                    $_order = "ORDER BY customer.first_name {$order}, customer.last_name {$order}";
                } else {
                    $_order = "ORDER BY customer.last_name {$order}, customer.first_name {$order}";
                }
            }
            if ($orderby == 'customer_agent') {
                $_order = "ORDER BY agent_firstname {$order}, agent_lastame  {$order}";
            }
            $limit = '';
            if (isset($args['current_page']) && isset($args['per_page'])) {
                $current_page = (int)$args['current_page'];
                $per_page = (int)$args['per_page'];
                $offset = $current_page * $per_page;
                $limit = "LIMIT {$offset}, {$per_page}";
            }
            if (isset($args['account_id']) && isset($args['account_id'])) {
                $c_ids = WC_Crm_Accounts::get_customers_of_account($args['account_id']);
                $c_ids = count($c_ids) ? implode(',', $c_ids) : implode(',', array(0));
                $filter[] = " AND customer.c_id IN({$c_ids})";
            }

            if (isset($_REQUEST['customer_watching']) && !empty($_REQUEST['customer_watching'])) {
                $join[] = "LEFT JOIN {$wpdb->usermeta} usm6 ON customer.user_id = usm6.user_id";
                $filter[] = " AND usm6.meta_key = 'customer_watching' AND usm6.meta_value = '" . $_REQUEST['customer_watching'] . "' ";
            }

            $filter = implode(' ', $filter);
            $join = implode(' ', $join);
            $inner = implode(' ', $inner);

            $sql = "SELECT 
                customer.*, 
                usm.meta_value as customer_agent,
                usm2.meta_value as agent_firstname,
                usm3.meta_value as agent_lastame,
                usm4.meta_value as date_of_birth,
                usm5.meta_value as phone,
                a_us.user_login as agent_username
                FROM {$wpdb->prefix}wc_crm_customer_list as customer
                LEFT JOIN {$wpdb->usermeta} usm ON customer.user_id = usm.user_id AND usm.meta_key = 'customer_agent'
                LEFT JOIN {$wpdb->prefix}wc_crm_customer_list agent ON usm.meta_value = agent.c_id
                LEFT JOIN {$wpdb->usermeta} usm2 ON agent.user_id = usm2.user_id AND usm2.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} usm3 ON agent.user_id = usm3.user_id AND usm3.meta_key = 'last_name'
                LEFT JOIN {$wpdb->usermeta} usm4 ON customer.user_id = usm4.user_id AND usm4.meta_key = 'date_of_birth'
                LEFT JOIN {$wpdb->users} a_us ON agent.user_id = a_us.ID
                LEFT JOIN {$wpdb->usermeta} usm5 ON customer.user_id = usm5.user_id AND usm5.meta_key = 'billing_phone'
                LEFT JOIN {$wpdb->usermeta} usm6 ON customer.user_id = usm6.user_id AND usm6.meta_key = '{$wpdb->prefix}capabilities'
	    		{$join}
	    		{$inner}
    			WHERE 1=1
    			{$user_role_filter}
    			{$filter}
    			GROUP BY customer.c_id, usm.meta_value
    			{$_order} {$limit}
		        ";
            global $wpdb;
            #echo '<textarea name="" id="" style="width: 100%; height: 200px; ">' . $sql . '</textarea>';
            return $sql;
        }

        public static function get_customers_on_group($group_id = 0)
        {
            global $wpdb;
            if ($group_id < 0) return array();

            $ids = array();
            $group_data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wc_crm_groups WHERE ID = $group_id LIMIT 1");
            if ($group_data->group_type == 'static') {

                $result = $wpdb->get_results("SELECT c_id FROM {$wpdb->prefix}wc_crm_groups_relationships WHERE group_id = {$group_id}", ARRAY_N);
                if ($result) {
                    foreach ($result as $key => $c_id) {
                        $ids[] = $c_id[0];
                    }
                }
                return $ids;

            } else if ($group_data->group_type == 'dynamic') {
                $match = $group_data->group_match == 0 ? 'AND' : 'OR';
                $_REQUEST['__match'] = $match;
                $filter = '';
                $inner = '';
                if (!empty($group_data->group_total_spent)) {
                    $spent = $group_data->group_total_spent;
                    $mark = $group_data->group_total_spent_mark;
                    switch ($mark) {
                        case 'greater':
                            $mark = '>';
                            break;
                        case 'less':
                            $mark = '<';
                            break;
                        case 'greater_or_equal':
                            $mark = '>=';
                            break;
                        case 'less_or_equal':
                            $mark = '<=';
                            break;
                        default:
                            $mark = '=';
                            break;
                    }
                    $filter .= " {$match} customer.order_value $mark $spent ";
                }
                if (!empty($group_data->group_user_role)) {
                    $group_user_role = $group_data->group_user_role;
                    if ($group_user_role != 'any') {
                        if ($group_user_role == 'guest') {
                            $filter .= "{$match} customer.user_id = 0
						";
                        } else {
                            $filter .= "{$match} customer.capabilities LIKE '%" . $group_user_role . "%'
						";
                        }
                    }
                }
                if (!empty($group_data->group_customer_status)) {
                    $group_customer_status = unserialize($group_data->group_customer_status);
                    if (!empty($group_customer_status)) {
                        if (count($group_customer_status) > 1 || !empty($group_customer_status[0]))
                            $filter .= "{$match} customer.status IN( '" . implode("', '", $group_customer_status) . "' )
					";
                    }
                }

                if (!empty($group_data->group_order_status)) {
                    $group_order_status = unserialize($group_data->group_order_status);
                    if (!empty($group_order_status)) {
                        if (count($group_order_status) > 1 || !empty($group_order_status[0]))
                            $_REQUEST['_order_status'] = $group_order_status;
                    }
                }
                if (!empty($group_data->group_product_categories)) {
                    $group_product_categories = unserialize($group_data->group_product_categories);
                    if (!empty($group_product_categories)) {
                        if (count($group_product_categories) > 1 || !empty($group_product_categories[0]))
                            $_REQUEST['_products_categories'] = $group_product_categories;
                    }
                }

                $d_from = false;
                $order_from = strtotime($group_data->group_last_order_from);
                $order_to = strtotime($group_data->group_last_order_to);
                if (!empty($group_data->group_last_order_from) && $order_from !== false && $order_from > 0) {
                    $d_from = strtotime($group_data->group_last_order_from);
                }
                $d_to = false;
                if (!empty($group_data->group_last_order_to) && $order_to !== false && $order_to > 0) {
                    $d_to = strtotime($group_data->group_last_order_to);
                }
                if ($d_to || $d_from) {
                    $mark = $group_data->group_last_order;
                    switch ($mark) {
                        case 'before':
                            $filter .= "{$match} (DATE(customer.last_purchase) <= '" . date('Y-m-d', $d_from) . "' AND customer.last_purchase <> '0000-00-00 00:00:00')
					";
                            break;
                        case 'after':
                            $filter .= "{$match} (DATE(customer.last_purchase) >= '" . date('Y-m-d', $d_from) . "' AND customer.last_purchase <> '0000-00-00 00:00:00')
					";
                            break;
                        case 'between':
                            $filter .= "{$match} (DATE(customer.last_purchase) >= '" . date('Y-m-d', $d_from) . "' AND  DATE(customer.last_purchase) <= '" . date('Y-m-d', $d_to) . ")'
					";
                            break;
                    }
                }
                if ($group_data->group_match == 0) {
                    $filter = 'WHERE 1=1 ' . $filter;
                } else {
                    $filter = 'WHERE 1!=1 ' . $filter;
                }
                $sql = "SELECT customer.c_id FROM {$wpdb->prefix}wc_crm_customer_list as customer    			
    			{$filter}
    			GROUP BY customer.c_id
		        ";

                #echo '<textarea name="" id="" style="width: 100%; height: 200px; ">'.$sql.'</textarea>';die;

                $transient_name = '_transient_wc_crm_customers_on_group' . md5($sql);

                /*if ( false === ( $result = get_transient( $transient_name ) ) ){

                    $result = $wpdb->get_results($sql);

                    set_transient( $transient_name, $result, 0 );

                }*/
                $result = $wpdb->get_results($sql);

                if ($result) {
                    foreach ($result as $key => $customer) {
                        $ids[] = $customer->c_id;
                    }
                }
                if (empty($ids)) {
                    $ids[] = 0;
                }
                return $ids;
            }
        }

        public static function get_dynamic_group_sql($group_id = 0)
        {
            global $wpdb;
            if ($group_id < 0) return array();

            $ids = array();
            $group_data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wc_crm_groups WHERE ID = $group_id LIMIT 1");
            if ($group_data->group_type == 'dynamic') {
                $match = $group_data->group_match === 0 ? 'AND' : 'OR';
                $_REQUEST['__match'] = $match;
                $filter = '';
                $inner = '';
                if (!empty($group_data->group_total_spent)) {
                    $spent = $group_data->group_total_spent;
                    $mark = $group_data->group_total_spent_mark;
                    switch ($mark) {
                        case 'greater':
                            $mark = '>';
                            break;
                        case 'less':
                            $mark = '<';
                            break;
                        case 'greater_or_equal':
                            $mark = '>=';
                            break;
                        case 'less_or_equal':
                            $mark = '<=';
                            break;
                        default:
                            $mark = '=';
                            break;
                    }
                    $filter .= " {$match} customer.order_value $mark $spent ";

                }
                if (!empty($group_data->group_user_role)) {
                    $group_user_role = $group_data->group_user_role;
                    if ($group_user_role != 'any') {
                        if ($group_user_role == 'guest') {
                            $filter .= "{$match} customer.user_id = 0
						";
                        } else {
                            $filter .= "{$match} customer.capabilities LIKE '%" . $group_user_role . "%'
						";
                        }
                    }
                }
                if (!empty($group_data->group_customer_status)) {
                    $group_customer_status = unserialize($group_data->group_customer_status);
                    if (!empty($group_customer_status)) {
                        if (count($group_customer_status) > 1 || !empty($group_customer_status[0]))
                            $filter .= "{$match} customer.status IN( '" . implode("', '", $group_customer_status) . "' )
					";
                    }
                }

                if (!empty($group_data->group_order_status)) {
                    $group_order_status = unserialize($group_data->group_order_status);
                    if (!empty($group_order_status)) {
                        if (count($group_order_status) > 1 || !empty($group_order_status[0]))
                            $_REQUEST['_order_status'] = $group_order_status;
                    }
                }
                if (!empty($group_data->group_product_categories)) {
                    $group_product_categories = unserialize($group_data->group_product_categories);
                    if (!empty($group_product_categories)) {
                        if (count($group_product_categories) > 1 || !empty($group_product_categories[0]))
                            $_REQUEST['_products_categories'] = $group_product_categories;
                    }
                }

                $d_from = false;
                $order_from = strtotime($group_data->group_last_order_from);
                $order_to = strtotime($group_data->group_last_order_to);
                if (!empty($group_data->group_last_order_from) && $order_from !== false && $order_from > 0) {
                    $d_from = strtotime($group_data->group_last_order_from);
                }
                $d_to = false;
                if (!empty($group_data->group_last_order_to) && $order_to !== false && $order_to > 0) {
                    $d_to = strtotime($group_data->group_last_order_to);
                }
                if ($d_to || $d_from) {
                    $mark = $group_data->group_last_order;
                    switch ($mark) {
                        case 'before':
                            $filter .= "{$match} (DATE(customer.last_purchase) <= '" . date('Y-m-d', $d_from) . "' AND customer.last_purchase <> '0000-00-00 00:00:00)'
					";
                            break;
                        case 'after':
                            $filter .= "{$match} (DATE(customer.last_purchase) >= '" . date('Y-m-d', $d_from) . "' AND customer.last_purchase <> '0000-00-00 00:00:00)'
					";
                            break;
                        case 'between':
                            $filter .= "{$match} (DATE(customer.last_purchase) >= '" . date('Y-m-d', $d_from) . "' AND  DATE(customer.last_purchase) <= '" . date('Y-m-d', $d_to) . ")'
					";
                            break;
                    }
                }
                if ($group_data->group_match === 0) {
                    $filter = 'WHERE 1=1 ' . $filter;
                } else {
                    $filter = 'WHERE 1!=1 ' . $filter;
                }
                $sql = "SELECT customer.c_id FROM {$wpdb->prefix}wc_crm_customer_list as customer    			
    			{$filter}
    			GROUP BY customer.c_id
		        ";
                return $sql;
            }
        }
    }

endif;