<?php
/**
 * Table with list of activity.
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WC_CRM_Table_Activity extends WP_List_Table
{
    protected $data;

    function __construct()
    {
        parent::__construct(array(
            'singular' => __('log', 'wc_crm'),
            'plural' => __('logs', 'wc_crm'),
            'ajax' => false

        ));
    }

    public function no_items()
    {
        _e('No found.');
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'subject':
            case 'activity_type':
            case 'created':
            case 'crm_actions':
                return $item[$column_name];
            case 'user_id':
                $user_info = get_userdata($item[$column_name]);
                return '<a href="' . get_edit_user_link($item[$column_name]) . '">' . $user_info->display_name . '</a>';
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'subject' => array('subject', false),
            'created' => array('created', false),
        );
        return $sortable_columns;
    }

    public function usort_reorder($a, $b)
    {
        // If no sort, default to title
        $orderby = (!empty($_GET['orderby']) && in_array($_GET['orderby'], $this->r)) ? $_GET['orderby'] : 'created';
        // If no order, default to desc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);
        // Send final sort direction to usort
        return ($order === 'desc') ? $result : -$result;
    }

    public function get_columns()
    {
        if (isset($_GET['page']) && $_GET['page'] != 'wc_crm') {
            $columns['cb'] = '<input type="checkbox" />';
        }
        $columns['subject'] = __('Subject', 'wc_crm');
        $columns['activity_type'] = __('Type', 'wc_crm');
        $columns['created'] = __('Date', 'wc_crm');
        $columns['user_id'] = __('Author', 'wc_crm');

        if (!isset($_GET['log_status']) || $_GET['log_status'] != 'trash') {
            $columns['crm_actions'] = __('Actions', 'wc_crm');
        }
        return $columns;
    }

    function column_crm_actions($item)
    {
        global $woocommerce;

        $actions = array(
            'view' => array(
                'classes' => 'view',
                'url' => sprintf('?page=%s&log_id=%d', WC_CRM_TOKEN . '-logs', $item['ID']),
                'name' => __('View', 'wc_crm')
            ),
            'delete_log' => array(
                'classes' => 'delete_log',
                'url' => sprintf('?page=%s&action=%s&log=%d', WC_CRM_TOKEN . '-logs', 'trash', $item['ID']),
                'name' => __('Trash', 'wc_crm'),
            )
        );

        echo '<p>';
        foreach ($actions as $action) {
            printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr($action['classes']), esc_url($action['url']), esc_attr($action['name']), esc_attr($action['name']));
        }
        echo '</p>';

    }

    function get_bulk_actions()
    {
        $actions = array();
        if (isset($_GET['page']) && $_GET['page'] != 'wc_crm') {
            if (isset($_GET['log_status']) && $_GET['log_status'] == 'trash') {
                $actions = array(
                    'untrash' => 'Restore',
                    'delete' => 'Delete Permanently'
                );
            } else {
                $actions = array(
                    'trash' => 'Move to Trash'
                );
            }
        }
        return $actions;
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="log[]" value="%s" />', $item['ID']
        );
    }

    function column_subject($item)
    {
        $actions = array();
        $item['subject'] = wp_unslash($item['subject']);
        $subject = '';
        if (isset($_GET['log_status']) && $_GET['log_status'] == 'trash') {
            $actions = array(
                'untrash' => sprintf('<a href="?page=%s&log_status=trash&action=%s&log=%s">' . __('Restore', 'wc_crm') . '</a>', WC_CRM_TOKEN . '-logs', 'untrash', $item['ID']),
                'delete' => sprintf('<a href="?page=%s&log_status=trash&action=%s&log=%s">' . __('Delete Permanently', 'wc_crm') . '</a>', WC_CRM_TOKEN . '-logs', 'delete', $item['ID']),
            );
            $subject = '<strong>' . $item['subject'] . '</strong>';
        } else {
            $actions = array(
                'id' => sprintf('ID: %s ', $item['ID']),
                'view' => sprintf('<a href="?page=%s&log_id=%d">' . __('View', 'wc_crm') . '</a>', WC_CRM_TOKEN . '-logs', $item['ID']),
                'trash' => sprintf('<a href="?page=%s&action=%s&log=%s">' . _x('Trash', 'verb') . '</a>', WC_CRM_TOKEN . '-logs', 'trash', $item['ID']),
            );
            $subject = sprintf(
                '<strong><a href="?page=%s&log_id=%d">%s</a></strong>', WC_CRM_TOKEN . '-logs', $item['ID'], $item['subject']
            );
        }
        return sprintf('%1$s %2$s', $subject, $this->row_actions($actions));

    }

    function column_activity_type($item)
    {
        if ($item['activity_type'] == 'email') {
            return '<i class="email tips" data-tip="' . esc_attr__('Email', 'woocommerce-crm') . '"></i>';
        } else if ($item['activity_type'] == 'phone call') {
            return '<i class="phone tips" data-tip="' . esc_attr__('Phone', 'woocommerce-crm') . '"></i>';
        } else {
            return '-';
        }
    }

    function column_created($item)
    {
        $t_time = date("Y/m/d g:i:s A", strtotime($item['created']));

        if ('0000-00-00 00:00:00' == $item['created_gmt']) {
            $item['created_gmt'] = get_gmt_from_date($item['created']);
        }
        $gmt_time = strtotime($item['created_gmt'] . ' UTC');
        $time_diff = current_time('timestamp', 1) - $gmt_time;

        if ($time_diff > 0 && $time_diff < 24 * 60 * 60)
            $h_time = sprintf(__('%s ago', 'woocommerce'), human_time_diff($gmt_time, current_time('timestamp', 1)));
        else
            $h_time = date("Y/m/d", strtotime($item['created']));
        return '<abbr title="' . esc_attr($t_time) . '">' . esc_html($h_time) . '</abbr>';
    }

    function prepare_items()
    {

        $columns = $this->get_columns();
        $hidden = array();

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

        $this->data = WC_CRM_Screen_Activity::get_activity();
        $total_items = count($this->data);

        $found_data = array_slice($this->data, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));
        $this->items = $found_data;

    }

    public function get_views()
    {

        global $wpdb;
        $table_name = $wpdb->prefix . "wc_crm_log";
        $all = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE log_status <> 'trash' ");
        $trash = $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE log_status = 'trash' ");

        $views = array();
        $current = (!empty($_REQUEST['log_status']) ? $_REQUEST['log_status'] : 'all');

        //All link
        $class_all = ($current == 'all' ? ' class="current"' : '');
        $class_trash = ($current == 'trash' ? ' class="current"' : '');
        $all_url = 'admin.php?page=' . WC_CRM_TOKEN . '-logs';
        $views['all'] = "<a href='{$all_url }' {$class_all} >All <span class='count'>({$all})</span></a>";
        $views['trash'] = "<a href='{$all_url }&log_status=trash' {$class_trash} > ". __('Bin', 'wc_crm') ." <span class='count'>({$trash})</span></a>";


        return $views;
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

    function extra_tablenav($which)
    {
        if ($which == 'top') {
            do_action('wc_crm_restrict_list_logs');
        }
    }

} //class