<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Email_Validation_Expiry')) :

    /**
     * Validation expiry email
     *
     * An email sent when a an agent assigned to a customer.
     *
     * @class       WC_CRM_Email_Validation_Expiry
     * @version     2.0.0
     * @package     WooCommerce/Classes/Emails
     * @author      WooThemes
     * @extends     WC_Email
     */
    class WC_CRM_Email_Validation_Expiry extends WC_Email
    {

        private $agent;
        private $post_id;

        /**
         * Constructor.
         */
        function __construct()
        {
            $this->id = 'wc_crm_validation_expiry';
            $this->title = __('Document expired', 'wc_crm');
            $this->description = __('Document expired email is sent to the agent of the customer when the document expiry date has been reached.', 'wc_crm');
            $this->heading = __('Documents Expired', 'wc_crm');
            $this->subject = __('A document of a customer has been expired', 'wc_crm');
            $this->template_html = 'emails/wc-crm-validation-expiry-notification.php';
            $this->template_plain = 'emails/plain/wc-crm-validation-expiry-notification.php';

            // Triggers for this email
            add_action('wc_crm_send_validation_expiry_notification', array($this, 'trigger'));

            // Call parent constructor
            parent::__construct();
        }

        /**
         * Trigger.
         *
         * @param $validation_data
         */
        function trigger($post_id)
        {
            $this->post_id = $post_id;
            $c_id = get_post_meta($post_id, 'validation_customer', true);
            $customer = new WC_CRM_Customer($c_id);
            $customer->init_general_fields();
            $this->object = $customer;

            if( isset($customer->customer_agent) && !empty($customer->customer_agent && ($agent = wc_crm_get_customer($customer->customer_agent))) ){
                $this->agent = get_user_by('id', $agent->user_id );
            }else{
                $this->agent = get_user_by('email', get_option('admin_email'));
            }

            $recipient = $this->agent->user_email ;

            $this->send($recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        /**
         * Get content html.
         *
         * @access public
         * @return string
         */
        function get_content_html()
        {
            $document = basename(get_post_meta($this->post_id, 'validation_file', true)['path']);
            $args = array(
                'customer' => $this->object,
                'agent' => $this->agent,
                'post_url' => get_edit_post_link($this->post_id, ''),
                'document' => $document,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
                'recipient' => !empty($this->agent->display_name) ? $this->agent->display_name : $this->agent->user_firstname . ' ' . $this->agent->user_lastname
            );

            return wc_get_template_html($this->template_html, $args, '', WC_CRM()->plugin_path() . '/templates/');
        }

        /**
         * Get content plain.
         *
         * @access public
         * @return string
         */
        function get_content_plain()
        {
            $document = basename(get_post_meta($this->post_id, 'validation_file', true)['path']);
            return wc_get_template_html($this->template_plain, array(
                'customer' => $this->object,
                'agent' => $this->agent,
                'post_url' => get_edit_post_link($this->post_id, ''),
                'document' => $document,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
                'recipient' => !empty($this->agent->display_name) ? $this->agent->display_name : $this->agent->user_firstname . ' ' . $this->agent->user_lastname
            ), '', WC_CRM()->plugin_path() . '/templates/');
        }


        /**
         * Get valid recipients.
         * @return string
         */
        function get_recipient(){
            return __('Agent', 'wc_crm');
        }
    }

endif;

return new WC_CRM_Email_Validation_Expiry();
