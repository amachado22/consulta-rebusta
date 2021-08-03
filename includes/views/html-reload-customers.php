<?php
/**
 * Admin View: Page - About
 *
 * @var string $view
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $wpdb;
$sql = "SELECT pe.meta_value as user_email FROM {$wpdb->postmeta} pc
		LEFT JOIN {$wpdb->postmeta} pe ON ( pe.post_id = pc.post_id ) 
		WHERE pc.meta_key = '_customer_user' AND pc.meta_value = 0 AND pe.meta_key = '_billing_email' AND pe.meta_value != '' AND pe.meta_value != 'null'
		GROUP BY pe.meta_value
";
$total_u = 0;
$total_g = 0;

$users   = $wpdb->get_results("SELECT ID FROM {$wpdb->users}");
$gusets  = $wpdb->get_results($sql);
if( !empty($gusets)){
	$temp_g = array();
	foreach ($gusets as $key => $value) {
		$temp_g[] = $value->user_email;
	}
	$gusets  = $temp_g;
	asort($gusets);
	$total_g = count($gusets);
}
if( !empty($users)){
	$temp_u = array();
	foreach ($users as $key => $value) {
		$temp_u[] = $value->ID;
	}
	$users   = $temp_u;
	asort($users);
	$total_u = count($users);
}
$text_goback = (!empty($_GET['goback']))
		 ? sprintf(__('To go back to the previous page, <a href="%s">click here</a>.', 'wc_crm'), 'javascript:history.go(-1)')
		 : '';

$text_failures = sprintf(__('All done! %1$s customer(s) were successfully loaded in %2$s seconds and there were %3$s failure(s). To try reloading the failed customers again, <a href="%4$s">click here</a>. %5$s', 'wc_crm'), "' + rt_successes + '", "' + rt_totaltime + '", "' + rt_errors + '", esc_url(wp_nonce_url(admin_url('admin.php?page='.WC_CRM_TOKEN.'-about&'.WC_CRM_TOKEN.'-instal=true&goback=1'), 'wc_crm') . '&ids=') . "' + rt_failedlist + '", $text_goback);
$text_nofailures = sprintf(__('All done! %1$s customer(s) were successfully loaded in %2$s seconds and there were 0 failures. %3$s', 'wc_crm'), "' + rt_successes + '", "' + rt_totaltime + '", $text_goback);
?>
<h2><?php _e("Reload customers", 'wc_crm'); ?></h2>
<div class="changelog reload-customers">
	<div class="wc-feature feature-section last-feature-section">
	<p><?php _e("Please be patient while the customers are reloaded. You will be notified via this page when the reloading is completed.", 'wc_crm'); ?></p>
	<noscript><p><em><?php _e("You must enable Javascript in order to proceed!", 'wc_crm'); ?></em></p></noscript>
	<p>
		<input type="submit" value="Start" id="force_reload_customers" name="force_reload_customers" class="button-primary hide-if-no-js">
	</p>

	<div id="reload-bar">
		<div id="reload-bar-percent"></div>
	</div>
	<h3 class="title"><?php _e("Process Information", 'wc_crm'); ?></h3>
	<p>
		<?php _e("Total users", 'wc_crm'); ?>: <?php echo $total_u; ?><br>
		<?php _e("Total guests", 'wc_crm'); ?>: <?php echo $total_g; ?><br>
		<?php _e("Success", 'wc_crm'); ?>: <span id="reload-debug-successcount">0</span><br>
		<?php _e("Failure", 'wc_crm'); ?>: <span id="reload-debug-failurecount">0</span>
	</p>
	<ol id="reload-debuglist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
		// <![CDATA[
		jQuery(document).ready(function($){
			var i;
			var users   = [<?php echo implode(',', $users); ?>];
			var u_total = <?php echo $total_u; ?>;

			var gusets  = [<?php echo $total_g > 0 ? "'" . implode("','", $gusets) . "'" : ''; ?>];
			var g_total = <?php echo $total_g; ?>;

			var rt_total = u_total + g_total;
			var rt_count = 1;
			var rt_percent = 0;
			var rt_successes = 0;
			var rt_errors = 0;
			var rt_failedlist = '';
			var rt_resulttext = '';
			var rt_timestart = new Date().getTime();
			var rt_timeend = 0;
			var rt_totaltime = 0;
			var rt_continue = true;

			$("#reload-bar").progressbar();
			$("#reload-bar-percent").html("0%");
			$("#force_reload_customers").click(function(event) {
				$("#reload-debuglist li").remove();
				$('#reload-bar').addClass('active');
				ReloadCustomers(users.shift());
			});
			// Stop button
			$("#reload-stop").click(function() {
				rt_continue = false;
				$('#reload-stop').val("<?php echo __('Stopping...', 'wc_crm'); ?>");
			});
			// Called after each resize. Updates debug information and the progress bar.
			function UpdateStatus(id, success, response) {
				$("#reload-bar").progressbar("value", (rt_count / rt_total) * 100);
				$("#reload-bar-percent").html(Math.round((rt_count / rt_total) * 1000) / 10 + "%");
				rt_count = rt_count + 1;

				if (success) {
					rt_successes = rt_successes + 1;
					$("#reload-debug-successcount").html(rt_successes);
					$("#reload-debuglist").append("<li>" + response.success + "</li>");
				}
				else {
					rt_errors = rt_errors + 1;
					rt_failedlist = rt_failedlist + ',' + id;
					$("#reload-debug-failurecount").html(rt_errors);
					$("#reload-debuglist").append("<li>" + response.error + "</li>");
				}
			}
			// Called when all images have been processed. Shows the results and cleans up.
			function FinishUp() {
				rt_timeend = new Date().getTime();
				rt_totaltime = Math.round((rt_timeend - rt_timestart) / 1000);

				$('#reload-stop').hide();

				if (rt_errors > 0) {
					rt_resulttext = '<?php echo $text_failures; ?>';
				} else {
					rt_resulttext = '<?php echo $text_nofailures; ?>';
				}

				$("#message").html("<p><strong>" + rt_resulttext + "</strong></p>");
				$('#reload-bar').removeClass('active');
				$("#message").show();
			}

			// Reload a specified user via AJAX
			function ReloadCustomers(id) {
				$.ajax({
					type: 'POST',
					cache: false,
					url: ajaxurl,
					data: { action: "wc_crm_reload_customer", id: id },
					success: function(response) {
						if (response.success) {
							UpdateStatus(id, true, response);
						} else {
							UpdateStatus(id, false, response);
						}

						if (users.length && rt_continue) {
							ReloadCustomers(users.shift());
						}else if (gusets.length && rt_continue) {
							ReloadGuests(gusets.shift());
						}  else {
							FinishUp();
						}
					},
					error: function(response) {
						UpdateStatus(id, false, response);

						if (users.length && rt_continue) {
							ReloadCustomers(users.shift());
						} else if (gusets.length && rt_continue) {
							ReloadGuests(gusets.shift());
						} else {
							FinishUp();
						}
					}
				});
			}		
			// Reload a specified user via AJAX
			function ReloadGuests(email) {
				$.ajax({
					type: 'POST',
					cache: false,
					url: ajaxurl,
					data: { action: "wc_crm_reload_guest", email: email },
					success: function(response) {
						if (response.success) {
							UpdateStatus(email, true, response);
						} else {
							UpdateStatus(email, false, response);
						}

						if (gusets.length && rt_continue) {
							ReloadGuests(gusets.shift());
						}  else {
							FinishUp();
						}
					},
					error: function(response) {
						UpdateStatus(email, false, response);

						if (gusets.length && rt_continue) {
							ReloadGuests(gusets.shift());
						} else {
							FinishUp();
						}
					}
				});
			}			
		});
		// ]]>
	</script>
	
	</div>
</div>