<?php
/**
 * WooCommerce CRM Woo Settings
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Crm_Settings_Woocommerce' ) ) :

/**
 * WC_Crm_Settings_Indication
 */
class WC_Crm_Settings_Woocommerce extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'wc_crm_woocommerce';
		$this->label = __( 'Customer Fields', 'wc_crm' );

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

        $cx = new WC_CRM_Customer();
        $cx->init_general_fields();

        $except = ["first_name", "last_name", "user_email", "customer_agent", "customer_categories", "customer_brands"];
        $my_account_fields = array();

        foreach ( $cx->general_fields as $key => $field ){

            if( in_array($key, $except) ) continue;

            $my_account_fields[$key] = $field['label'];
        }

        $fields = array(
            array('name' => __( 'My Account Fields', 'wc_crm' ), 'type' => 'title', 'desc' => '', 'id' => 'customer_relationship_woocommerce'),
            array(
                'title' => __('Enabled Fields', 'wc_crm'),
                'desc_tip' => 'Choose which fields you would like to display on the Account details tab under the My Account page.',
                'name' => __('Fields', 'wc_crm'),
                'id' => 'wc_crm_my_account_fields',
                'class' => 'wc-enhanced-select',
                'type' => 'multiselect',
                'options' => $my_account_fields
            ),
            array('type' => 'sectionend', 'id' => 'customer_relationship_woocommerce'),
            array(
                'title' => __('Checkout Fields', 'wc_crm'),
                'type' => 'title',
                'desc' => __('The following options affects the WooCommerce checkout fields.', 'wc_crm'),
                'id' => 'customer_relationship_checkout'
            ),
            array(
                'title' => __('Date of Birth', 'wc_crm'),
                'desc' => __('Enable this field on the WooCommerce checkout page.'),
                'desc_tip' => __('Note: this will not appear for guest customers.'),
                'name' => __('dob_field', 'wc_crm'),
                'id' => 'wc_crm_dob_field',
                'type' => 'checkbox'
            ),
            array('type' => 'sectionend', 'id' => 'customer_relationship_checkout'),
        );
//
        return apply_filters('woocommerce_customer_relationship_woocommerce_settings_fields', $fields);

	}

	/**
	 * Save settings
	 */
	public function save() {
		$settings = $this->get_settings();
		WC_CRM_Admin_Settings::save_fields( $settings );
	}

}

endif;

return new WC_Crm_Settings_Woocommerce();
