<?php
/**
 * CRM Logs
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_CRM_Call
{

    /** @public int Call (post) ID. */
    public $id = 0;

    /** @var string Call Subject. */
    public $subject = '';

    /** @public string Call Status. */
    public $call_status = '';


    /** @public string Call Type. */
    public $type = '';

    /** @public string Call Purpose . */
    public $purpose = '';

    /** @public int Post Author (user) ID. */
    public $call_owner;

    /** @public int Customer (user) ID. */
    public $customer_id = 0;

    /** @public int Product ID. */
    public $product = 0;

    /** @public int Order ID. */
    public $order = 0;

    /** @public string Call Date. */
    public $call_date = '';

    /** @public string Modified Date. */
    public $modified_date = '';

    /** @public string Call Duration. */
    public $call_duration = array(0, 0, 0);

    /** @public string Phone Number. */
    public $phone_number = '';

    /** @public string Call Description. */
    public $description = '';

    /** @var $post WP_Post. */
    public $post = null;

    public function __construct($call = 0)
    {
        $this->init($call);
    }

    /**
     * Init/load the call object. Called from the constructor.
     *
     * @param  int|object|WC_Call $call Call to init.
     */
    protected function init($call)
    {
        if (is_numeric($call)) {
            $this->id = absint($call);
            $this->post = get_post($call);
            $this->get_call($this->id);
        } elseif ($call instanceof WC_CRM_Call) {
            $this->id = absint($call->id);
            $this->post = $call->post;
            $this->get_call($this->id);
        } elseif (isset($call->ID)) {
            $this->id = absint($call->ID);
            $this->post = $call;
            $this->get_call($this->id);
        }
    }


    /**
     * Gets an call from the database.
     *
     * @param int $id (default: 0).
     * @return bool
     */
    public function get_call($id = 0)
    {

        if (!$id) {
            return false;
        }

        if ($result = get_post($id)) {
            $this->populate($result);
            return true;
        }

        return false;
    }

    /**
     * Populates an call from the loaded post data.
     *
     * @param mixed $result
     */
    public function populate($result)
    {

        // Standard post data
        $statuses = wc_crm_get_call_statuses();

        $this->id = $result->ID;
        $this->subject = $result->post_title;
        $this->call_owner = $result->post_author;
        $this->call_date = $result->post_date;
        $this->modified_date = $result->post_modified;
        $this->description = $result->post_content;
        $this->call_status = isset($statuses[$result->post_status]) ? $result->post_status : (!empty($statuses) ? key($statuses) : $result->post_status);
        $keys = wc_crm_get_call_populate_fields();
        foreach ($keys as $key) {
            $this->$key = get_post_meta($result->ID, '_' . $key, true);
            switch ($key) {
                case 'call_duration':
                    if (!is_array($this->$key)) {
                        $this->$key = array(0, 0, 0);
                    }
                    break;
            }
        }
    }

    public function get_customer_name()
    {
        $customer_name = '';
        if ($this->customer_id > 0) {
            $the_customer = new WC_CRM_Customer($this->customer_id);
            $customer_name = $the_customer->get_name();
        }
        return $customer_name;
    }

    public function get_product_name()
    {
        $product_name = '';
        if ($this->product > 0) {
            $product = wc_get_product($this->product);
            if (is_object($product)) {
                $product_name = wp_kses_post(html_entity_decode($product->get_formatted_name(), ENT_QUOTES, get_bloginfo('charset')));
            }
        }
        return $product_name;
    }

    public function get_order_number()
    {
        $order_number = '';
        if ($this->order > 0) {
            $user_info = '';
            $order = wc_get_order($this->order);
            if (!$order) return '';
            if ($order->get_user_id()) {
                $user_info = get_userdata($order->get_user_id());
            }

            if (!empty($user_info)) {

                $username = '';

                if ($user_info->first_name || $user_info->last_name) {
                    $username .= esc_html(sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), ucfirst($user_info->first_name), ucfirst($user_info->last_name)));
                } else {
                    $username .= esc_html(ucfirst($user_info->display_name));
                }

            } else {
                if ($order->get_billing_first_name() || $order->get_billing_last_name()) {
                    $username = trim(sprintf(_x('%1$s %2$s', 'full name', 'woocommerce'), $order->get_billing_first_name(), $order->get_billing_last_name()));
                } else {
                    $username = __('Guest', 'woocommerce');
                }
            }

            $order_number = sprintf(_x('Order %s by %s', 'Order number by X', 'woocommerce'), '#' . esc_attr($order->get_order_number()), $username);
        }
        return $order_number;
    }

    public function get_owner_name()
    {
        $owner_name = '';
        $user_meta = (object)get_user_meta($this->call_owner);
        $owner_name = trim(implode(' ', array($user_meta->first_name[0], $user_meta->last_name[0])));
        if (empty($owner_name)) {
            $owner_name = $user_meta->user_nicename[0];
        }
        return $owner_name;
    }

    public function get_formated_call_duration()
    {
        $call_duration = $this->call_duration[0] > 0 ? $this->call_duration[0] . ' h ' : '';
        $call_duration = $this->call_duration[1] > 0 ? $this->call_duration[1] . ' min ' : (!empty($call_duration) ? '0 min ' : '');
        $call_duration .= $this->call_duration[2] . ' sec';
        return $call_duration;
    }

    public function get_account()
    {
        $account = array(
            'id' => get_post_meta($this->id, '_account', true)
        );

        $account['title'] = get_the_title($account['id']);
        return $account;
    }

} //class