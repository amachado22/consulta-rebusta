<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Get group taxonomies.
 *
 * @return object
 */
function wc_crm_get_groups()
{

    $transient_name = 'wc_crm_group_taxonomies';

    if (false === ($group_taxonomies = get_transient($transient_name))) {

        global $wpdb;

        $group_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wc_crm_groups");

        set_transient($transient_name, $group_taxonomies);
    }

    return apply_filters('wc_crm_groups', $group_taxonomies);
}

/**
 * Get static group taxonomies.
 *
 * @return object
 */
function wc_crm_get_static_groups()
{

    $transient_name = 'wc_crm_static_group_taxonomies';

    if (false === ($group_taxonomies = get_transient($transient_name))) {

        global $wpdb;

        $group_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wc_crm_groups WHERE group_type = 'static' ");

        set_transient($transient_name, $group_taxonomies);
    }

    return apply_filters('wc_crm_static_groups', $group_taxonomies);
}

function wc_get_static_groups()
{

    $transient_name = 'wc_crm_static_group_taxonomies';

    if (false === ($group_taxonomies = get_transient($transient_name))) {

        global $wpdb;

        $group_taxonomies = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_crm_groups WHERE group_type = 'static'");

        set_transient($transient_name, $group_taxonomies);
    }

    return apply_filters('wc_crm_static_groups', $group_taxonomies);
}

function wc_get_static_groups_ids_array()
{
    $groups = wc_get_static_groups();
    $ids = array();
    foreach ($groups as $group) {
        $ids[] = $group->ID;
    }
    return apply_filters('wc_static_groups_ids_array', $ids);;
}

function wc_get_dynamic_groups()
{

    $transient_name = 'wc_crm_dynamic_group_taxonomies';

    if (false === ($group_taxonomies = get_transient($transient_name))) {

        global $wpdb;

        $group_taxonomies = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_crm_groups WHERE group_type = 'dynamic'");

        set_transient($transient_name, $group_taxonomies);
    }

    return apply_filters('wc_crm_dynamic_groups', $group_taxonomies);
}


function wc_crm_group_exists($group_slug)
{
    global $wpdb;

    return $label = $wpdb->get_var($wpdb->prepare("SELECT group_slug FROM {$wpdb->prefix}wc_crm_groups WHERE group_slug = %s;", $group_slug));
}

function wc_crm_delete_group_transient()
{
    $transient = array('wc_crm_static_group_taxonomies', 'wc_crm_group_taxonomies', 'wc_crm_dynamic_group_taxonomies');
    foreach ($transient as $name) {
        delete_transient($name);
    }
}

function convert_group_total_spent_mark($value = '')
{
    switch ($value) {
        case 'equal':
            return '=';
            break;
        case 'greater':
            return '>';
            break;
        case 'less':
            return '<';
            break;
        case 'greater_or_equal':
            return '&ge;';
            break;
        case 'less_or_equal':
            return '&le;';
            break;
    }
}

add_action('wc_crm_group_updated', 'wc_crm_delete_group_transient');
add_action('wc_crm_group_added', 'wc_crm_delete_group_transient');
add_action('wc_crm_group_deleted', 'wc_crm_delete_group_transient');


function wc_crm_add_to_group($group_id = 0, $customers_ids = array())
{
    if (!$group_id || empty($customers_ids) || !is_array($customers_ids)) return false;

    $groups_array = wc_get_static_groups_ids_array();
    if (!in_array($group_id, $groups_array)) return false;
    foreach ($customers_ids as $c_id) {
        global $wpdb;
        $data = array(
            'group_id' => $group_id,
            'c_id' => $c_id
        );
        $table = $wpdb->prefix . 'wc_crm_groups_relationships';
        $wpdb->hide_errors();
        if (!$wpdb->query("SELECT ID FROM $table WHERE group_id = {$group_id} AND c_id = {$c_id} ")) {
            $wpdb->query("INSERT INTO $table (group_id, c_id) VALUES ({$group_id}, '{$c_id}');");
        }
    }

}

function wc_crm_get_groups_by_id($ids)
{ //Should be array
    global $wpdb;
    if (!is_array($ids)) {
        return false;
    }
    $query_var = "'" . implode("','", $ids) . "'";
    $result = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "wc_crm_groups WHERE ID IN ({$query_var})");
    return $result;
}