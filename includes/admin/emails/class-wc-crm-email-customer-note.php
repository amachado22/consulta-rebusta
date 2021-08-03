<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WC_CRM_Email_Customer_Note')) :

    /**
     * Customer notes emails
     *
     * An email sent when a new customer note is added.
     *
     * @class       WC_CRM_Email_Customer_Note
     * @version     2.0.0
     * @package     WooCommerce/Classes/Emails
     * @author      WooThemes
     * @extends     WC_Email
     */
    class WC_CRM_Email_Customer_Note extends WC_Email
    {
        private $note_data = array();
        private $customer;
        private $agent;
        private $recipient_role;

        /**
         * Constructor.
         */
        function __construct()
        {
            $this->id = 'wc_crm_customer_note';
            $this->title = __('Note notification', 'wc_crm');
            $this->description = __('Note notification emails are sent when a new note has been added to the customer.', 'wc_crm');
            $this->heading = __('New Note', 'wc_crm');
            $this->subject = __('You have a new note', 'wc_crm');
            $this->template_html = 'emails/wc-crm-customer-note-notification.php';
            $this->template_plain = 'emails/plain/wc-crm-customer-note-notification.php';

            // Triggers for this email
            add_action('wc_crm_send_customer_note_notification', array($this, 'trigger'));

            // Call parent constructor
            parent::__construct();
        }

        /**
         * Trigger.
         *
         * @param array $note_data
         */
        function trigger($note_data)
        {
            $this->note_data = $note_data;
            $this->object = get_comment($note_data['comment_id']);
            if ($this->note_data['note_type'] != 'private') {
                $this->customer = new WC_CRM_Customer($this->note_data['customer_id']);
            }
            if ($this->note_data['note_type'] == 'agent' || $this->note_data['note_type'] == 'all') {
                $this->customer->init_general_fields();
                $this->agent = get_userdata($this->customer->customer_agent);
            }

            if (!$this->is_enabled() || !$recipients = $this->get_recipient()) {
                return;
            }
            foreach ($recipients as $recipient) {
                if ($this->customer && $recipient == $this->customer->email) {
                    $this->recipient_role = 'customer';
                } elseif ($this->agent && $recipient == $this->agent->user_email) {
                    $this->recipient_role = 'agent';
                }
                $this->send($recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }
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
                'note' => $this->object,
                'customer' => $this->customer,
                'note_data' => $this->note_data,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this
            );

            if ($this->recipient_role == 'customer') {
                $args['recipient'] = $this->customer->first_name . ' ' . $this->customer->last_name;
            } elseif ($this->recipient_role == 'agent') {
                $args['recipient'] = $this->agent->first_name . ' ' . $this->agent->last_name;
            }
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
                'task' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this
            ), '', WC_CRM()->plugin_path() . '/templates/');
        }

        /**
         * Get valid recipients.
         * @return string
         */
        public function get_recipient()
        {
            if(!$this->note_data)
                return __('Customer/Agent', 'wc_crm');

            if( !count($this->note_data) )
                return $this->recipient;

            switch ($this->note_data['note_type']) {
                case 'private':
                    break;
                case 'customer':
                    $this->recipient = $this->customer->email;
                    break;
                case 'agent':
                    $this->recipient = $this->agent->user_email;
                    break;
                case 'all':
                    $this->recipient = $this->agent->user_email . ',' . $this->customer->email;
                    break;
            }
            $recipient = apply_filters('woocommerce_email_recipient_' . $this->id, $this->recipient, $this->object);
            $recipients = array_map('trim', explode(',', $recipient));
            $recipients = array_filter($recipients, 'is_email');
            return $recipients;
        }
    }

endif;

return new WC_CRM_Email_Customer_Note();
