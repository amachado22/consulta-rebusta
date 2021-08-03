<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Email_Agent_Assignment')) :

    /**
     * Agent assignment email
     *
     * An email sent when a an agent assigned to a customer.
     *
     * @class       WC_CRM_Email_Agent_Assignment
     * @version     2.0.0
     * @package     WooCommerce/Classes/Emails
     * @author      WooThemes
     * @extends     WC_Email
     */
    class WC_CRM_Email_Agent_Assignment extends WC_Email
    {
        private $agent;

        /**
         * Constructor.
         */
        function __construct()
        {
            $this->id = 'wc_crm_agent_assignment';
            $this->title = __('Agent notification', 'wc_crm');
            $this->description = __('Agent notification emails are sent to the agent when a customer has been assigned to them.', 'wc_crm');
            $this->heading = __('Agent Assignment', 'wc_crm');
            $this->subject = __('You have been assigned a customer', 'wc_crm');
            $this->template_html = 'emails/wc-crm-agent-assignment-notification.php';
            $this->template_plain = 'emails/plain/wc-crm-agent-assignment-notification.php';

            // Triggers for this email
            add_action('wc_crm_send_agent_assignment_notification', array($this, 'trigger'));

            // Call parent constructor
            parent::__construct();
        }

        /**
         * Trigger.
         *
         * @param $customer_data
         */
        function trigger($customer_data)
        {
            $customer = $customer_data['customer'];
            $customer->init_general_fields();
            $this->object = $customer;
            $this->agent = new WC_CRM_Customer( $customer->general_fields['customer_agent']['value'] );
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

return new WC_CRM_Email_Agent_Assignment();
