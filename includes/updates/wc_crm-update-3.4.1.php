<?php
/**
 * Update WC_CRM to 3.4.1
 *
 * @author     Adailton Machado
 * @category    Admin
 * @package     WC_CRM/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$super_admins = get_users(array('role' => 'administrator'));
foreach ($super_admins as $admin){
    $current_opt = get_user_meta($admin->ID,'managetoplevel_page_wc_crmcolumnshidden', true);
    $current_opt[] = 'customer_agent';
    update_user_meta($admin->ID, 'managetoplevel_page_wc_crmcolumnshidden', $current_opt);
}