<?php
/**
 * Plugin Name: Consulta Rebusta Master
 * Description: Permite uma gestão geral dos clientes e suas contas relacionadas, bem como a gestão da comunicação entre a sua loja e eles.
 * Version: 3.0
 * Author: Adailton Machado
 * Text Domain: wc_crm
 * Domain Path: /lang/
 * License: GNU General Public License v3.0
 *
 * @author      Adailton Machado
 * @category    Plugin
 * 
 * WC requires at least: 3.5
 * WC tested up to: 3.5.4
 */

if ( !defined( 'ABSPATH' ) ) exit; // Saia se acessado diretamente

if (function_exists('is_multisite') && is_multisite()) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) )
        return;
}else{
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
        return; // Verifique se WooCommerce está ativo   
}

// Carregar arquivos de classe de plugin
require_once( 'includes/class-wc-crm.php' );

require 'updater/updater.php';
global $aebaseapi;
$aebaseapi->add_product(__FILE__);

/**
 * Retorna a instância principal de WC_CRM para evitar a necessidade de usar globais.
 *
 * @since    2.7.0
 * @return object WC_CRM
 */
global $wpdb;
$wpdb->wc_crm_customermeta = $wpdb->prefix . "wc_crm_customermeta";

/**
 * @return WC_CRM $instance;
 */
function WC_CRM () {
	$instance = WC_CRM::instance( __FILE__, '3.5.2' );

	/*if ( is_null( $instance->settings ) ) {
		$instance->settings = WC_CRM_Settings::instance( $instance );
	}*/

	return $instance;
}

WC_CRM();