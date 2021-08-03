<?php

if (!defined('ABSPATH')) {
    exit;
}
$the_customer->init_address_fields();
$the_customer->init_general_fields();

$customer_watched = get_user_meta($the_customer->user_id, 'customer_watching', true);
?>
<div id="woocommerce-customer-detail" class="postbox ">
    <div class="inside" style="margin:0px; padding:0px;">
        <div class="panel-wrap woocommerce" id="customer_data">
            <div id="order_data" class="panel">
                <?php if ($the_customer->customer_id > 0) { ?>
                	<br>
                    <?php echo get_avatar($the_customer->email, 120); ?>
                    <h2><?php echo $the_customer->first_name . ' ' . $the_customer->last_name; ?></h2>
                    <h2 style="float: right; color: #777;">
                        #<?php echo $the_customer->customer_id; ?>
                    </h2>
                        <div id="watch-button">
                            <label>
                                <input type="checkbox" id="customer_watching" name="customer_watching" <?php checked( $customer_watched, 1) ?> >
                                <span class="button tips" data-tip="<?php _e("Pin Customer", "wc_crm") ?>"></span>
                            </label>
                        </div>
                    <p class="order_number total_value">
                        <?php echo wc_price($the_customer->order_value); ?>
                    </p>
                    <p class="order_number num_orders">
                        <?php
                        echo sprintf(_n('1 order', '%s orders', (int)$the_customer->num_orders, 'wc_crm'), (int)$the_customer->num_orders);
                        ?>
                    </p>
                    <?php if ($the_customer->user_id) {
                        $user_data = get_userdata(intval($the_customer->user_id)); ?>
                        <p class="order_number created_date">
                            <?php
                            echo wc_crm_get_customer_pretty_time($user_data->user_registered);
                            ?>
                        </p>
                    <?php } else if ($the_customer->order_id) { ?>

                    <?php } ?>
                <?php } ?>
                <div class="order_data_column_container">
                    <div class="order_data_column">
                        <h4><?php _e('General Details', 'wc_crm'); ?></h4>
                        <div id="customer_general_details">
                            <?php
                            $disabled = array('first_name', 'last_name', 'user_email');
                            if ($the_customer->general_fields) {
                                foreach ($the_customer->general_fields as $key => $field) {

                                    if($the_customer->user_id == 0 && $key == "customer_agent"){
                                        continue;
                                    }

                                    $field['id'] = $key;
                                    if (!isset($field['type']))
                                        $field['type'] = 'text';

                                    if ($key == 'customer_status' && empty($field['value'])) {
                                        $default_status = get_option('wc_crm_default_status_crm');
                                        $field['value'] = !empty($default_status) ? $default_status : 'Customer';
                                    }
                                    if ($id > 0 && $the_customer->user_id == 0 && in_array($key, $disabled)) {
                                        $field['custom_attributes']['disabled'] = true;
                                    }

                                    switch ($field['type']) {
                                        case "select" :
                                            woocommerce_wp_select($field);
                                            break;
                                        case "multiselect" :
                                            wc_crm_wp_multiselect($field);
                                            break;
                                        default :
                                            woocommerce_wp_text_input($field);
                                            break;
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="order_data_column" id="order_data_column_billing">
                        <h4>
                            <?php _e('Billing Details', 'woocommerce'); ?>
                            <?php if ($the_customer->user_id > 0) { ?>
                                <a href="#" class="edit_address"><?php _e('Edit', 'woocommerce'); ?></a>
                                <a href="#" class="tips load_customer_billing"
                                   data-tip="<?php esc_attr_e('Load billing address', 'woocommerce'); ?>"
                                   style="display:none;"><?php _e('Load billing address', 'woocommerce'); ?></a>
                            <?php } ?>
                        </h4>
                        <div class="address" <?php echo $id == 0 ? 'style="display: none;"' : ''; ?> >
                            <?php
                            if ($the_customer->get_formatted_billing_address())
                                echo '<p><strong>' . __('Address', 'wc_crm') . ':</strong>' . wp_kses($the_customer->get_formatted_billing_address(), array('br' => array())) . '</p>';
                            else
                                echo '<p class="none_set"><strong>' . __('Address', 'wc_crm') . ':</strong> ' . __('No billing address set.', 'wc_crm') . '</p>';

                            foreach ($the_customer->billing_fields as $key => $field) {
                                if (isset($field['show']) && $field['show'] === false)
                                    continue;

                                $field_name = 'billing_' . $key;
                                $field_value = $the_customer->$field_name;

                                if (!empty($field_value))
                                    echo '<p><strong>' . esc_html($field['label']) . ':</strong> ' . make_clickable(esc_html($field_value)) . '</p>';
                            }
                            if (WC()->payment_gateways())
                                $payment_gateways = WC()->payment_gateways->payment_gateways();

                            $payment_method = $the_customer->preferred_payment_method;

                            if ($payment_method)
                                echo '<p><strong>' . __('Preferred Payment Method', 'wc_crm') . ':</strong> ' . (isset($payment_gateways[$payment_method]) ? esc_html($payment_gateways[$payment_method]->get_title()) : esc_html($payment_method)) . '</p>';
                            ?>
                        </div>
                        <?php if ($id == 0 || $the_customer->user_id > 0) { ?>
                            <div class="edit_address" <?php echo $id == 0 ? 'style="display: block;"' : ''; ?> >
                                <?php
                                foreach ($the_customer->billing_fields as $key => $field) {
                                    if (!isset($field['type'])) {
                                        $field['type'] = 'text';
                                    }

                                    if (isset($data['_billing_' . $key]) && $data['_billing_' . $key]) {
                                        $value = $data['_billing_' . $key];
                                    } else {
                                        $var_name = 'billing_' . $key;
                                        $value = $the_customer->$var_name;
                                    }

                                    $field['id'] = '_billing_' . $key;
                                    $field['value'] = $value;

                                    switch ($field['type']) {
                                        case "select" :
                                            woocommerce_wp_select($field);
                                            break;
                                        default :
                                            woocommerce_wp_text_input($field);
                                            break;
                                    }
                                }
                                ?>
                                <p class="form-field form-field-wide">
                                    <label><?php _e('Payment Method:', 'wc_crm'); ?></label>
                                    <select name="_payment_method" id="_payment_method" class="first">
                                        <option value=""><?php _e('N/A', 'wc_crm'); ?></option>
                                        <?php
                                        $found_method = false;
                                        foreach ($payment_gateways as $gateway) {
                                            if ($gateway->enabled == "yes") {
                                                echo '<option value="' . esc_attr($gateway->id) . '" ' . selected($payment_method, $gateway->id, false) . '>' . esc_html($gateway->get_title()) . '</option>';
                                                if ($payment_method == $gateway->id)
                                                    $found_method = true;
                                            }
                                        }

                                        if (!$found_method && !empty($payment_method)) {
                                            echo '<option value="' . esc_attr($payment_method) . '" selected="selected">' . __('Other', 'wc_crm') . '</option>';
                                        } else {
                                            echo '<option value="other">' . __('Other', 'wc_crm') . '</option>';
                                        }
                                        ?>
                                    </select>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="order_data_column" id="order_data_column_shipping">
                        <h4>
                            <?php _e('Shipping Details', 'woocommerce'); ?>
                            <?php if ($the_customer->user_id > 0) { ?>
                                <a href="#" class="edit_address"><?php _e('Edit', 'woocommerce'); ?></a>
                                <a href="#" class="tips billing-same-as-shipping"
                                   data-tip="<?php esc_attr_e('Copy from billing', 'woocommerce'); ?>"
                                   style="display:none;"><?php _e('Copy from billing', 'woocommerce'); ?></a>
                                <a href="#" class="tips load_customer_shipping"
                                   data-tip="<?php esc_attr_e('Load shipping address', 'woocommerce'); ?>"
                                   style="display:none;"><?php _e('Load shipping address', 'woocommerce'); ?></a>
                            <?php } ?>
                        </h4>
                        <div class="address" <?php echo $id == 0 ? 'style="display: none;"' : ''; ?> >
                            <?php
                            if ($the_customer->get_formatted_shipping_address()) {
                                echo '<p><strong>' . __('Address', 'wc_crm') . ':</strong>' . wp_kses($the_customer->get_formatted_shipping_address(), array('br' => array())) . '</p>';
                            } else {
                                echo '<p class="none_set"><strong>' . __('Address', 'wc_crm') . ':</strong> ' . __('No shipping address set.', 'wc_crm') . '</p>';
                            }
                            if ($the_customer->shipping_fields) foreach ($the_customer->shipping_fields as $key => $field) {
                                if (isset($field['show']) && $field['show'] === false) {
                                    continue;
                                }

                                $field_name = 'shipping_' . $key;
                                $field_value = $the_customer->$field_name;

                                if (!empty($order->$field_name)) {
                                    echo '<p><strong>' . esc_html($field['label']) . ':</strong> ' . make_clickable(esc_html($field_value)) . '</p>';
                                }
                            }
                            ?>
                        </div>
                        <?php if ($id == 0 || $the_customer->user_id > 0) { ?>
                            <div class="edit_address" <?php echo $id == 0 ? 'style="display: block;"' : ''; ?> >
                                <?php
                                foreach ($the_customer->shipping_fields as $key => $field) {
                                    if (!isset($field['type'])) {
                                        $field['type'] = 'text';
                                    }

                                    if (isset($data['_shipping_' . $key]) && $data['_shipping_' . $key]) {
                                        $value = $data['_shipping_' . $key];
                                    } else {
                                        $var_name = 'shipping_' . $key;
                                        $value = $the_customer->$var_name;
                                    }

                                    $field['id'] = '_shipping_' . $key;
                                    $field['value'] = $value;

                                    switch ($field['type']) {
                                        case "select" :
                                            woocommerce_wp_select($field);
                                            break;
                                        default :
                                            woocommerce_wp_text_input($field);
                                            break;
                                    }
                                }
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php
                if ($id > 0) {
                    if (get_option('wc_crm_google_map_address', 'billing') == 'billing') {
                        $address_l = $the_customer->get_billing_address_map_address();
                    } else {
                        $address_l = $the_customer->get_shipping_address_map_address();
                    }
                    ?>
                    <script>
                        var wc_pos_customer_formatted_billing_address = '<?php echo $address_l; ?>';
                    </script>
                <?php } ?>
                <div class="clear"></div>
            </div>
        </div>
        <script>
            jQuery('.form-row-left').parent().css('float', 'left');
            jQuery('.form-row-right').parent().css('float', 'right');
        </script>
    </div>
</div>