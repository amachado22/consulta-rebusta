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

if ( ! class_exists( 'WC_Crm_Settings_Indication' ) ) :

/**
 * WC_Crm_Settings_Indication
 */
class WC_Crm_Settings_Indication extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'indication';
		$this->label = __( 'Indicators', 'woocommerce' );

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
		return apply_filters( 'woocommerce_customer_relationship_newsletter_settings_fields', array(
			array('name' => __( 'Indicators', 'wc_crm' ), 'type' => 'title', 'desc' => '', 'id' => 'customer_relationship_indication'),
				array(
					'name' => __( 'Spending ', 'wc_crm' ),
					'desc_tip' => __( 'Enter value of sales when spending indicator appears. Only rders with Completed and Processing status will be taken into account.', 'wc_crm' ),
					'id' => 'wc_crm_total_spent_indication',
					'default' => '1000',
					'type' => 'number',
					'css' => 'width: 100px;',
				),
				array('type' => 'sectionend', 'id' => 'customer_relationship_indication'),
		) ); // End general settings

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

return new WC_Crm_Settings_Indication();
