<?php
/**
 * Customer Functions
 *
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

function wc_crm_get_cmeta($c_id, $key = '', $single = false)
{
    return get_metadata('wc_crm_customer', $c_id, $key, $single);
}

function wc_crm_update_cmeta($c_id, $meta_key, $meta_value, $prev_value = '')
{
    return update_metadata('wc_crm_customer', $c_id, $meta_key, $meta_value, $prev_value);
}

function wc_crm_add_cmeta($c_id, $meta_key, $meta_value)
{
    return add_metadata('wc_crm_customer', $c_id, $meta_key, $meta_value);
}

function wc_crm_reload_customer($id = 0)
{
    $user = get_userdata($id);

    if (!$user) {
        return array('error' => sprintf(__('Falha:% d é um ID de usuário inválido.', 'wc_crm'), $id));
    }
    $data = array(
        'user_id' => $id,
        'email' => '',
        'first_name' => '',
        'last_name' => '',
        'capabilities' => '',
        'state' => '',
        'city' => '',
        'country' => '',
        'status' => '',
        'order_id' => 0,
        'last_purchase' => 0,
        'order_value' => 0,
        'num_orders' => 0,
        'cpf' => 0,
        'cnpj' => 0,
    );
    $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d');

    global $wpdb;
    $sql = "SELECT
      users.user_email as email,
      fname.meta_value as first_name,
      lname.meta_value as last_name,
      caps.meta_value as capabilities,
      state.meta_value as state,
      city.meta_value as city,
      country.meta_value as country,
      status.meta_value as status
      lname.meta_value as cpf,
      lname.meta_value as cnpj,
    FROM {$wpdb->users} as users
    LEFT JOIN {$wpdb->usermeta} fname ON fname.user_id = users.ID AND fname.meta_key = 'first_name'
    LEFT JOIN {$wpdb->usermeta} lname ON lname.user_id = users.ID AND lname.meta_key = 'last_name'
    LEFT JOIN {$wpdb->usermeta} lname ON lname.user_id = users.ID AND lname.meta_key = 'billing_cpf'
    LEFT JOIN {$wpdb->usermeta} lname ON lname.user_id = users.ID AND lname.meta_key = 'billing_cnpj'
    LEFT JOIN {$wpdb->usermeta} caps ON caps.user_id = users.ID AND caps.meta_key = '{$wpdb->prefix}capabilities'
    LEFT JOIN {$wpdb->usermeta} state ON state.user_id = users.ID AND state.meta_key = 'billing_state'
    LEFT JOIN {$wpdb->usermeta} city ON city.user_id = users.ID AND city.meta_key = 'billing_city'
    LEFT JOIN {$wpdb->usermeta} country ON country.user_id = users.ID AND country.meta_key = 'billing_country'
    LEFT JOIN {$wpdb->usermeta} status ON status.user_id = users.ID AND status.meta_key = 'customer_status'
    WHERE users.ID = {$id}
    ";
    $result = $wpdb->get_row($sql, 'ARRAY_A');

    if ($result)
        $data = array_merge($data, $result);

    $orders_status = get_option('wc_crm_total_value');
    if (!$orders_status || empty($orders_status)) {
        $orders_status[] = 'wc-completed';
    }
    $orders_statuses = "'" . implode("','", $orders_status) . "'";
    $order_types = "'" . implode("','", wc_get_order_types('order-count')) . "'";

    $sql = "SELECT
      posts.ID as order_id,
      posts.post_date as last_purchase
    FROM {$wpdb->posts} as posts
    LEFT JOIN {$wpdb->postmeta} postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key = '_customer_user'
    WHERE postmeta.meta_value = {$id}
    AND   posts.post_type   IN ({$order_types})
    ORDER BY posts.post_date DESC
    LIMIT 1
    ";

    $result = $wpdb->get_row($sql, 'ARRAY_A');

    if ($result)
        $data = array_merge($data, $result);

    $sql = "SELECT
      SUM(v.meta_value) as order_value,
      COUNT(posts.ID) as num_orders
    FROM {$wpdb->posts} as posts
    LEFT JOIN {$wpdb->postmeta} postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key = '_customer_user'
    LEFT JOIN {$wpdb->postmeta} v ON v.post_id = posts.ID AND v.meta_key = '_order_total'
    WHERE postmeta.meta_value = {$id}
    AND   posts.post_type IN ({$order_types})
    AND   posts.post_status IN({$orders_statuses})
    ";

    $result = $wpdb->get_row($sql, 'ARRAY_A');

    if ($result)
        $data = array_merge($data, $result);

    $u_message = sprintf(__('<b>&quot;%s&quot; (ID %s)</b>', 'wc_crm'), esc_html($data['first_name'] . ' ' . $data['last_name']), $id);
    $message = '';


    $res = 0;
    if ($wpdb->get_var("SELECT c_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE user_id = {$id}")) {
        $status = get_user_meta($id, 'customer_status', true);
        if (empty($status) && empty($data['status'])) {
            $default_status = get_option('wc_crm_default_status_account');
            $data['status'] = $default_status;
            update_user_meta($id, 'customer_status', $data['status']);
        }
        $res = $wpdb->update("{$wpdb->prefix}wc_crm_customer_list", $data, array('user_id' => $id));
        $message .= sprintf(__('<br />Successfully updated in %s seconds', 'wc_crm'), timer_stop());

    } else {

        if (isset($_REQUEST['wc-ajax']) && $_REQUEST['wc-ajax'] == 'checkout') {

            $default_status = get_option('wc_crm_default_status_store');
            $data['status'] = !empty($default_status) ? $default_status : $data['status'];

        } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == 'createuser') {

            $default_status = get_option('wc_crm_default_status_crm');
            $data['status'] = !empty($default_status) ? $default_status : $data['status'];

        } elseif(strstr($_SERVER["REQUEST_URI"], 'pos_orders') !== false) {

            $u_obj = new WP_User($id);
            $default_status = get_option('wc_crm_default_status_store');
            $data['status'] = $default_status;
            $u_obj->add_cap($data['status']);

        } else {

            $default_status = get_option('wc_crm_default_status_account');
            $data['status'] = $default_status;

        }
        $res = $wpdb->insert("{$wpdb->prefix}wc_crm_customer_list", $data, $format);
        update_user_meta($id, 'customer_status', $data['status']);
        $message .= sprintf(__('<br />Successfully added in %s seconds', 'wc_crm'), timer_stop());
    }
    if (false === $res) {
        return array('error' => '<div id="message" class="error fade"><p>' . $u_message . ' cannot be loaded.</p></div>');
    } else {
        return array('success' => '<div id="message" class="updated fade"><p>' . $u_message . $message . '</p></div>');
    }
}

function wc_crm_reload_guest($email = '')
{
    if (empty($email)) {
        return array('error' => sprintf(__('Failed: %s is an invalid user email.', 'wc_crm'), $email));
    }

    global $wpdb;

    $data = array(
        'user_id' => 0,
        'email' => $email,
        'first_name' => '',
        'last_name' => '',
        'capabilities' => '',
        'state' => '',
        'city' => '',
        'country' => '',
        'order_id' => 0,
        'last_purchase' => 0,
        'order_value' => 0,
        'num_orders' => 0,
    );
    $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d');


    $orders_status = get_option('wc_crm_total_value');
    if (!$orders_status || empty($orders_status)) {
        $orders_status[] = 'wc-completed';
    }
    $orders_statuses = "'" . implode("','", $orders_status) . "'";
    $order_types = "'" . implode("','", wc_get_order_types('order-count')) . "'";

    $sql = "SELECT
      posts.ID as order_id,
      posts.post_date as last_purchase
    FROM {$wpdb->posts} as posts
    LEFT JOIN {$wpdb->postmeta} user ON user.post_id = posts.ID AND user.meta_key = '_customer_user'
    LEFT JOIN {$wpdb->postmeta} email ON email.post_id = posts.ID AND email.meta_key = '_billing_email'
    WHERE (user.meta_value = '' || user.meta_value = '0')
    AND   email.meta_value = '{$email}'
    AND   posts.post_type   IN ({$order_types})
    ORDER BY posts.post_date DESC
    LIMIT 1
    ";

    $result = $wpdb->get_row($sql, 'ARRAY_A');

    if ($result)
        $data = array_merge($data, $result);

    $sql = "SELECT
      SUM(v.meta_value) as order_value,
      COUNT(posts.ID) as num_orders
    FROM {$wpdb->posts} as posts
    LEFT JOIN {$wpdb->postmeta} user ON user.post_id = posts.ID AND user.meta_key = '_customer_user'
    LEFT JOIN {$wpdb->postmeta} email ON email.post_id = posts.ID AND email.meta_key = '_billing_email'
    LEFT JOIN {$wpdb->postmeta} v ON v.post_id = posts.ID AND v.meta_key = '_order_total'
    WHERE (user.meta_value = '' || user.meta_value = '0')
    AND   email.meta_value = '{$email}'
    AND   posts.post_type IN ({$order_types})
    AND   posts.post_status IN({$orders_statuses})
    ";

    $result = $wpdb->get_row($sql, 'ARRAY_A');

    if ($result)
        $data = array_merge($data, $result);

    $result = array(
        'first_name' => get_post_meta($data['order_id'], '_billing_first_name', true),
        'last_name' => get_post_meta($data['order_id'], '_billing_last_name', true),
        'state' => get_post_meta($data['order_id'], '_billing_state', true),
        'city' => get_post_meta($data['order_id'], '_billing_city', true),
        'country' => get_post_meta($data['order_id'], '_billing_country', true)
    );
    $data = array_merge($data, $result);

    $u_message = sprintf(__('<b>Guest &quot;%s&quot; </b>', 'wc_crm'), $email);
    $message = '';

    $res = 0;
    $default_status = get_option('wc_crm_default_status_account');

    if ($customer = $wpdb->get_row("SELECT c_id, status FROM {$wpdb->prefix}wc_crm_customer_list WHERE email = '{$email}' AND user_id = 0")) {

        if (empty($customer->status))
            $data['status'] = $default_status;

        $res = $wpdb->update("{$wpdb->prefix}wc_crm_customer_list", $data, array('c_id' => $customer->c_id));
        $message .= sprintf(__('<br /> Atualizado com sucesso em% s segundos', 'wc_crm'), timer_stop());
    } else {

        $data['status'] = $default_status;

        $res = $wpdb->insert("{$wpdb->prefix}wc_crm_customer_list", $data, $format);
        $message .= sprintf(__('<br />Adicionado com sucesso em% s segundos ', 'wc_crm'), timer_stop());
    }
    if (false === $res) {
        return array('error' => '<div id="message" class="error fade"><p>' . $u_message . ' não pode ser carregado.</p></div>');
    } else {
        return array('success' => '<div id="message" class="updated fade"><p>' . $u_message . $message . '</p></div>');
    }
}

/**
 * Gets template for emailing customers.
 *
 * @param $template_name
 * @param array $args
 */
function wc_crm_custom_woocommerce_get_template($template_name, $args = array())
{

    if ($args && is_array($args))
        extract($args);

    $located = dirname(WC_CRM_FILE) . '/templates/' . $template_name;

    do_action('woocommerce_before_template_part', $template_name, '', $located, $args);

    include($located);

    do_action('woocommerce_after_template_part', $template_name, '', $located, $args);

}

function get_customer_by_term($term = '')
{
    global $wpdb;
    $term = strtolower($term);
    $sql = " SELECT * FROM {$wpdb->prefix}wc_crm_customer_list
      WHERE LOWER(first_name) LIKE LOWER('%$term%') OR LOWER(last_name) LIKE LOWER('%$term%') OR LOWER(email) LIKE LOWER('%$term%') OR user_id LIKE '%$term%' OR concat_ws(' ',first_name,last_name) LIKE '%$term%'
      ";
    $users = $wpdb->get_results($sql);
    return $users;
}

function wc_crm_get_customer($user_id, $by = 'id')
{
    global $wpdb;
    switch ($by) {
        case 'user_id':
            $user_id = absint($user_id);
            $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_customer_list WHERE user_id = {$user_id} ";
            break;
        case 'email':
            $email = (string)$user_id;
            $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_customer_list WHERE email = '{$email}'";
            break;
        case 'id':
        default:
            $c_id = absint($user_id);
            $sql = "SELECT * FROM {$wpdb->prefix}wc_crm_customer_list WHERE c_id = {$c_id} ";
            break;
    }
    $users = $wpdb->get_row($sql);
    return $users;
}

function wc_crm_change_customer_status($action = '', $user_ids = array())
{

    if (empty($action) || empty($user_ids) || !is_array($user_ids)) return;

    foreach ($user_ids as $value) {
        $user_id = $value;
        $status = '';

        $statuses = wc_crm_get_statuses_slug();
        if (array_key_exists($action, $statuses)) {
            update_user_meta($user_id, 'customer_status', $action);
            $status = $action;
        }
        global $wpdb;
        $sql = "UPDATE {$wpdb->prefix}wc_crm_customer_list
                SET status = '$status'
                WHERE user_id = $user_id
            
      ";
        $wpdb->query($sql);
    }
}

