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

if ( ! class_exists( 'WC_Crm_Settings_Validation' ) ) :

/**
 * WC_Crm_Settings_Indication
 */
class WC_Crm_Settings_Validation extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'validation';
		$this->label = __( 'Documents', 'wc_crm' );

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
		return apply_filters( 'woocommerce_customer_relationship_validation_settings_fields', array(
			array('name' => __( 'Documents', 'wc_crm' ), 'type' => 'title', 'desc' => '', 'id' => 'customer_relationship_validation'),
            array(
                'name' => __('Enable documents', 'wc_crm'),
                'desc' => __('Enable documents uploading'),
                'desc_tip' => __('Check this box to allow customers to upload documents from their My Account page.'),
                'id' => 'wc_crm_enable_validation',
                'type' => 'checkbox'
            ),
            array(
                'name' => __( 'File Types', 'wc_crm' ),
                'desc_tip' => __( 'Choose the accepted file types that users upload as their documents.', 'wc_crm' ),
                'id' => 'wc_crm_file_types_validation',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'options' => WC_CRM_VALIDATION::get_validation_types(),
                'autoload' => true
            ),
            array(
                'name' => __( 'Upload Instructions', 'wc_crm' ),
                'desc_tip' => __( 'Display instructions to the customer when uploading documents to their profile.', 'wc_crm' ),
                'id' => 'wc_crm_instruction_validation',
                'placeholder' => __( 'Please upload your documents below.', 'wc_crm' ),
                'type' => 'textarea',
                'css' => 'height:100px;',
                'class' => '',
            ),
            array('type' => 'sectionend', 'id' => 'customer_relationship_validation')
		) );

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

return new WC_Crm_Settings_Validation();
