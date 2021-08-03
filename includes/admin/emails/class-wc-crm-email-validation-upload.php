<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Email_Validation_Upload')) :

    /**
     * Agent assignment email
     *
     * An email sent when a an agent assigned to a customer.
     *
     * @class       WC_CRM_Email_Validation_Upload
     * @version     2.0.0
     * @package     WooCommerce/Classes/Emails
     * @author      WooThemes
     * @extends     WC_Email
     */
    class WC_CRM_Email_Validation_Upload extends WC_Email
    {
        private $agent;
        private $post_url;

        /**
         * Constructor.
         */
        function __construct()
        {
            $this->id = 'wc_crm_validation_upload';
            $this->title = __('Document notification', 'wc_crm');
            $this->description = __('Document notification is sent to the agent of the customer when customer has uploaded documents to their profile.', 'wc_crm');
            $this->heading = __('Documents Received', 'wc_crm');
            $this->subject = __('A document of a customer has been uploaded', 'wc_crm');
            $this->template_html = 'emails/wc-crm-validation-upload-notification.php';
            $this->template_plain = 'emails/plain/wc-crm-validation-upload-notification.php';

            // Triggers for this email
            add_action('wc_crm_send_validation_upload_notification', array($this, 'trigger'));

            // Call parent constructor
            parent::__construct();
        }

        /**
         * Trigger.
         *
         * @param $validation_data
         */
        function trigger($validation_data)
        {
            $c_id = 0;
            if (isset($validation_data['customer']->c_id)) {
                $c_id = $validation_data['customer']->c_id;
            }
            $customer = new WC_CRM_Customer($c_id);
            $customer->init_general_fields();
            $this->object = $customer;
            $this->agent = get_userdata( $customer->general_fields['customer_agent']['value'] );
            $this->post_url = $validation_data['post_url'];
            $recipient = $this->agent->user_email;

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
            $args = array(
                'customer' => $this->object,
                'agent' => $this->agent,
                'post_url' => $this->post_url,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
                'recipient' => $this->agent->first_name . ' ' . $this->agent->last_name
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
            return wc_get_template_html($this->template_plain, array(
                'customer' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
                'recipient' => $this->agent->first_name . ' ' . $this->agent->last_name
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

return new WC_CRM_Email_Validation_Upload();
