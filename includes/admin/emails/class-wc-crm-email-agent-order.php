<?php
/**
 * Class WC_CRM_Email_Agent_Order file
 *
 * @package Woocommerce-customer-relationship\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_CRM_Email_Agent_Order' ) ) :

	/**
	 * New Order Email.
	 *
	 * An email sent to the agent when a new order is received/paid for.
	 *
	 * @class       WC_CRM_Email_Agent_Order
	 * @version     2.0.0
	 * @package     Woocommerce-customer-relationship/Classes/Emails
     * @author     Adailton Machado
	 * @extends     WC_Email
	 */
	class WC_CRM_Email_Agent_Order extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'agent_order';
			$this->title          = __( 'Agent order notification', 'wc-crm' );
			$this->description    = __( 'New order emails are sent to the agent when a new order is received.', 'wc-crm' );
            $this->heading = __('New customer order', 'wc_crm');
            $this->template_base = WC_CRM()->plugin_path() . '/templates/';
			$this->template_html  = 'emails/wc-crm-agent-order-notification.php';
			$this->template_plain = 'emails/plain/wc-crm-agent-order-notification.php';

			// Triggers for this email.
            add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
            add_action( 'woocommerce_order_status_pending_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
            add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
            add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
            add_action( 'woocommerce_order_status_failed_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
            add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( '[{site_title}] New customer order ({order_number}) - {order_date}', 'wc-crm' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}

            $customer = $this->object->get_user();
            if( !$customer ) return;

            $customer = new WC_CRM_Customer($customer);
            $customer->init_general_fields();

            $agent = $customer->general_fields['customer_agent']['value'];
            if( !$agent || empty($agent)) return;

            $this->recipient = (new WC_CRM_Customer($agent))->user_email;

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get content html.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
                array(
					'order'         => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => false,
					'email'         => $this,
				),
                '',
                $this->template_base
			);
		}

		/**
		 * Get content plain.
		 *
		 * @access public
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
                array(
					'order'         => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => true,
					'email'         => $this,
				),
                '',
                $this->template_base
			);
		}

		public function get_recipient()
        {
            if(!$this->recipient)
                return __('Agent', 'wc_crm');

            return parent::get_recipient();
        }
    }

endif;

return new WC_CRM_Email_Agent_Order();
