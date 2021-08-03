<?php
/**
 * Admin View: Page - About
 *
 * @var string $view
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<br>
<br>
<hr>
<div class="feature-section one-col">
	<div class="col">
		<h2><?php _e("Introducing Document Validation", 'wc_crm'); ?></h2>
		<p><?php _e( 'We are excited to share our latest improvements to our feature-rich Customer Relationship Manager. Customers can now upload their documents for validation by shop administrators. You can control what document type is accepted, set the status and even add an expiry date to ensure and maintain validity. The latest update introduces the ability for customers to edit their fields from the My Account > Account Details page and to pin a customer for easy tracking and customer management.', 'wc_crm' ); ?></p>
	</div>
</div>
<div class="feature-section two-col">
	<div class="col">
		<h3><?php _e( 'Document Validation', 'wc_crm' ); ?></h3>
		<p><?php _e( 'Validating customer documents such as drivers license or passport scans can be cumbersome. With our new document validator module, customers can upload their document and the shop administrator can then set a status from the following:', 'wc_crm' ); ?></p>
		<ol>
			<li><?php _e( 'Confirmed', 'wc_crm' ); ?></li>
			<li><?php _e( 'Awaiting Confirmation', 'wc_crm' ); ?></li>
			<li><?php _e( 'Cancelled', 'wc_crm' ); ?></li>
		</ol>
		<p><?php _e( 'You can also configure the files accepted by going to Customers > Settings > Documents. Enter upload instructions in the instructions field to help users with their experience.', 'wc_crm' ); ?></p>
		</ol>
	</div>
	<div class="col">
		<h3><?php _e( 'Customer Fields', 'wc_crm' ); ?></h3>
		<p><?php _e( 'Managing customers can sometimes be done by the customer themselves. For example, updating their records or adding additional information to their customer account. Our new customer fields feature allows you to enable or disable fields from the customers profile, to be shown to the customer on the frontend My Account page under the Account Details tab.', 'wc_crm' ); ?></p>
	</div>
</div>
<div class="feature-section two-col">
	<div class="col">
		<h3><?php _e( 'Pinned Customers', 'wc_crm' ); ?></h3>
		<p><?php _e( 'Customers come and go and sometimes their importance varies between time to time. With the ability to pin customers, you can now quickly pin a customer and then filter all pinned customers instantly from the customers page. This can be done simply by clicking the pin icon.', 'wc_crm' ); ?></p>
	</div>
	<div class="col">
		<h3><?php _e( 'Filters', 'wc_crm' ); ?></h3>
		<p><?php _e( 'The integration of Advanced Custom Fields (ACF) and our plugin has allowed many customers to take advantage of the powers of WordPress and WooCommerce, serving a better experience for their business and customers. To compliment this, our plugin now exports custom fields when exporting customers. In addition to this, the custom fields can now be filtered using the filters enabled under Customer > Settings > General > Filters.', 'wc_crm' ); ?></p>
	</div>
</div>