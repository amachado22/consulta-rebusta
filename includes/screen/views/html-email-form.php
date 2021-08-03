<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="wc-crm-page" class="wrap">
	<h2 class="email-heading"><?php _e( 'Send Email', 'wc_crm' ); ?></h2>
	<p><?php _e( 'Compose a new email to the customer. You can use the default name and email address or alternatively, enter your own custom name and email address below. Shortcodes can be used using the shortcode button within the text editor.', 'wc_crm' ); ?></p>
	<form id="wc_crm_send_customer_email" method="post" action="<?php echo admin_url('admin.php?page='.WC_CRM_TOKEN); ?>">
		<table class="form-table">
			<tbody>
				<tr class="form-field">
					<th scope="row">
						<label for="form_email"><?php _e( 'From Name', 'wc_crm' ); ?></label>
					</th>
					<td>
						<input type="text" name="from_name" value="" id="from_name" placeholder="<?php echo sprintf($mailer->get_from_name() ); ?>" autocomplete="off"/>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row">
						<label for="form_email"><?php _e( 'From Email Address', 'wc_crm' ); ?></label>
					</th>
					<td>
						<input type="text" name="from_email" value="" id="from_email" placeholder="<?php echo sprintf($mailer->get_from_address() ); ?>" autocomplete="off"/>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row">
						<label for="recipients"><?php _e( 'Recipients', 'wc_crm' ); ?></label>
					</th>
					<td>
						<input type="text" name="recipients" value="<?php echo implode( ',', $recipients ); ?>" id="recipients" autocomplete="off"/>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row">
						<label for="subject"><?php _e( 'Subject', 'wc_crm' ); ?></label>
					</th>
					<td>
						<input type="text" name="subject" value="" id="subject" autocomplete="on"/>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row">
						<label for="subject"><?php _e( 'Body', 'wc_crm' ); ?></label>
					</th>
					<td>
						<?php wp_editor( '', 'emaileditor' ); ?>
						<div id="emaileditor">
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="wc_crm_customer_action" value="sent_email">
		<button name="send" type="submit" class="button button-primary button-large" id="send" accesskey="p" style="margin-top: 10px;">
		<?php _e( 'Send email', 'wc_crm' ); ?>
		</button>
	</form>
</div>