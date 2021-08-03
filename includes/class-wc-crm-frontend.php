<?php

class WC_CRM_Frontend
{

    public function __construct()
    {
        add_filter("woocommerce_billing_fields", array($this, 'wc_crm_dob_field'));
        add_action("woocommerce_checkout_order_processed", array($this, 'wc_crm_dob_field_save'), 10, 3);
    }

    public function wc_crm_dob_field($fields)
    {
        if(get_option('wc_crm_dob_field', false) !== "yes") return $fields;

        if(!get_current_user_id()) return $fields;

        $dob = get_user_meta(get_current_user_id(), "date_of_birth", true);
        $fields['date_of_birth'] = array(
            'type' => 'text',
            'id' => 'date_of_birth',
            'label' => __('DOB', 'wc_crm'),
            'placeholder' => __('Date of birth', 'wc_crm'),
            'class' => array('form-row-wide'),
            'default' => $dob
        );

        return $fields;
    }

    public function wc_crm_dob_field_save($order_id, $posted_data, $order)
    {
        if(isset($posted_data['date_of_birth']) && !empty($posted_data['date_of_birth'])){
            update_user_meta(get_current_user_id(), 'date_of_birth', $posted_data['date_of_birth']);
        }
    }
}
new WC_CRM_Frontend();