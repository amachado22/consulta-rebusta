<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_CRM_Email_Task_Notification' ) ) :

/**
 * Customer Processing Order Email.
 *
 * An email sent to the customer when a new order is received/paid for.
 *
 * @class       WC_CRM_Email_Task_Notification
 * @version     2.0.0
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
 * @extends     WC_Email
 */
class WC_CRM_Email_Task_Notification extends WC_Email {

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->id               = 'wc_crm_task_notification';
		$this->title            = __( 'Task notification', 'wc_crm' );
		$this->description      = __( 'Task notification emails are sent to the task owner when a new task is created.', 'wc_crm' );
		$this->heading          = __( 'Task Assignment', 'wc_crm' );
		$this->subject          = __( 'You have a new task {task_subject}', 'wc_crm' );
		$this->template_html    = 'emails/wc-crm-task-notification.php';
		$this->template_plain   = 'emails/plain/wc-crm-task-notification.php';

		// Triggers for this email
		add_action( 'wc_crm_send_task_notification', array( $this, 'trigger' ) );

		// Call parent constructor
		parent::__construct();
	}

	/**
	 * Trigger.
	 *
	 * @param int $order_id
	 */
	function trigger( $task_id ) {

		if ( $task_id ) {
			$this->object       = new WC_CRM_Task($task_id);
			$task_owner         = get_userdata($this->object->task_owner);

			$this->recipient    = $task_owner->user_email;

			$this->find['task_subject']    = '{task_subject}';
			$this->replace['task_subject'] = $this->object->subject;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'task'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		), '', WC_CRM()->plugin_path() . '/templates/' );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {		
		return wc_get_template_html( $this->template_plain, array(
			'task'          => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		), '', WC_CRM()->plugin_path() . '/templates/' );
	}

	/**
	 * Get valid recipients.
	 * @return string
	 */
	public function get_recipient() {
		if( isset($_GET['page']) && $_GET['page'] == 'wc-settings'){
			return __('Task owner', 'wc_crm');
		}
		$recipient  = apply_filters( 'woocommerce_email_recipient_' . $this->id, $this->recipient, $this->object );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );
		return implode( ', ', $recipients );
	}
}

endif;

return new WC_CRM_Email_Task_Notification();
