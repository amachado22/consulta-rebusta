<?php
/**
 * Table with list of customers.
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WC_CRM_Table_Customers extends WP_List_Table
{
    protected $data;
    protected $found_data;

    public $pending_count = array();
    public $mailchimp = array();

    function __construct()
    {
        parent::__construct(array(
            'singular' => __('customer', 'wc_crm'), //singular name of the listed records
            'plural' => __('customers', 'wc_crm'), //plural name of the listed records
            'ajax' => false //does this table support ajax?
            //'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
        ));

        add_action('admin_head', array(&$this, 'admin_header'));

        if (woocommerce_crm_mailchimp_enabled()) {
            $this->mailchimp = woocommerce_crm_get_members();
        }
    }

    function admin_header()
    {
        $page = (isset($_GET['page'])) ? esc_attr($_GET['page']) : false;
        if (WC_CRM_TOKEN != $page)
            return;
        echo '<style type="text/css">';
        if (woocommerce_crm_mailchimp_enabled()) {
            echo '.wp-list-table .column-id {}';
            echo '.wp-list-table .column-customer_status { width: 14ch;}';
            echo '.wp-list-table .column-customer_name {width: 25ch;}';
            echo '.wp-list-table .column-email { width: 15%;}';
            echo '.wp-list-table .column-location { width: 10%;}';
            echo '.wp-list-table .column-customer_agent { width: 10%;}';
            echo '.wp-list-table .column-last_purchase { width: 10%;}';
            echo '.wp-list-table .column-order_value { width: 13ch;}';
            echo '.wp-list-table .column-enrolled { width: 45px;}';
            echo '.wp-list-table .column-customer_notes { width: 48px;}';
            echo '.wp-list-table .column-crm_actions { width: 120px;}';
        } else {
            echo '.wp-list-table .column-id {}';
            echo '.wp-list-table .column-customer_status { width: 14ch;}';
            echo '.wp-list-table .column-customer_name { width: 25ch;}';
            echo '.wp-list-table .column-email { width: 15%;}';
            echo '.wp-list-table .column-location { width: 10%;}';
            echo '.wp-list-table .column-customer_agent { width: 10%;}';
            echo '.wp-list-table .column-last_purchase { width: 10%;}';
            echo '.wp-list-table .column-order_value { width: 13ch;}';
            echo '.wp-list-table .column-customer_notes { width: 48px;}';
            echo '.wp-list-table .column-crm_actions { width: 120px;}';
        }
        echo '</style>';
    }

    public function display_tablenav($which)
    {
        $screen = get_current_screen();
        if ($screen->id != 'wc_crm_accounts') {
            parent::display_tablenav($which);
        }
    }

    function no_items()
    {
        $screen = get_current_screen();
        if ($screen->id == 'wc_crm_accounts') {
            _e('Customers not found.', 'wc_crm');
        } else {
            _e('Customers not found. Try to adjust the filter.', 'wc_crm');
        }
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'customer_status':
            case 'customer_name':
            case 'email':
            case 'last_purchase':
            case 'order_value':
            case 'enrolled':
            case 'customer_notes':
            case 'wc_group':
            case 'wc_subscriber':
            case 'crm_actions':
            case 'customer_agent':
                return $item[$column_name];
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns()
    {
        $screen = get_current_screen();
        if ($screen->id == 'wc_crm_accounts') {
            $sortable_columns = array();
        } else {
            $sortable_columns = array(
                'customer_name' => array('customer_name', true),
                'order_value' => array('order_value', true),
                'last_purchase' => array('last_purchase', true),
                'customer_agent' => array('customer_agent', true),
            );
        }
        return $sortable_columns;
    }

    function get_columns()
    {
        $screen = get_current_screen();
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'customer_name' => __('Clientes', 'wc_crm'),
            'email' => __('Contato', 'wc_crm'),
            'customer_status' => __('Status', 'wc_crm'),
            'customer_agent' => __('Agent', 'wc_crm'),
            'customer_notes' => '<span class="ico_notes tips" data-tip="' . esc_attr__('Notas do cliente', 'wc_crm') . '">' . esc_attr__('Customer Notes', 'wc_crm') . '</span>',
            'last_purchase' => __('Último pedido', 'wc_crm'),
            'order_value' => __('Dinheiro gasto', 'wc_crm'),
        );
        if (woocommerce_crm_mailchimp_enabled()) {
            $columns['enrolled'] = '<span class="ico_news tips" data-tip="' . esc_attr__('Newsletter', 'wc_crm') . '">' . esc_attr__('Newsletter', 'wc_crm') . '</span>';
        }

        $wc_subscriptions = get_option('wc_crm_show_subscribers_column');
        if (class_exists('WC_Subscriptions') && $wc_subscriptions == 'yes') {
            $columns['wc_subscriber'] = __('Subscribed', 'wc_crm');
        }

        $groups_wc = get_option('wc_crm_show_groups_wc_column');
        if (class_exists('Groups_WordPress') && class_exists('Groups_WS') && $groups_wc == 'yes') {
            $columns['wc_group'] = __('Group', 'wc_crm');
        }

        $columns['crm_actions'] = __('Actions', 'wc_crm');

        if ($screen->id == 'wc_crm_accounts') {
            unset($columns['crm_actions']);
            unset($columns['cb']);
        }

        $current_status = (!empty($_REQUEST['_customer_status']) ? $_REQUEST['_customer_status'] : 'all');
        if ($current_status == 'trashed') {
            unset($columns['customer_status']);
            unset($columns['crm_actions']);
        }

        $columns = apply_filters('wc_pos_customer_custom_column', $columns);
        return $columns;
    }

    function usort_reorder($a, $b)
    {
        // If no sort, default to last purchase
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'order_value';
        // If no order, default to desc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
        // Determine sort order
        if ($orderby == 'order_value') {
            $result = $a[$orderby] - $b[$orderby];
        } else {
            $result = strcasecmp($a[$orderby], $b[$orderby]);
        }
        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    protected function bulk_actions($which = '')
    {
        if ( is_null( $this->_actions ) ) {
            $this->_actions = $this->get_bulk_actions();
            $this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
            $two = '';
        } else {
            $two = '2';
        }

        if ( empty( $this->_actions ) )
            return;

        echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
        echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
        echo '<option value="-1">' . __( 'Bulk Actions' ) . "</option>\n";

        foreach ( $this->_actions as $name => $title ) {
            $class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

            echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
        }

        echo "</select>\n";

        submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );

        $classes = isset($_GET['customer_watching']) ? "button watching-button button-primary" : "button watching-button";

        echo "<a href='admin.php?page=wc_crm&customer_watching=1' id='doaction' class='".$classes." tips' data-tip='" .  __('Pinned Customers', 'wc_crm') . "'></a>";
        echo "\n";
    }

    function get_bulk_actions()
    {
        $screen = get_current_screen();
        if ($screen->id != 'wc_crm_accounts') {

            if (isset($_GET['_customer_status']) && $_GET['_customer_status'] == 'trashed') {
                $actions['untrash'] = __('Restore');
                $actions['delete'] = __('Delete Permanently');
            } else {
                $actions = array(
                    'email' => __('Send Email', 'wc_crm'),
                    'export_csv' => __('Export Contacts', 'wc_crm'),
                );
                $statuses = wc_crm_get_statuses();
                foreach ($statuses as $status) {
                    $actions['mark_as_' . $status->status_slug] = sprintf(__('Change status to %s', 'wc_crm'), strtolower($status->status_slug));
                }
                $groups = wc_get_static_groups();

                foreach ($groups as $group) {
                    $actions['crm_add_to_group_' . $group->ID] = sprintf(__('Add to %s', 'wc_crm'), $group->group_name);
                }
                $actions['trash'] = __('Move to Bin');
            }
            return $actions;
        }
        return array();
    }

    function column_cb($item)
    {
        return '<label class="screen-reader-text" for="cb-select-' . $item['c_id'] . '">' . sprintf(__('Select %s'), $item['first_name'] . ' ' . $item['last_name']) . '</label>'
            . "<input type='checkbox' name='customer_id[]' id='customer_" . $item['c_id'] . "' value='" . $item['c_id'] . "' />";
    }

    function column_customer_status($item)
    {
        if ($item['status'] && !empty($item['status'])) {
            $default_statuses = WC_CRM()->statuses;
            $_status = $item['status'];

            if (array_key_exists($_status, $default_statuses)) {
                $customer_status = '<div style="position: relative;  float: left;"><span class="' . $_status . '" data-tip="' . esc_attr($_status) . '">' . $_status . '</span></div>';
            } else {
                $custom_status = wc_crm_get_status_by_slug($_status);
                if ($custom_status) {
                    $s = wc_crm_get_status_icon_code($custom_status['status_icon']);
                    $customer_status = sprintf('<div><span style="background: %s40; color: %s; position: relative;  float: left;" class="customer_status_custom"><i data-icomoon="%s" data-fip-value="%s"></i>' . esc_attr($custom_status['status_name']) . '</span></div>', $custom_status['status_colour'], $custom_status['status_colour'], $s, $custom_status['status_icon']);
                } else {
                    $customer_status = '<div style="position: relative;">' . $_status . '</div>';
                }
            }
        } else {
            $customer_status = '<div style="position: relative; float: left;"><span class="Customer" data-tip="Customer">Customer</span></div>';
        }
        return $customer_status;
    }

    function column_customer_name($item)
    {
        global $the_customer;

        $edit = '';
        if (get_option('wc_crm_customer_name', 'fl') == 'fl') {
            $name = '<strong>' . $the_customer->first_name . '</strong> ' . $the_customer->last_name;
        } else {
            $name = $the_customer->last_name . ' <strong>' . $the_customer->first_name . '</strong>';
        }
        $secondary_label = $the_customer->get_name_secondary_label();
        $avatar = get_avatar($the_customer->get_email(), 100);

        $current_status = (!empty($_REQUEST['_customer_status']) ? $_REQUEST['_customer_status'] : 'all');
        if ($current_status == 'trashed') {
            $actions = array();
            $c_name = "$avatar$name" . '<small class="meta">' . $secondary_label . "</small>";
            if (current_user_can('manage_woocommerce')) {
                $actions['untrash'] = sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    wp_nonce_url(admin_url(add_query_arg(array('page' => WC_CRM_TOKEN, 'wc_crm_customer_action' => 'untrash', 'ids' => $the_customer->c_id), 'admin.php'))),
                    /* translators: %s: post title */
                    esc_attr(sprintf(__('Restore &#8220;%s&#8221; from the Trash'), $name)),
                    __('Restore')
                );
                $actions['delete'] = sprintf(
                    '<a href="%s" class="submitdelete customerdelete" aria-label="%s" data-cid="%d">%s</a>',
                    wp_nonce_url(admin_url(add_query_arg(array('page' => WC_CRM_TOKEN, 'wc_crm_customer_action' => 'delete', 'ids' => $the_customer->c_id), 'admin.php'))),
                    /* translators: %s: post title */
                    esc_attr(sprintf(__('Delete &#8220;%s&#8221; permanently'), $name)),
                    $the_customer->c_id,
                    __('Delete Permanently')
                );

            }
            return $c_name . $this->row_actions($actions);
        } else {
            if (!empty($name)) {
                $edit .= "<a href='admin.php?page=" . WC_CRM_TOKEN . "&c_id=" . $item['c_id'] . "'>" . $name . "</a>";
            }
            $watching = get_user_meta($the_customer->user_id, 'customer_watching', true);
            if(isset($watching) && $watching){
                $edit .= "<a class='watch-customer'></a>";
            }
            $edit .= '<br><small class="meta">' . $secondary_label . "</small>";
            return "<a href='admin.php?page=" . WC_CRM_TOKEN . "&c_id=" . $item['c_id'] . "'>$avatar</a> $edit";
        }
    }

    //
    function column_email($item)
    {
        global $the_customer;
        $email = $the_customer->email;
        $phone = $the_customer->phone;
        if (!$phone) {
            $phone = $the_customer->mobile;
        }
        $the_customer->init_address_fields();
        $term = $the_customer->billing_address_1 . ',' . $the_customer->billing_city . ',' . $the_customer->billing_country;
        if ($the_customer->billing_country == null)
            $customer_location = '<span class="note-off" style="text-align:left;">-</span>';
        else
            $customer_location = '<a href="http://maps.google.com/?q=' . $term . '">' . $the_customer->billing_fields['country']['options'][$the_customer->country] . '</a>';
        $content = "<a href='mailto:$email' title='" . esc_attr(sprintf(__('Email: %s'), $email)) . "'>{$email}</a><br><span class='crm_phone'>{$phone}</span>";
        $content .= $customer_location;
        return $content;

    }

    function column_customer_agent($item)
    {
        $content = ' - ';
        //var_dump($item);
        if ($item['customer_agent']) {
//            $agent = wc_crm_get_customer($item['customer_agent'], 'user_id');
            $content = "<a href='admin.php?page=" . WC_CRM_TOKEN . "&c_id=" . $item["customer_agent"] . "'>" . $item["agent_firstname"] . ' ' . $item["agent_lastame"] . "</a><br><small class='meta'>{$item['agent_username']}</span>";
        }
        return ($content);
    }

    function column_last_purchase($item)
    {
        if ($item['order_id'] && !empty($item['order_id'])) {
            $order = wc_get_order($item['order_id']);
            if ($order) {
                return '<a href="' . get_edit_post_link($item['order_id']) . '">#' . $order->get_order_number() . '</a><br><small class="meta"> ' . woocommerce_crm_get_pretty_time($item['order_id']) . '</small>';
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    function column_order_value($item)
    {
        global $wpdb;
        $order_statuses = get_option('wc_crm_total_value', array());

        $sql = "SELECT COUNT(p.ID) as orders, SUM(pm1.meta_value) as order_values FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_customer_user' AND pm.meta_value = {$item['user_id']}
                JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_order_total' AND pm.meta_value = {$item['user_id']}";

        if($item["user_id"] == "0" && !empty($item["email"])){
            $sql .= " JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_billing_email' AND pm2.meta_value = '{$item['email']}'";
        }

        $sql .= " WHERE p.post_type = 'shop_order'";

        if(!empty($order_statuses)){
            $sql .= " AND p.post_status IN ('" . implode("','" ,$order_statuses) . "')";
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        $num_orders = $results[0]['orders'];
        $total_spent = $results[0]['order_values'];

        $num_orders = $num_orders > 0 ? '<br><small class="meta">' . sprintf(_n('%d order', '%d orders', $num_orders, 'wc_crm'), $num_orders) . '</small>' : '';
        return wc_price($total_spent) . $num_orders;
    }

    function column_customer_notes($item)
    {
        global $the_customer;

        $notes = $the_customer->get_last_note();
        if ($notes == 'No Customer Notes')
            $customer_notes = '<span class="note-off">-</span>';
        else
            $customer_notes = '<a href="admin.php?page=' . WC_CRM_TOKEN . '&screen=customer_notes&c_id=' . $item['c_id'] . '" class="open_c_notes note-on tips" data-tip="' . $notes . '"></a>';

        return $customer_notes;
    }

    function column_enrolled($item)
    {
        global $the_customer;

        if (woocommerce_crm_mailchimp_enabled()) {
            return (is_array($this->mailchimp) && in_array($the_customer->email, $this->mailchimp)) ? "<span class='enrolled-yes tips' data-tip='Enrolled'></span>" : "<span class='enrolled-no tips' data-tip='Not Enrolled'></span>";
        }
        return '';

    }

    function column_wc_subscriber($item)
    {

        if ($item['user_id'] && !empty($item['user_id'])) {
            if (WC_Subscriptions_Manager::user_has_subscription($item['user_id'], '', 'active')) {

                return "<span class='enrolled-yes tips' data-tip='Active Subscriber'></span>";

            } else {

                return "<span>-</span>";

            }
        }
        return "<span>-</span>";

    }

    function column_wc_group($item)
    {

        if ($item['user_id'] && !empty($item['user_id'])) {
            $groups_user = new Groups_User($item['user_id']);
            $groups = $groups_user->groups;
            if (count($groups) > 0) {
                usort($groups, array(__CLASS__, 'by_group_name'));
                $output = '<ul>';
                foreach ($groups as $group) {
                    $output .= '<li>';
                    $output .= wp_filter_nohtml_kses($group->name);
                    $output .= '</li>';
                }
                $output .= '</ul>';
            } else {
                $output = __('-', GROUPS_PLUGIN_DOMAIN);
            }
            return $output;
        }
        return __('-', GROUPS_PLUGIN_DOMAIN);;

    }

    function column_crm_actions($item)
    {
        global $the_customer;
        $actions = array();

        $email = $the_customer->billing_email;
        $phone = $the_customer->phone;

        $actions['edit'] = array(
            'classes' => 'edit',
            'url' => sprintf('?page=%s&c_id=%s', $_REQUEST['page'], $the_customer->customer_id),
            'name' => __('Edit Customer', 'wc_crm'),
            'target' => ''
        );
        if ($the_customer->order_id) {
            $actions['orders'] = array(
                'classes' => 'view',
                'url' => sprintf('edit.php?s=%s&post_status=%s&post_type=%s&shop_order_status&_customer_user&paged=1&mode=list&search_by_email_only', urlencode($email), 'all', 'shop_order'),
                'action' => 'view',
                'name' => __('View Orders', 'wc_crm'),
                'target' => ''
            );
            if ($the_customer->user_id > 0) {
                $actions['orders']['url'] = sprintf('edit.php?post_status=%s&post_type=%s&shop_order_status&_customer_user=%d&paged=1&mode=list', 'all', 'shop_order', $the_customer->user_id);
            }

        }
        $actions['email'] = array(
            'classes' => 'email',
            'url' => sprintf('?page=%s&screen=%s&c_id=%s', $_REQUEST['page'], 'email', $the_customer->customer_id),
            'name' => __('Send Email', 'wc_crm'),
            'target' => (get_option('wc_crm_email_composition', 'new') == 'new') ? 'target="_blank"' : ''
        );
        $actions['new_order'] = array(
            'classes' => 'new_order',
            'url' => admin_url('post-new.php?post_type=shop_order&c_id=' . $the_customer->customer_id),
            'name' => __('Create order', 'wc_crm'),
            'target' => (get_option('wc_crm_new_order_composition', 'new') == 'new') ? 'target="_blank"' : ''
        );

        $crm_actions = '';
        if (!empty($actions)) {
            foreach ($actions as $key => $action) {
                $crm_actions .= '<a class="button tips ' . esc_attr($action['classes']) . '" href="' . esc_url($action['url']) . '" data-tip="' . esc_attr($action['name']) . '" ' . esc_attr($action['target']) . ' >' . esc_attr($action['name']) . '</a>';
            }
        }
        return $crm_actions;
    }


    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = get_hidden_columns($this->screen);

        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $user = get_current_user_id();
        $screen = get_current_screen();

        $option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $option, true);
        if (empty ($per_page) || $per_page < 1) {
            $per_page = $screen->get_option('per_page', 'default');
        }

        $current_page = $this->get_pagenum();
        $args = array(
            'current_page' => $current_page - 1,
            'per_page' => $per_page
        );
        $this->data = WC_CRM_Screen_Customers_List::get_customers($args);
        //usort( $this->data, array( &$this, 'usort_reorder' ) );

        $total_items = WC_CRM_Screen_Customers_List::get_customers_count();
        //$this->found_data = array_slice( $this->data,( ( $current_page-1 )* $per_page ), $per_page );

        $this->set_pagination_args(array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page' => $per_page                     //WE have to determine how many items to show on a page
        ));
        $this->items = $this->data;
    }

    public function prepare_accounts_items()
    {
        $columns = $this->get_columns();
        $this->_column_headers = array($columns, array(), array());
        global $post;
        $account_id = $post->ID;
        $c_id = get_post_meta($account_id, '_wc_crm_customer_id');
        $args = array(
            'account_id' => $account_id,
            'guest_customers' => 'no'
        );
        if (!empty($c_id)) {
            $this->items = WC_CRM_Screen_Customers_List::get_customers($args);
        } else {
            $this->items = array();
        }
    }


    function extra_tablenav($which)
    {
        $screen = get_current_screen();
        if ($which == 'top' && $screen->id != 'wc_crm_accounts') {
            do_action('wc_crm_restrict_list_customers');
        }
    }

    function get_views()
    {
        $customer_status = WC_CRM_Screen_Customer_Filters::get_filter_data('status');

        $_statuses = array();
        $all = 0;
        if ($customer_status) {
            foreach ($customer_status as $st) {
                $_statuses[$st->status] = $st->count;
                if ($st->status != 'trashed') {
                    $all += (int)$st->count;
                }
            }
        }

        $views = array();
        $current = (!empty($_REQUEST['_customer_status']) ? $_REQUEST['_customer_status'] : 'all');

        //All link
        $class = ($current == 'all' ? ' class="current"' : '');
        $all_url = 'admin.php?page=' . WC_CRM_TOKEN;
        $views['all'] = "<a href='{$all_url }' {$class} >All <span class='count'>({$all})</span></a>";

        $statuses = wc_crm_get_statuses();

        if ($customer_status && $statuses) {
            foreach ($statuses as $st) {
                if (isset($_statuses[$st->status_slug])) {
                    $count = $_statuses[$st->status_slug];
                    $url = add_query_arg(array('_customer_status' => $st->status_slug), 'admin.php?page=' . WC_CRM_TOKEN);
                    $class = ($current == $st->status_slug ? ' class="current"' : '');
                    $views[$st->status_slug] = "<a href='{$url}' {$class} >{$st->status_name} <span class='count'>({$count})</span></a>";
                }
            }
        }
        if (isset($_statuses['trashed'])) {
            $count = $_statuses['trashed'];
            $url = add_query_arg(array('_customer_status' => 'trashed'), 'admin.php?page=' . WC_CRM_TOKEN);
            $class = ($current == 'trashed' ? ' class="current"' : '');
            $views['trashed'] = "<a href='{$url}' {$class} >" . __('Trash', 'wc_crm') . " <span class='count'>({$count})</span></a>";
        }
        return $views;
    }

    /**
     * Generate the table rows
     *
     * @since 3.0.0
     * @access public
     */
    public function display_rows()
    {
        global $the_customer;
        foreach ($this->items as $item) {
            $the_customer = new WC_CRM_Customer($item['c_id']);
            $this->single_row($item);
        }
    }

    public function views()
    {
        $views = $this->get_views();
        /**
         * Filter the list of available list table views.
         *
         * The dynamic portion of the hook name, `$this->screen->id`, refers
         * to the ID of the current screen, usually a string.
         *
         * @since 3.5.0
         *
         * @param array $views An array of available list table views.
         */
        $views = apply_filters("views_{$this->screen->id}", $views);

        if (empty($views))
            return;

        echo "<ul class='subsubsub'>\n";
        foreach ($views as $class => $view) {
            $views[$class] = "\t<li class='st-$class'>$view";
        }
        echo implode(" |</li>\n", $views) . "</li>\n";
        echo "</ul>";
    }
}