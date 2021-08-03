<?php
/**
 * Table with list of products purchased.
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WC_CRM_Table_Customer_Products_Purchased extends WP_List_Table
{

    protected $data;
    protected $popup;
    protected $_items;

    function __construct($products, $popup = false, $items = array())
    {
        include_once(dirname(WC_PLUGIN_FILE) . '/includes/admin/class-wc-admin-post-types.php');
        global $status, $page;

        $this->data = $products;
        $this->popup = $popup;
        $this->_items = $items;

        parent::__construct(array(
            'singular' => __('product', 'wc_crm'),
            'plural' => __('products', 'wc_crm'),
            'ajax' => false

        ));
    }

    function no_items()
    {
        _e('No products data found.', 'wc_crm');
    }

    function column_default($item, $column_name)
    {
        global $post;
        $product = new WC_Product($item->ID);

        if (is_null($post) || (int)$item->ID != (int)$post->ID) {
            $post = get_post($item->ID);
            if (is_null($post)) return;
        }

        switch ($column_name) {
            case 'thumb':
                echo $product->get_image();
                break;
            case 'name':
                echo '<a href="' . get_edit_post_link($post->ID) . '">' . $product->get_name() . '</a>';
                echo '<div class="item-sku"><strong>' . __('SKU:', 'wc_crm') . ' </strong>' . $product->get_sku() . '</div>';
                break;
            case 'is_in_stock':
            case 'price':
            case 'product_cat':
            case 'product_type':
            case 'date':
            case 'crm_actions':
                break;
            case 'number_purchased':
                if ($this->popup) {
                    $id = (int)$item->ID;
                    if (isset($this->_items[$id])) {
                        echo '<small class="times">&#215;</small>  ' . $this->_items[$id]->items_count;
                    } else {
                        return 0;
                    }
                } else {
                    echo '<small class="times">&#215;</small>  ' . $item->items_count;
                }
                break;
            case 'value_purchases':
                if ($this->popup) {
                    $id = (int)$item->ID;

                    if (isset($this->_items[$id])) {
                        return wc_price($this->_items[$id]->line_total);
                    } else {
                        return wc_price(0);
                    }
                } else {
                    return wc_price($item->line_total);
                }
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns()
    {
        return array();
    }

    function get_columns()
    {
        $columns = array(
            'thumb' => '<span class="wc-image tips">' . __('Image', 'wc_crm') . '</span>',
            'name' => __('Name', 'wc_crm'),
            'is_in_stock' => __('Stock', 'wc_crm'),
            'price' => __('Price', 'wc_crm'),
            'product_cat' => __('Categories', 'wc_crm'),
            'product_type' => '<span class="wc-type tips">' . __('Type', 'wc_crm') . '</span>',
            'number_purchased' => __('Quantity', 'wc_crm'),
            'value_purchases' => __('Money Spent', 'wc_crm'),
            'crm_actions' => __('Actions', 'wc_crm'),
        );
        if ($this->popup === false) {
            unset($columns['is_in_stock']);
            unset($columns['price']);
            unset($columns['product_cat']);
            unset($columns['product_type']);
            unset($columns['crm_actions']);
        } else {
            //unset($columns['value_purchases']);
        }
        return $columns;
    }

    function column_crm_actions($item)
    {
        global $woocommerce;
        $actions = array(
            'select_order' => array(
                'classes' => 'select_order',
                'url' => '#' . $item->ID,
                'name' => __('Select', 'wc_crm'),
            )
        );
        if ($this->popup === false) {
            unset($actions['select_order']);
        }
        echo '<p>';
        foreach ($actions as $action) {
            printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr($action['classes']), esc_url($action['url']), esc_attr($action['name']), esc_attr($action['name']));
        }
        echo '</p>';
    }

    function usort_reorder($a, $b)
    {
        // If no sort, default to last purchase
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'name';
        // If no order, default to desc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';

        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, array(), $sortable);
        if ($this->popup !== false) {
            $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = count($this->data);
            $found_data = array_slice($this->data, (($current_page - 1) * $per_page), $per_page);

            $this->set_pagination_args(array(
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page' => $per_page //WE have to determine how many items to show on a page
            ));
            $this->data = $found_data;
        }

        $this->items = $this->data;
    }
}