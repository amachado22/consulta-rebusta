<?php
/**
 *
  * @author     Adailton Machado
 * @package  WC_CRM
 * @since    3.0.6
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_CRM_Screen_Call
{
    private static $saved_meta_boxes = false;

    public static function output_info($post)
    {
        global $post_type;
        wp_nonce_field('wc_crm_save_data', 'wc_crm_meta_nonce');
        $fields_info = self::get_info_fields($post->ID);
        include_once 'views/html-call-data.php';
    }

    public static function output_actions($post)
    {
        global $action;

        $post_type = $post->post_type;
        $post_type_object = get_post_type_object($post_type);
        $can_publish = current_user_can($post_type_object->cap->publish_posts);
        $call = new WC_CRM_Call($post->ID);
        $edit = !(!$call->post->post_date_gmt || '0000-00-00 00:00:00' == $call->post->post_date_gmt);
        if (!$edit && isset($_GET['c_id']) & !empty($_GET['c_id'])) {
            $customer = (int)$_GET['c_id'];
            $the_customer = new WC_CRM_Customer($customer);
            $call->customer_id = $customer;
            $call->phone_number = $the_customer->phone;
        }

        include_once 'views/html-call-actions.php';
        ?>
        <?php
    }

    public static function get_info_fields($id)
    {
        $call = new WC_CRM_Call($id);

        $customer = (int)$call->customer_id;
        $edit = !(!$call->post->post_date_gmt || '0000-00-00 00:00:00' == $call->post->post_date_gmt);
        if (!$edit && isset($_GET['c_id']) & !empty($_GET['c_id'])) {
            $customer = (int)$_GET['c_id'];
            $the_customer = new WC_CRM_Customer($customer);
            $call->customer_id = $customer;
            $call->phone_number = $the_customer->phone;
        }

        $user_string = '';

        if ($customer) {
            $the_customer = new WC_CRM_Customer($customer);
            $name = $the_customer->get_name();
            $user_string = esc_html($name) . ' (#' . absint($the_customer->c_id) . ' ' . sanitize_email($the_customer->customer->email) . ')';
        } else {
            $customer = '';
        }
        $product_string = '';
        $product_id = (int)$call->product;
        if ($product_id) {
            $product_string = $call->get_product_name();
        } else {
            $product_id = '';
        }

        $order_string = '';
        $order_id = (int)$call->order;
        if ($order_id) {
            $order_string = $call->get_order_number();
        } else {
            $order_id = '';
        }

        $account = $call->get_account();

        $info_fields = apply_filters('wcrm_calls_info_fields', array(
            'dropdown_customers' => array(
                'name' => 'customer_id',
                'label' => __('Customer:', 'wc_crm'),
                'value' => $customer,
                'class' => 'wc-crm-customer-search',
                'type' => 'select',
                'options' => array(
                    $customer => htmlspecialchars($user_string)
                ),
                'custom_attributes' => array(
                    'data-allow_clear' => 'true',
                    'autocomplete' => 'off',
                    'data-action' => 'wc_crm_json_search_customers',
                    'data-placeholder' => __('Select a Customer&hellip;', 'wc_crm'),
                ),
            ),
            'post_title' => array(
                'label' => __('Subject:', 'wc_crm'),
                'value' => $call->subject,
                'type' => 'text',
                'custom_attributes' => array(
                    'size' => '30',
                    'autocomplete' => 'off',
                    'required' => true
                ),
            ),
            'phone_number' => array(
                'label' => __('Phone Number:', 'wc_crm'),
                'value' => $call->phone_number,
                'type' => 'text',
                'custom_attributes' => array(
                    'autocomplete' => 'off'
                ),
            ),
            'purpose' => array(
                'label' => __('Purpose:', 'wc_crm'),
                'value' => $call->purpose,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'options' => wc_crm_get_call_purposes(),
                'custom_attributes' => array(
                    'autocomplete' => 'off'
                ),
            ),
            'call_owner_name' => array(
                'label' => __('Call Owner:', 'wc_crm'),
                'value' => $call->get_owner_name(),
                'type' => 'text',
                'custom_attributes' => array(
                    'autocomplete' => 'off',
                    'disabled' => 'disabled',
                ),
            ),
            'type' => array(
                'label' => __('Type:', 'wc_crm'),
                'value' => $call->type,
                'class' => 'wc-enhanced-select',
                'type' => 'select',
                'options' => wc_crm_get_call_types(),
                'custom_attributes' => array(
                    'autocomplete' => 'off',
                    'required' => true
                ),
            ),
            'product' => array(
                'label' => __('Product:', 'wc_crm'),
                'value' => $product_id,
                'class' => 'wc-product-search',
                'type' => 'select',
                'options' => array(
                    $product_id => htmlspecialchars($product_string)
                ),
                'custom_attributes' => array(
                    'data-allow_clear' => 'true',
                    'autocomplete' => 'off',
                    'data-action' => 'woocommerce_json_search_products',
                    'data-placeholder' => __('Search for a product&hellip;', 'wc_crm'),
                ),
            ),
            'order' => array(
                'label' => __('Order:', 'wc_crm'),
                'value' => $order_id,
                'class' => 'wc-product-search',
                'type' => 'select',
                'options' => array(
                    $order_id => htmlspecialchars($order_string)
                ),
                'custom_attributes' => array(
                    'data-allow_clear' => 'true',
                    'autocomplete' => 'off',
                    'data-action' => 'wc_crm_json_search_orders',
                    'data-placeholder' => __('Search for an order&hellip;', 'wc_crm'),
                ),
            ),
            'account' => array(
                'label' => __('Account:', 'wc_crm'),
                'value' => $account['id'],
                'class' => 'wc-product-search',
                'type' => 'select',
                'options' => array(
                    $account['id'] => htmlspecialchars($account['title'])
                ),
                'custom_attributes' => array(
                    'data-allow_clear' => 'true',
                    'autocomplete' => 'off',
                    'data-action' => 'wc_crm_json_search_accounts',
                    'data-placeholder' => __('Search for an account&hellip;', 'wc_crm'),
                ),
            ),
            'post_content' => array(
                'label' => __('Call Results:', 'wc_crm'),
                'value' => $call->description,
                'type' => 'textarea',
            ),
        ));

        return $info_fields;
    }

    public static function save_meta_boxes($post_id, $post)
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
        if (empty($_POST['wc_crm_meta_nonce']) || !wp_verify_nonce($_POST['wc_crm_meta_nonce'], 'wc_crm_save_data')) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if (empty($_POST['post_ID']) || $_POST['post_ID'] != $post_id) {
            return;
        }

        // Check user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        self::$saved_meta_boxes = true;

        // Check the post type
        if ($post->post_type == 'wc_crm_calls') {


            $keys = wc_crm_get_call_populate_fields();
            foreach ($keys as $key) {

                switch ($key) {
                    case 'post_title':
                    case 'post_status':
                        continue;
                        break;
                    case 'customer_id':
                        if ($_POST[$key]) {
                            $the_customer = new WC_CRM_Customer($_POST[$key]);
                            $customer_name = $the_customer->get_name();
                            update_post_meta($post_id, '_customer_name', $customer_name);
                        }
                        update_post_meta($post_id, '_' . $key, $_POST[$key]);
                        break;
                    case 'call_duration':
                        $h = !empty($_POST['call_duration_h']) ? $_POST['call_duration_h'] : 0;
                        $m = !empty($_POST['call_duration_m']) ? $_POST['call_duration_m'] : 0;
                        $s = !empty($_POST['call_duration_s']) ? $_POST['call_duration_s'] : 0;
                        $call_duration = array($h, $m, $s);
                        update_post_meta($post_id, '_' . $key, $call_duration);
                        continue;
                        break;
                    default:
                        if (isset($_POST[$key])) {
                            update_post_meta($post_id, '_' . $key, $_POST[$key]);
                        } else {
                            delete_post_meta($post_id, '_' . $key);
                        }
                        break;
                }
            }

            do_action('wc_crm_process_calls_meta', $post_id, $post);
        }
    }
}
