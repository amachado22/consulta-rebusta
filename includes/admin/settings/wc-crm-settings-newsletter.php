<?php
/**
 * WooCommerce General Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Crm_Settings_Newsletter' ) ) :

/**
 * WC_Crm_Settings_Newsletter
 */
class WC_Crm_Settings_Newsletter extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'newsletter';
		$this->label = __( 'Newsletter', 'woocommerce' );

		add_filter( 'wc_crm_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wc_crm_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wc_crm_settings_save_' . $this->id, array( $this, 'save' ) );

	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		global $woocommerce;
		$api_key = get_option( 'wc_crm_mailchimp_api_key', '' );
        $mailchimp_lists = array();
        $mailchimp_list = get_option( 'wc_crm_mailchimp_list', '' );

        if ( !empty($api_key) ) {
            $mailchimp_lists = woocommerce_crm_get_mailchimp_lists( $api_key );
        }

        if( !is_array($mailchimp_lists))
            $mailchimp_lists = array();

		return apply_filters( 'woocommerce_customer_relationship_newsletter_settings_fields', array(

			array('name' => __( 'MailChimp Integration', 'wc_crm' ), 'type' => 'title', 'desc' => '', 'id' => 'customer_relationship_mailchimp'),

				array(
					'name' => __( 'Integrate with MailChimp', 'wc_crm' ),
					'desc' => __( 'Specify whether to integrate Customer Relationship Manager with MailChimp to see which customers signed to the newsletter.', 'wc_crm' ),
					'id' => 'wc_crm_mailchimp',
					'css' => '',
					'std' => 'yes',
					'type' => 'checkbox',
					'default' => 'no'
				),

				array(
					'name' => __( 'MailChimp API Key', 'wc_crm' ),
					'desc' => __( 'You can obtain your API key by <a href="https://us2.admin.mailchimp.com/account/api/">logging in to your MailChimp account</a>.', 'wc_crm' ),
					'tip' => '',
					'id' => 'wc_crm_mailchimp_api_key',
					'css' => '',
					'std' => '',
					'type' => 'text',
					'default' => $api_key
				),

				array(
					'name' => __( 'MailChimp List', 'wc_crm' ),
					'desc' => __( 'Choose a list customers can subscribe to (you must save your API key first).', 'wc_crm' ),
					'tip' => '',
					'id' => 'wc_crm_mailchimp_list',
					'css' => '',
					'std' => '',
					'type' => 'select',
					'options' => $mailchimp_lists,
					'default' => $mailchimp_list
				),

				array('type' => 'sectionend', 'id' => 'customer_relationship_mailchimp'),

		) ); // End general settings

	}

	/**
	 * Save settings
	 */
	public function save() {
		delete_transient('woocommerce_crm_mailchimp_members');
		delete_transient('woocommerce_crm_mailchimp_lists');
		$settings = $this->get_settings();

		WC_CRM_Admin_Settings::save_fields( $settings );
	}

}

endif;

return new WC_Crm_Settings_Newsletter();
