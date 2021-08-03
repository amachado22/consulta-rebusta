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

if (!class_exists('WC_CRM_Admin_Post_Types')) :

    /**
     * WC_CRM_Admin_Post_Types Class
     *
     * Handles the edit posts views and some functionality on the edit post screen for WC post types.
     */
    class WC_CRM_Admin_Post_Types
    {

        /**
         * Hook into ajax events
         */
        public function __construct()
        {

            add_action('admin_init', array($this, 'register_post_types'));
            add_action('add_meta_boxes', array($this, 'remove_meta_boxes'), 10);
            add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 30);
            add_action('enter_title_here', array($this, 'enter_title_here'), 30, 2);
            add_action('edit_form_after_title', array($this, 'edit_form_after_title'), 30, 2);

            add_action('save_post', array($this, 'save_meta_boxes'), 1, 2);
            add_action('wc_crm_process_accounts_meta', 'WC_Crm_Accounts::save', 10, 2);
            add_filter('post_row_actions', array($this, 'remove_quick_edit_view'), 10, 1);

            add_filter('post_updated_messages', array($this, 'post_updated_messages'), 10, 1);

            add_filter('comments_clauses', array($this, 'exclude_comments'), 10, 1);

            add_action('comment_feed_join', array($this, 'exclude_account_from_feed_join'), 30, 1);
            add_action('comment_feed_where', array($this, 'exclude_account_from_feed_where'), 30, 1);

            // WP List table columns. Defined here so they are always available for events such as inline editing.
            add_filter('manage_wc_crm_accounts_posts_columns', array($this, 'add_wc_crm_accounts_column'));
            add_filter('manage_wc_crm_tasks_posts_columns', array($this, 'add_wc_crm_tasks_column'));
            add_filter('manage_wc_crm_calls_posts_columns', array($this, 'add_wc_crm_calls_column'));
            add_filter('manage_wc_crm_validations_posts_columns', array($this, 'add_wc_crm_validations_column'));

            add_action('manage_wc_crm_accounts_posts_custom_column', array($this, 'wc_crm_accounts_custom_columns'), 10, 2);
            add_action('manage_wc_crm_tasks_posts_custom_column', array($this, 'wc_crm_tasks_custom_columns'), 10, 2);
            add_action('manage_wc_crm_calls_posts_custom_column', array($this, 'wc_crm_calls_custom_columns'), 10, 2);
            add_action('manage_wc_crm_validations_posts_custom_column', array($this, 'wc_crm_validations_custom_columns'), 10000, 2);

            add_filter('manage_edit-wc_crm_tasks_sortable_columns', array($this, 'wc_crm_tasks_sortable_columns'));
            add_filter('manage_edit-wc_crm_calls_sortable_columns', array($this, 'wc_crm_calls_sortable_columns'));
            add_filter('manage_edit-wc_crm_validations_sortable_columns', array($this, 'wc_crm_validations_sortable_columns'));

            add_filter('request', array($this, 'request_query'));

            add_action('init', array($this, 'register_post_status'), 10);
            add_action('admin_notices', array($this, 'task_notices'), 80);

        }

        /**
         * Register core post types.
         */
        public function register_post_types()
        {
            if (post_type_exists('wc_crm_accounts')) {
                return;
            }
            $post_types = array(
                'wc_crm_accounts' => array(
                    array(
                        'description' => __('This is where Accounts are stored.', 'wc_crm'),
                        'supports' => array('custom-fields'),
                    ),
                    __('Account', 'wc_crm'),
                    __('Accounts', 'wc_crm')
                ),
                'wc_crm_tasks' => array(
                    array(
                        'supports' => array('custom-fields'),
                    ),
                    __('Task', 'wc_crm'),
                    __('Tasks', 'wc_crm')
                ),
                'wc_crm_calls' => array(
                    array(
                        'supports' => array('custom-fields'),
                    ),
                    __('Call', 'wc_crm'),
                    __('Calls', 'wc_crm')
                ),
                'wc_crm_validations' => array(
                    array(
                        'supports' => false,
                        'capabilities' => array(
                            'create_posts' => false,
                        ),
                        'map_meta_cap' => true,
                    ),
                    __('Document', 'wc_crm'),
                    __('Documents', 'wc_crm')
                )
            );

            foreach ($post_types as $post_type => $opt) {
                $this->register_post_type($post_type, $opt[0], $opt[1], $opt[2]);
            }


        }

        public function register_post_type($post_type = '', $options = array(), $single = '', $plural = '')
        {
            if (empty($post_type)) return;
            if (empty($single)) $single = $post_type;
            if (empty($plural)) $plural = $post_type;

            $labels = array(
                'name' => $plural,
                'singular_name' => $single,
                'name_admin_bar' => $single,
                'add_new' => sprintf(__('Add %s', 'wc_crm'), $single),
                'add_new_item' => sprintf(__('Add New %s', 'wc_crm'), $single),
                'edit_item' => sprintf(__('Edit %s', 'wc_crm'), $single),
                'new_item' => sprintf(__('New %s', 'wc_crm'), $single),
                'all_items' => sprintf(__('All %s', 'wc_crm'), $plural),
                'view_item' => sprintf(__('View %s', 'wc_crm'), $single),
                'search_items' => sprintf(__('Search %s', 'wc_crm'), $plural),
                'not_found' => sprintf(__('No %s Found', 'wc_crm'), $plural),
                'not_found_in_trash' => sprintf(__('No %s Found In Trash', 'wc_crm'), $plural),
                'parent_item_colon' => sprintf(__('Parent %s'), $single),
                'menu_name' => $plural,
            );

            $args = array(
                'labels' => apply_filters($post_type . '_labels', $labels),
                'description' => '',
                'public' => false,
                'publicly_queryable' => true,
                /*'publicly_queryable'  => false,*/
                'exclude_from_search' => true,
                'show_ui' => true,
                'map_meta_cap' => true,
                'show_in_admin_bar' => true,
                'show_in_menu' => 'admin.php?page=wc_crm',
                'show_in_nav_menus' => false,
                'query_var' => true,
                'can_export' => true,
                'rewrite' => true,
                'capability_type' => 'post',
                'has_archive' => false,
                'hierarchical' => false,
                'supports' => array('title', 'editor', 'excerpt', 'comments'),
                'menu_icon' => 'dashicons-admin-post',
            );

            $args = array_merge($args, $options);

            register_post_type($post_type, apply_filters($post_type . '_register_args', $args, $post_type));
        }

        public function post_updated_messages($messages)
        {
            global $post_type;
            switch ($post_type) {
                case 'wc_crm_accounts':
                    $messages['wc_crm_accounts'] = array(
                        0 => '', // Unused. Messages start at index 1.
                        1 => __('Account updated.', 'wc_crm'),
                        2 => __('Custom field updated.', 'wc_crm'),
                        3 => __('Custom field deleted.', 'wc_crm'),
                        4 => __('Account updated.', 'wc_crm'),
                        /* translators: %s: date and time of the revision */
                        5 => isset($_GET['revision']) ? sprintf(__('Account restored to revision from %s', 'wc_crm'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
                        6 => __('Account published.', 'wc_crm'),
                        7 => __('Account saved.', 'wc_crm'),
                        8 => __('Account submitted.', 'wc_crm'),
                    );
                    break;
                case 'wc_crm_tasks':
                    $messages['wc_crm_tasks'] = array(
                        0 => '', // Unused. Messages start at index 1.
                        1 => __('Task updated.', 'wc_crm'),
                        2 => __('Custom field updated.', 'wc_crm'),
                        3 => __('Custom field deleted.', 'wc_crm'),
                        4 => __('Task updated.', 'wc_crm'),
                        /* translators: %s: date and time of the revision */
                        5 => isset($_GET['revision']) ? sprintf(__('Task restored to revision from %s', 'wc_crm'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
                        6 => __('Task created.', 'wc_crm'),
                        7 => __('Task saved.', 'wc_crm'),
                        8 => __('Task submitted.', 'wc_crm'),
                    );
                    break;
                case 'wc_crm_calls':
                    $messages['wc_crm_calls'] = array(
                        0 => '', // Unused. Messages start at index 1.
                        1 => __('Call updated.', 'wc_crm'),
                        2 => __('Custom field updated.', 'wc_crm'),
                        3 => __('Custom field deleted.', 'wc_crm'),
                        4 => __('Call updated.', 'wc_crm'),
                        /* translators: %s: date and time of the revision */
                        5 => isset($_GET['revision']) ? sprintf(__('Call restored to revision from %s', 'wc_crm'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
                        6 => __('Call created.', 'wc_crm'),
                        7 => __('Call saved.', 'wc_crm'),
                        8 => __('Call submitted.', 'wc_crm'),
                    );
                    break;
            }
            return $messages;
        }

        /**
         * Remove bloat
         */
        public function remove_meta_boxes()
        {
            $post_types = array('wc_crm_accounts', 'wc_crm_tasks', 'wc_crm_calls');
            foreach ($post_types as $post_type) {
                remove_meta_box('submitdiv', $post_type, 'side');
                remove_meta_box('commentsdiv', $post_type, 'normal');
                remove_meta_box('woothemes-settings', $post_type, 'normal');
                remove_meta_box('commentstatusdiv', $post_type, 'normal');
                remove_meta_box('slugdiv', $post_type, 'normal');
            }
        }

        /**
         * Add WC_CRM Accounts Meta boxes
         */
        public function add_meta_boxes()
        {
            add_meta_box('wc_crm_account_data', __('Dados da conta', 'wc_crm'), 'WC_Crm_Accounts::output', 'wc_crm_accounts', 'normal', 'high');
            add_meta_box('wc_crm_account_customers', __('Cliente', 'wc_crm'), 'WC_Crm_Accounts::output_customers', 'wc_crm_accounts', 'normal', 'high');
            add_meta_box('wc_crm-account-actions', __('Ações da conta', 'wc_crm'), 'WC_Crm_Accounts::output_actions', 'wc_crm_accounts', 'side', 'high');
            add_meta_box('woocommerce-order-notes', __('Notas de conta', 'wc_crm'), 'WC_Crm_Accounts::output_notes', 'wc_crm_accounts', 'side', 'default');

            add_meta_box('wc_crm_task_info', __('Dados da Tarefa', 'wc_crm'), 'WC_CRM_Screen_Task::output_info', 'wc_crm_tasks', 'advanced', 'high');
            add_meta_box('wc_crm_task_description', __('Descrição da tarefa', 'wc_crm'), 'WC_CRM_Screen_Task::output_description', 'wc_crm_tasks', 'advanced', 'low');
            add_meta_box('submitdiv', __('Ações de Tarefa', 'wc_crm'), 'WC_CRM_Screen_Task::output_actions', 'wc_crm_tasks', 'side', 'high');

            add_meta_box('submitdiv', __('Ações de chamada', 'wc_crm'), 'WC_CRM_Screen_Call::output_actions', 'wc_crm_calls', 'side', 'high');
            add_meta_box('wc_crm_call_data', __('Dados de Chamadas', 'wc_crm'), 'WC_CRM_Screen_Call::output_info', 'wc_crm_calls', 'advanced', 'high');

            add_meta_box('wc_crm_validation_data', __('Dados do Documento', 'wc_crm'), 'WC_CRM_VALIDATION::output', 'wc_crm_validations', 'normal', 'high');
            add_meta_box('wc_crm_validation_notes', __('Notas do Documento', 'wc_crm'), 'WC_CRM_VALIDATION::output_validation_notes', 'wc_crm_validations', 'side', 'default');

        }

        public function enter_title_here($text, $post)
        {
            $pts = array('wc_crm_tasks', 'wc_crm_calls');
            if (in_array($post->post_type, $pts)) {
                $text = __('Enter subject here', 'wc_crm');
            }
            return $text;
        }

        public function edit_form_after_title()
        {
            global $post, $wp_meta_boxes;
            $pts = array('wc_crm_tasks', 'wc_crm_calls');
            if (in_array($post->post_type, $pts)) {
                do_meta_boxes(get_current_screen(), 'advanced', $post);
                unset($wp_meta_boxes[get_post_type($post)]['advanced']);
            }
        }

        public function save_meta_boxes($post_id, $post)
        {
            WC_Crm_Accounts::save_meta_boxes($post_id, $post);
            WC_CRM_Screen_Task::save_meta_boxes($post_id, $post);
            WC_CRM_Screen_Call::save_meta_boxes($post_id, $post);
            WC_CRM_VALIDATION::save_meta_boxes($post_id, $post);
        }

        public static function exclude_comments($clauses)
        {
            global $wpdb, $typenow;

            if (is_admin() && in_array($typenow, wc_get_order_types()) && current_user_can('manage_woocommerce')) {
                return $clauses; // Don't hide when viewing orders in admin
            }

            if (!$clauses['join']) {
                $clauses['join'] = '';
            }

            if (!strstr($clauses['join'], "JOIN $wpdb->posts")) {
                $clauses['join'] .= " LEFT JOIN $wpdb->posts ON comment_post_ID = $wpdb->posts.ID ";
            }

            if ($clauses['where']) {
                $clauses['where'] .= ' AND ';
            }

            $clauses['where'] .= " $wpdb->posts.post_type NOT IN ('" . implode("','", wc_crm_get_exclude_comments_post_types()) . "') ";

            return $clauses;
        }

        /**
         * Exclude order comments from queries and RSS
         * @param  string $join
         * @return string
         */
        public static function exclude_account_from_feed_join($join)
        {
            global $wpdb;

            if (!strstr($join, $wpdb->posts)) {
                $join = " LEFT JOIN $wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID ";
            }

            return $join;
        }

        /**
         * Exclude order comments from queries and RSS
         * @param  string $where
         * @return string
         */
        public static function exclude_account_from_feed_where($where)
        {
            global $wpdb;

            if ($where) {
                $where .= ' AND ';
            }

            $where .= " $wpdb->posts.post_type NOT IN ('" . implode("','", wc_crm_get_exclude_comments_post_types()) . "') ";

            return $where;
        }

        public function add_wc_crm_accounts_column($columns)
        {
            unset($columns['date']);
            $columns['title'] = __('Account Name', 'wc_crm');
            $columns['owner'] = __('Account Owner', 'wc_crm');
            $columns['type'] = __('Account Type', 'wc_crm');
            $columns['ownership'] = __('Ownership', 'wc_crm');
            $columns['industry'] = __('Industry', 'wc_crm');

            return $columns;
        }

        public function add_wc_crm_tasks_column($columns)
        {
            unset($columns['date']);
            $columns['title'] = __('Subject', 'wc_crm');
            $columns['due_date'] = __('Due Date', 'wc_crm');
            $columns['status'] = '<span class="status_head tips" data-tip="' . esc_attr__('Status', 'wc_crm') . '">' . esc_attr__('Status', 'wc_crm') . '</span>';
            $columns['priority'] = '<span class="Priority tips" data-tip="' . esc_attr__('Priority', 'wc_crm') . '">' . esc_attr__('Priority', 'wc_crm') . '</span>';
            $columns['customer_n_account'] = __('Customer', 'wc_crm');
            $columns['activity_owner'] = __('Owner', 'wc_crm');

            return $columns;
        }

        public function add_wc_crm_calls_column($columns)
        {
            unset($columns['date']);
            $columns['title'] = __('Subject', 'wc_crm');
            $columns['call_type'] = '<span class="CallType tips" data-tip="' . esc_attr__('Type', 'wc_crm') . '">' . esc_attr__('Type', 'wc_crm') . '</span>';
            $columns['call_date'] = __('Date', 'wc_crm');
            $columns['call_duration'] = '<span class="Duration tips" data-tip="' . esc_attr__('Duration', 'wc_crm') . '">' . esc_attr__('Duration', 'wc_crm') . '</span>';
            $columns['customer'] = __('Customer', 'wc_crm');
            $columns['related_to'] = __('Related To', 'wc_crm');
            $columns['activity_owner'] = __('Owner', 'wc_crm');

            return $columns;
        }

        public function add_wc_crm_validations_column($columns)
        {
            $date = $columns['date'];
            unset($columns['date']);
            $columns['title'] = __('Customer', 'wc_crm');
            $columns['file'] = __('File Name', 'wc_crm');
            $columns['published_date'] = __('Submission Date', 'wc_crm');
            $columns['status'] = __('Status', 'wc_crm');
            $columns['actions'] = __('Actions', 'wc_crm');

            return $columns;
        }

        public function wc_crm_accounts_custom_columns($column, $post_id)
        {
            switch ($column) {
                case 'owner' :
                    $account_owner = get_post_meta($post_id, '_account_owner', true);;
                    $user_meta = get_user_meta($account_owner);
                    if ($user_meta) {
                        $user_meta = (object)$user_meta;
                        ?>
                        <a href="<?php echo get_edit_user_link($account_owner); ?>" target="_blank">
                            <?php echo $user_meta->first_name[0]; ?> <?php echo $user_meta->last_name[0]; ?>
                        </a>
                        <?php
                    } else {
                        echo '-';
                    }
                    break;

                case 'type' :
                    $options = wc_crm_get_account_types();
                    $type = get_post_meta($post_id, '_account_type', true);
                    echo isset($options[$type]) ? $options[$type] : '';
                    break;

                case 'ownership' :
                    $options = wc_crm_get_account_ownerships();
                    $ownership = get_post_meta($post_id, '_ownership', true);
                    echo isset($options[$ownership]) ? $options[$ownership] : '';
                    break;

                case 'industry' :
                    $all_i = wc_crm_get_industries();
                    $ind = get_post_meta($post_id, '_industry', true);
                    echo isset($all_i[$ind]) ? $all_i[$ind] : '';
                    break;
            }
        }

        public function wc_crm_tasks_custom_columns($column, $post_id)
        {
            global $the_task;
            if (is_null($the_task) || $the_task->id != $post_id) {
                $the_task = new WC_CRM_Task($post_id);
            }
            switch ($column) {
                case 'due_date' :
                    $datef = __('Y/m/d');
                    echo date_i18n($datef, strtotime($the_task->due_date));
                    break;
                case 'status' :
                    $statuses = wc_crm_get_task_statuses();
                    $status = $the_task->task_status;
                    if (isset($statuses[$the_task->task_status])) {
                        $status = $statuses[$the_task->task_status];
                    }
                    echo '<div style="position: relative;"><span class="' . $the_task->task_status . ' tips" data-tip="' . esc_attr($status) . '"></span></div>';
                    break;
                case 'priority' :
                    $priorities = wc_crm_get_task_priorities();
                    $priority = $the_task->priority;
                    if (isset($priorities[$the_task->priority])) {
                        $priority = $priorities[$the_task->priority];
                    }
                    echo '<div style="position: relative;"><span class="' . $priority . ' tips" data-tip="' . esc_attr($priority) . '"></span></div>';
                    break;
                case 'customer_n_account' :
                    $customer = (int)$the_task->customer_id;
                    $account = (int)$the_task->account;
                    if ($customer) {
                        $the_customer = new WC_CRM_Customer($customer);
                        $name = $the_customer->get_name();
                        $user_string = esc_html($name);
                        $phone = $the_customer->phone;
                        $user_contact = esc_html($phone) . ' <br> ' . sanitize_email($the_customer->email);
                        printf('<a href="admin.php?page=wc_crm&c_id=%d" target="_blank" class="tips" data-tip="%s">%s</a>', $the_customer->id, $user_contact, $user_string);
                    }
                    if ($account) {
                        $title = get_the_title($account);
                        printf('<span class="task_account">%s</span>', $title);
                    }
                    break;
                case 'activity_owner' :
                    echo $the_task->get_owner_name();
                    break;
            }
        }

        public function wc_crm_calls_custom_columns($column, $post_id)
        {
            global $the_call;
            $post = get_post($post_id);
            if (is_null($the_call) || $the_call->id != $post_id) {
                $the_call = new WC_CRM_Call($post_id);
            }
            switch ($column) {
                case 'call_type' :
                    echo '<div style="position: relative;"><span class="' . $the_call->type . ' tips" data-tip="' . $the_call->type . '"></span></div>';
                    break;
                case 'related_to' :
                    $product_name = $the_call->get_product_name();
                    $order_number = $the_call->get_order_number();
                    if (!empty($product_name)) {
                        printf('<div>%s</div>', $product_name);
                    }
                    if (!empty($order_number)) {
                        printf('<div>%s</div>', $order_number);
                    }
                    break;
                case 'customer' :
                    $customer = (int)$the_call->customer_id;
                    if ($customer) {
                        $the_customer = new WC_CRM_Customer($customer);

                        if ($the_customer->customer) {
                            $name = $the_customer->get_name();
                            $user_string = esc_html($name);
                            $phone = $the_customer->phone;
                            $user_contact = esc_html($phone) . ' <br> ' . sanitize_email($the_customer->email);
                            printf('<a href="admin.php?page=wc_crm&c_id=%d" target="_blank" class="tips" data-tip="%s">%s</a>', $customer, $user_contact, $user_string);
                        } else {
                            echo get_post_meta($post_id, '_customer_name', true);
                        }
                    }
                    break;
                case 'activity_owner' :
                    echo $the_call->get_owner_name();
                    break;
                case 'call_date' :
                    if ('0000-00-00 00:00:00' === $post->post_date) {
                        $t_time = $h_time = __('Unpublished');
                        $time_diff = 0;
                    } else {
                        $t_time = get_the_time(__('Y/m/d g:i:s a'));
                        $m_time = $post->post_date;
                        $time = get_post_time('G', true, $post);

                        $time_diff = time() - $time;

                        if ($time_diff > 0 && $time_diff < DAY_IN_SECONDS) {
                            $h_time = sprintf(__('%s ago'), human_time_diff($time));
                        } else {
                            $h_time = mysql2date(__('Y/m/d'), $m_time);
                        }
                    }
                    echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
                    break;
                case 'call_duration' :
                    echo $the_call->get_formated_call_duration();
                    break;
                default :
                    echo $column;
                    break;
            }
        }

        public function wc_crm_validations_custom_columns($column, $post_id)
        {
            $status_array = array(0 => 'Canceled', 1 => 'Awaiting Confirmation', 2 => 'Confirmed');
            $file = get_post_meta($post_id, 'validation_file', true);
            $status = get_post_meta($post_id, 'validation_status', true);
            $date_time = get_the_date('Y-m-d, H:i', $post_id);

            switch ($column){
                case 'file':
                    echo '<a href="'.$file["url"].'" target="_blank"><strong>'.basename($file["file"]).'</strong></a></br>';
                    echo '<small class="meta">'.$file["type"].'</small>';
                    break;
                case 'status':
                    echo $status_array[$status];
                    break;
                case 'published_date':
                    echo explode(',', $date_time)[0].'</br>';
                    echo '<small class="meta">'.explode(',', $date_time)[1].'</small>';
                    break;
                case 'actions':
                    echo '<a href="' . $file["url"] . '" target="_blank" class="button button-small validation-action">'.wc_help_tip(__('View File')).'</a>';
                    break;

            }

        }


        public function wc_crm_tasks_sortable_columns($columns)
        {
            $custom = array(
                'due_date' => 'due_date'
            );
            return wp_parse_args($custom, $columns);
        }

        public function wc_crm_calls_sortable_columns($columns)
        {
            $custom = array(
                'call_date' => 'date'
            );
            return wp_parse_args($custom, $columns);
        }

        public function wc_crm_validations_sortable_columns($columns)
        {
            $custom = array(
                'published_date' => 'date'
            );
            return wp_parse_args($custom, $columns);
        }

        public function register_post_status()
        {
            $task = wc_crm_get_task_statuses();
            $call = wc_crm_get_call_statuses();
            $statuses = array_merge($task, $call);
            if ($statuses) {

                foreach ($statuses as $key => $label) {
                    register_post_status($key, array(
                        'label' => $label,
                        'public' => true,
                        'exclude_from_search' => true,
                        'show_in_admin_all_list' => true,
                        'show_in_admin_status_list' => true,
                        'label_count' => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'woocommerce')
                    ));
                }
            }
        }

        public function task_notices()
        {
            $screen = get_current_screen();
            global $post, $action;
            if ($screen->base == 'post' && $screen->id == 'wc_crm_tasks' && $screen->post_type == 'wc_crm_tasks' && $action == 'edit') {
                $task = new WC_CRM_Task($post->ID);
                if ($task->is_repeated()) {
                    $weeksequence = array(
                        1 => __('First', 'wc_crm'),
                        __('Second', 'wc_crm'),
                        __('Third', 'wc_crm'),
                        __('Fourth', 'wc_crm'),
                        __('Last', 'wc_crm'),
                    );
                    ?>
                    <div id="message" class="updated">
                        <p>
                            <?php
                            $date_format = get_option('date_format');
                            $srart_date = date_i18n($date_format, strtotime($task->srart_date));
                            $end_date = date_i18n($date_format, strtotime($task->end_date));
                            switch ($task->repeat_type) {
                                case 'daily':
                                    if ($task->repeat_options['daily']['type'] == 1) {
                                        printf(__('This is a recurring task that repeats every days from %s to %s.', 'wc_crm'), $srart_date, $end_date);
                                    } else {
                                        printf(__('This is a recurring task that repeats every %s day(s) from %s to %s.', 'wc_crm'), $task->repeat_options['daily']['days'], $srart_date, $end_date);
                                    }
                                    break;

                                case 'weekly':
                                    $weekdays = array();
                                    $_weekdays = $task->repeat_options['weekly']['weekdays'];
                                    foreach ($_weekdays as $w) {
                                        $st = new DateTime('Sunday');
                                        $weekdays[] = $st->modify('+' . $w . ' day')->format('l');
                                    }
                                    $weekdays = implode(', ', $weekdays);
                                    $weeks = $task->repeat_options['weekly']['weeks'];
                                    printf(__('This is a recurring task that repeats on %s from %s to %s for every %s week(s).', 'wc_crm'), $weekdays, $srart_date, $end_date, $weeks);
                                    break;

                                case 'monthly':

                                    $month_number = $task->repeat_options['monthly']['noMonths'];
                                    if ($task->repeat_options['monthly']['type'] == 1) {
                                        $day_number = $task->repeat_options['monthly']['day'];
                                        printf(__('This is a recurring task that repeats on day %s of every %s month(s) from %s to %s.', 'wc_crm'), $day_number, $month_number, $srart_date, $end_date);
                                    } else {
                                        $interval = $task->repeat_options['monthly']['weeksequence'];
                                        $weekday = $task->repeat_options['monthly']['weekday'];
                                        $st = new DateTime('Sunday');
                                        $weekday = $st->modify('+' . $weekday . ' day')->format('l');
                                        printf(__('This is a recurring task that repeats on the %s %s of every %s months(s) from %s to %s.', 'wc_crm'), strtolower($weeksequence[$interval]), $weekday, $month_number, $srart_date, $end_date);
                                    }
                                    break;
                                case 'yearly':

                                    $year_month = $task->repeat_options['yearly']['months'];
                                    $year_month = date('F', mktime(0, 0, 0, $year_month));
                                    if ($task->repeat_options['yearly']['type'] == 1) {
                                        $year_day = $task->repeat_options['yearly']['days'];
                                        printf(__('This is a recurring task that repeats every year on %d %s from %s to %s.', 'wc_crm'), $year_day, $year_month, $srart_date, $end_date);
                                    } else {
                                        $interval = $task->repeat_options['yearly']['weeksequence'];
                                        $weekday = $task->repeat_options['yearly']['weekday'];
                                        $st = new DateTime('Sunday');
                                        $weekday = $st->modify('+' . $weekday . ' day')->format('l');
                                        printf(__('This is a recurring task that repeats every year on the %s %s of %s from %s to %s', 'wc_crm'), strtolower($weeksequence[$interval]), $weekday, $year_month, $srart_date, $end_date);
                                    }
                                    break;
                            }
                            ?>
                        </p>
                    </div>
                    <?php
                }
            }

        }

        public function request_query($vars)
        {
            global $typenow, $wp_query, $wp_post_statuses;

            if ('wc_crm_tasks' === $typenow) {
                // Sorting
                if (isset($vars['orderby'])) {
                    if ('due_date' == $vars['orderby']) {
                        $vars = array_merge($vars, array(
                            'meta_key' => '_due_date',
                            'orderby' => 'meta_value'
                        ));
                    }


                }
            }
            return $vars;
        }

        public function remove_quick_edit_view($actions)
        {
            $screen = get_current_screen();
            $arr = array('edit-wc_crm_accounts', 'edit-wc_crm_calls', 'edit-wc_crm_tasks', 'edit-wc_crm_validations');
            if (in_array($screen->id, $arr)) {
                unset($actions['inline hide-if-no-js']);
                unset($actions['view']);
            }
            return $actions;
        }

    }

    new WC_CRM_Admin_Post_Types();

endif;