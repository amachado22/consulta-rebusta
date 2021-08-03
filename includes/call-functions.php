<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Get all call statuses.
 *
 * @since 3.0.6
 * @return array
 */
function wc_crm_get_call_statuses()
{
    $call_statuses = array(
        'wcrm-current' => _x('Current Call', 'Call status', 'wc_crm'),
        'wcrm-completed' => _x('Completed Call', 'Call status', 'wc_crm'),
        'future' => _x('Schedule Call', 'Call status', 'wc_crm'),
    );
    return array_merge($call_statuses, apply_filters('wc_crm_call_statuses', array()));
}

/**
 * Get all call types.
 *
 * @since 3.0.6
 * @return array
 */
function wc_crm_get_call_types()
{
    return array(
        'Inbound' => _x('Inbound', 'Call type', 'wc_crm'),
        'Outbound' => _x('Outbound', 'Call type', 'wc_crm'),
    );
}

/**
 * Get all call purposes.
 *
 * @since 3.0.6
 * @return array
 */
function wc_crm_get_call_purposes()
{
    $purposes = array(
        'None' => _x('None', 'Call purpose', 'wc_crm'),
        'Prospecting' => _x('Prospecting', 'Call purpose', 'wc_crm'),
        'Administrative' => _x('Administrative', 'Call purpose', 'wc_crm'),
        'Negotiation' => _x('Negotiation', 'Call purpose', 'wc_crm'),
        'Demo' => _x('Demo', 'Call purpose', 'wc_crm'),
        'Project' => _x('Project', 'Call purpose', 'wc_crm'),
        'Support' => _x('Support', 'Call purpose', 'wc_crm'),
    );
    return apply_filters('wc_crm_call_purposes', $purposes);
}

function wc_crm_get_call_populate_fields()
{
    $keys = array('type', 'purpose', 'customer_id', 'product', 'order', 'call_duration', 'phone_number', 'account');
    return array_merge($keys, apply_filters('wc_crm_call_populate', array()));
}

function wc_crm_formatTime($seconds)
{
    $t = round($seconds);
    $h = $t / 3600;
    $m = $t / 60 % 60;
    $s = $t % 60;
    $ms = ($t - ($s * 100) - ($m - 6000)) % 100;
    return sprintf('%02d:%02d:%02d:%02d', $h, $m, $s, $ms);
}

function wc_crm_get_cookie_call_duration($post)
{
    if (!isset($_COOKIE['wc_crm_current_call'])) return false;
    $current_call = json_decode(stripslashes($_COOKIE['wc_crm_current_call']));

    if ($post && !is_null($post) && $post->ID != $current_call->post_id) return false;

    $call = new WC_CRM_Call($current_call->post_id);

    $can_edit_post = current_user_can('edit_post', $call->id);
    //$title = _draft_or_post_title($call->id);

    if (!$can_edit_post || $call->call_status == 'trash') return false;
    $edit_link = get_edit_post_link($call->id);

    if ($current_call->call_stop <= 0) {
        $current_call->call_stop = time();
    }
    if ($current_call->pause_stamp > 0) {
        $current_call->call_stop = $current_call->pause_stamp;
        $current_call->pause_stamp = 0;
    }
    $duration = $current_call->call_stop - $current_call->call_start - $current_call->pause_duration;
    return $duration;
}

//Todo create Calls filters class
function get_call_owner_filter()
{
    global $wpdb;
    $_customer_user = isset($_REQUEST['_call_owner']) ? (int)$_REQUEST['_call_owner'] : 0;
    $user_string = '';
    if ($_customer_user > 0) {
        $user = get_user_by('id', $_customer_user);
        $user_string = esc_html($user->display_name) . ' (#' . absint($user->ID) . ' - ' . esc_html($user->user_email);
    }
    ?>
    <?php if (WC_VERSION >= 3) { ?>
    <select id="dropdown_customers" name="_call_owner"
            value="<?php echo isset($_REQUEST['_call_owner']) ? $_REQUEST['_call_owner'] : ''; ?>"
            class="wc-crm-customer-search" style="width: 200px" data-placeholder="Search for an owner..."
            data-allow_clear="true" data-selected="<?php echo htmlspecialchars($user_string); ?>"></select>
<?php } else { ?>
    <input type="text" id="dropdown_customers" name="_call_owner"
           value="<?php echo isset($_REQUEST['_call_owner']) ? $_REQUEST['_call_owner'] : ''; ?>"
           class="wc-crm-customer-search" style="width: 200px" data-placeholder="Search for an owner..."
           data-allow_clear="true" data-selected="<?php echo htmlspecialchars($user_string); ?>">
<?php } ?>
    <?php
}