<?php
/**
 * Class for Export
 *
  * @author     Adailton Machado
 * @package  WC_CRM
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_CRM_Export
{

    public static function init($ids = array())
    {

        global $wpdb;
        $guest = __('Guest', 'wc_crm');
        $enrolled_plain = "'No'";
        if (woocommerce_crm_mailchimp_enabled()) {
            $members = woocommerce_crm_get_members();
            if (!empty($members)) {
                $enrolled_plain = "IF(customers.email IN('" . implode("','", $members) . "'), 'Yes', 'No') ";
            }
        }
        $acf_f = '';
        $acf_join = '';
        if (class_exists('acf')) {
            $acf_fields = array();
            $acf_j = array();
            $acf_val = get_acf_fields_array();
            foreach ($acf_val as $key => $value) {
                $meta_key_val = str_replace('acf_', '', $key);
                $acf_fields[] = "{$key}.meta_value as '{$key}'";
                $acf_j[] = "LEFT JOIN {$wpdb->usermeta} as {$key} ON ( customers.user_id = {$key}.user_id AND {$key}.meta_key = '{$meta_key_val}')";
            }
            $acf_f = ',' . implode(',', $acf_fields);
            $acf_join = implode(' ', $acf_j);
        }

        $group_join = '';
        $filter = '';

        if (isset($_REQUEST['group']) && $_REQUEST['group']) {
            $type_sql = $wpdb->prepare("SELECT g.`group_type` FROM {$wpdb->prefix}wc_crm_groups g WHERE g.`ID` = %d", $_REQUEST['group']);
            $group_type = $wpdb->get_var($type_sql);
            switch ($group_type) {
                case 'static':
                    $group_join = $wpdb->prepare("INNER JOIN {$wpdb->prefix}wc_crm_groups_relationships g ON (customers.c_id = g.`c_id` AND g.`group_id` = %d)", $_REQUEST['group']);
                    break;
                case 'dynamic':
                    $dynamic_group_sql = WC_CRM_Screen_Customers_List::get_dynamic_group_sql(intval($_REQUEST['group']));
                    $group_join = "INNER JOIN ({$dynamic_group_sql}) as g ON (customers.c_id = g.`c_id`)";
                    break;
            }
        }
        if (!empty($ids)) {
            $filter = " AND customers.c_id IN(" . implode(', ', $ids) . ")";
        } else {
            $user_roles = get_option('wc_crm_user_roles', array('customer'));
            $guest_customers = get_option('wc_crm_guest_customers', 'no');
            if (empty($user_roles) || !is_array($user_roles)) {
                $user_roles = array('customer');
            }
            foreach ($user_roles as $value) {
                if (!empty($filter)) $filter .= ' OR ';
                $filter .= "customers.capabilities LIKE '%{$value}%'";
            }
            if ($guest_customers == 'yes') {
                $filter .= " OR customers.capabilities = ''";
            }
            $filter = " AND (" . $filter . ')';
        }


        $query = "SELECT 
				CONCAT(customers.first_name, ' ', customers.last_name) as 'Customer name',
				customers.first_name as 'customer_first_name',
				customers.last_name as 'customer_last_name',
				customers.email as 'email',
				IF(u1.meta_value is not NULL, u1.meta_value, p1.meta_value) as 'billing_first_name',
				IF(u2.meta_value is not NULL, u2.meta_value, p2.meta_value) as 'billing_last_name',
				IF(u3.meta_value is not NULL, u3.meta_value, p3.meta_value) as 'billing_company',
				IF(u4.meta_value is not NULL, u4.meta_value, p4.meta_value) as 'billing_address_1',
				IF(u5.meta_value is not NULL, u5.meta_value, p5.meta_value) as 'billing_address_2',
				IF(u6.meta_value is not NULL, u6.meta_value, p6.meta_value) as 'billing_city',
				IF(u7.meta_value is not NULL, u7.meta_value, p7.meta_value) as 'billing_postcode',
				IF(u8.meta_value is not NULL, u8.meta_value, p8.meta_value) as 'billing_country',
				IF(u9.meta_value is not NULL, u9.meta_value, p9.meta_value) as 'billing_state',
                IF(u24.meta_value is not NULL, u24.meta_value, p24.meta_value) as 'billing_cpf',
                IF(u25.meta_value is not NULL, u25.meta_value, p25.meta_value) as 'billing_cnpj',
				IF(u10.meta_value is not NULL, u10.meta_value, p10.meta_value) as 'billing_email',
				IF(u11.meta_value is not NULL, u11.meta_value, p11.meta_value) as 'billing_phone',
				IF(u12.meta_value is not NULL, u12.meta_value, p12.meta_value) as 'shipping_first_name',
				IF(u13.meta_value is not NULL, u13.meta_value, p13.meta_value) as 'shipping_last_name',
				IF(u14.meta_value is not NULL, u14.meta_value, p14.meta_value) as 'shipping_company',
				IF(u15.meta_value is not NULL, u15.meta_value, p15.meta_value) as 'shipping_address_1',
				IF(u16.meta_value is not NULL, u16.meta_value, p16.meta_value) as 'shipping_address_2',
				IF(u17.meta_value is not NULL, u17.meta_value, p17.meta_value) as 'shipping_city',
				IF(u18.meta_value is not NULL, u18.meta_value, p18.meta_value) as 'shipping_postcode',
				IF(u19.meta_value is not NULL, u19.meta_value, p19.meta_value) as 'shipping_country',
				IF(u20.meta_value is not NULL, u20.meta_value, p20.meta_value) as 'shipping_state',
				IF( customers.last_purchase ='0000-00-00 00:00:00', '', customers.last_purchase) as 'last_purchase_date',
				customers.num_orders as 'num_orders',
				format(customers.order_value, 2) as 'total_value',
				{$enrolled_plain} as 'susbcribed'
				{$acf_f}
				,IF(u21.meta_value is not NULL, u21.meta_value, p21.meta_value) as 'lead_source',
				IF(u21.meta_value is not NULL, u22.meta_value, p22.meta_value) as 'industry',
				IF(u21.meta_value is not NULL, u23.meta_value, p23.meta_value) as 'lead_status',
				FROM {$wpdb->prefix}wc_crm_customer_list as customers
				LEFT JOIN {$wpdb->usermeta} as u1 ON ( customers.user_id = u1.user_id AND u1.meta_key = 'billing_first_name' )
				LEFT JOIN {$wpdb->usermeta} as u2 ON ( customers.user_id = u2.user_id AND u2.meta_key = 'billing_last_name' )
				LEFT JOIN {$wpdb->usermeta} as u3 ON ( customers.user_id = u3.user_id AND u3.meta_key = 'billing_company' )
				LEFT JOIN {$wpdb->usermeta} as u4 ON ( customers.user_id = u4.user_id AND u4.meta_key = 'billing_address_1' )
				LEFT JOIN {$wpdb->usermeta} as u5 ON ( customers.user_id = u5.user_id AND u5.meta_key = 'billing_address_2' )
				LEFT JOIN {$wpdb->usermeta} as u6 ON ( customers.user_id = u6.user_id AND u6.meta_key = 'billing_city' )
				LEFT JOIN {$wpdb->usermeta} as u7 ON ( customers.user_id = u7.user_id AND u7.meta_key = 'billing_postcode' )
				LEFT JOIN {$wpdb->usermeta} as u8 ON ( customers.user_id = u8.user_id AND u8.meta_key = 'billing_country' )
				LEFT JOIN {$wpdb->usermeta} as u9 ON ( customers.user_id = u9.user_id AND u9.meta_key = 'billing_state' )
				LEFT JOIN {$wpdb->usermeta} as u10 ON ( customers.user_id = u10.user_id AND u10.meta_key = 'billing_email' )
				LEFT JOIN {$wpdb->usermeta} as u11 ON ( customers.user_id = u11.user_id AND u11.meta_key = 'billing_phone' )
				LEFT JOIN {$wpdb->usermeta} as u12 ON ( customers.user_id = u12.user_id AND u12.meta_key = 'shipping_first_name' )
				LEFT JOIN {$wpdb->usermeta} as u13 ON ( customers.user_id = u13.user_id AND u13.meta_key = 'shipping_last_name' )
				LEFT JOIN {$wpdb->usermeta} as u14 ON ( customers.user_id = u14.user_id AND u14.meta_key = 'shipping_company' )
				LEFT JOIN {$wpdb->usermeta} as u15 ON ( customers.user_id = u15.user_id AND u15.meta_key = 'shipping_address_1' )
				LEFT JOIN {$wpdb->usermeta} as u16 ON ( customers.user_id = u16.user_id AND u16.meta_key = 'shipping_address_2' )
				LEFT JOIN {$wpdb->usermeta} as u17 ON ( customers.user_id = u17.user_id AND u17.meta_key = 'shipping_city' )
				LEFT JOIN {$wpdb->usermeta} as u18 ON ( customers.user_id = u18.user_id AND u18.meta_key = 'shipping_postcode' )
				LEFT JOIN {$wpdb->usermeta} as u19 ON ( customers.user_id = u19.user_id AND u19.meta_key = 'shipping_country' )
				LEFT JOIN {$wpdb->usermeta} as u20 ON ( customers.user_id = u20.user_id AND u20.meta_key = 'shipping_state' )
				LEFT JOIN {$wpdb->usermeta} as u21 ON ( customers.user_id = u21.user_id AND u21.meta_key = 'lead_source' )
				LEFT JOIN {$wpdb->usermeta} as u22 ON ( customers.user_id = u22.user_id AND u22.meta_key = 'industry' )
				LEFT JOIN {$wpdb->usermeta} as u23 ON ( customers.user_id = u23.user_id AND u23.meta_key = 'lead_status' )
				LEFT JOIN {$wpdb->postmeta} as p1 ON ( customers.order_id = p1.post_id AND p1.meta_key = '_billing_first_name' )
				LEFT JOIN {$wpdb->postmeta} as p2 ON ( customers.order_id = p2.post_id AND p2.meta_key = '_billing_lastpname' )
				LEFT JOIN {$wpdb->postmeta} as p3 ON ( customers.order_id = p3.post_id AND p3.meta_key = '_billing_company' )
				LEFT JOIN {$wpdb->postmeta} as p4 ON ( customers.order_id = p4.post_id AND p4.meta_key = '_billing_address_1' )
				LEFT JOIN {$wpdb->postmeta} as p5 ON ( customers.order_id = p5.post_id AND p5.meta_key = '_billing_address_2' )
				LEFT JOIN {$wpdb->postmeta} as p6 ON ( customers.order_id = p6.post_id AND p6.meta_key = '_billing_city' )
				LEFT JOIN {$wpdb->postmeta} as p7 ON ( customers.order_id = p7.post_id AND p7.meta_key = '_billing_postcode' )
				LEFT JOIN {$wpdb->postmeta} as p8 ON ( customers.order_id = p8.post_id AND p8.meta_key = '_billing_country' )
				LEFT JOIN {$wpdb->postmeta} as p9 ON ( customers.order_id = p9.post_id AND p9.meta_key = '_billing_state' )
				LEFT JOIN {$wpdb->postmeta} as p10 ON ( customers.order_id = p10.post_id AND p10.meta_key = '_billing_email' )
				LEFT JOIN {$wpdb->postmeta} as p11 ON ( customers.order_id = p11.post_id AND p11.meta_key = '_billing_phone' )
				LEFT JOIN {$wpdb->postmeta} as p12 ON ( customers.order_id = p12.post_id AND p12.meta_key = '_shipping_first_name' )
				LEFT JOIN {$wpdb->postmeta} as p13 ON ( customers.order_id = p13.post_id AND p13.meta_key = '_shipping_lastpname' )
				LEFT JOIN {$wpdb->postmeta} as p14 ON ( customers.order_id = p14.post_id AND p14.meta_key = '_shipping_company' )
				LEFT JOIN {$wpdb->postmeta} as p15 ON ( customers.order_id = p15.post_id AND p15.meta_key = '_shipping_address_1' )
				LEFT JOIN {$wpdb->postmeta} as p16 ON ( customers.order_id = p16.post_id AND p16.meta_key = '_shipping_address_2' )
				LEFT JOIN {$wpdb->postmeta} as p17 ON ( customers.order_id = p17.post_id AND p17.meta_key = '_shipping_city' )
				LEFT JOIN {$wpdb->postmeta} as p18 ON ( customers.order_id = p18.post_id AND p18.meta_key = '_shipping_postcode' )
				LEFT JOIN {$wpdb->postmeta} as p19 ON ( customers.order_id = p19.post_id AND p19.meta_key = '_shipping_country' )
				LEFT JOIN {$wpdb->postmeta} as p20 ON ( customers.order_id = p20.post_id AND p20.meta_key = '_shipping_state' )
				LEFT JOIN {$wpdb->postmeta} as p21 ON ( customers.order_id = p21.post_id AND p21.meta_key = 'lead_source' )
				LEFT JOIN {$wpdb->postmeta} as p22 ON ( customers.order_id = p22.post_id AND p22.meta_key = 'industry' )
				LEFT JOIN {$wpdb->postmeta} as p23 ON ( customers.order_id = p23.post_id AND p23.meta_key = 'lead_status' )
                LEFT JOIN {$wpdb->usermeta} as p24 ON ( customers.user_id = p24.post_id AND p24.meta_key = '_billing_cpf' )
                LEFT JOIN {$wpdb->usermeta} as p25 ON ( customers.user_id = p25.post_id AND p25.meta_key = '_billing_cnpj')
                {$group_join}
				{$acf_join}
				WHERE 1=1
				{$filter}
				";
        echo '<textarea name="" id="" cols="30" rows="10">' . $query . '</textarea>';
        $result = $wpdb->query("SET SQL_BIG_SELECTS=1");
        $result = $wpdb->get_results($query, ARRAY_A);

        if ($result) {
            self::download_send_headers("customers_export_" . date("Y-m-d") . ".csv");
            echo self::array2csv($result);
            die();
        }
    }

    public static function download_send_headers($filename)
    {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }

    public static function array2csv(array &$array)
    {
        if (count($array) == 0) {
            return null;
        }
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }
}