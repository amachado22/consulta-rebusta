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
<h2><?php _e("What's New", 'wc_crm'); ?></h2>
<div class="changelog">
		<div class="changelog about-integrations">
			<div class="wc-feature feature-section last-feature-section col three-col">
				<div>
					<h4><?php _e( 'Speed Optimisation', 'woocommerce' ); ?></h4>
					<p><?php _e( "To cater for bigger databases, we've optimised the performance of the plugin when handling larger records and number of customers..", 'woocommerce' ); ?></p>
				</div>
				<div class="last-feature">
					<h4><?php _e( 'Refactoring', 'woocommerce' ); ?></h4>
					<p><?php _e( "We have refactored the entire plugin to prepare for exciting changes coming towards the end of this year.", 'woocommerce' ); ?></p>
				</div>
			</div>
		</div>
	</div>