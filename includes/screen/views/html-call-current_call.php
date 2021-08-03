<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form action="">
	<input type="hidden" name="call_duration_h" class="call_duration_h" class="call_time">
	<input type="hidden" name="call_duration_m" class="call_duration_m" class="call_time">
	<input type="hidden" name="call_duration_s" class="call_duration_s" class="call_time">
	<div class="hide-if-no-js" class="call-duration-timer">
		<a href="#" class="button tips" id="wpbar_start_timer" data-tip="<?php _e('Start', 'wc_crm'); ?>"></a>
		<a href="#" class="button tips" id="wpbar_stop_timer" data-tip="<?php _e('Stop', 'wc_crm'); ?>"><i class="ico_stop"></i></a>
		<a href="#" class="button tips" id="wpbar_pause_timer" data-tip="<?php _e('Pause/Resume', 'wc_crm'); ?>"><i class="ico_pause"></i></a>
		<a href="#" class="button tips" id="wpbar_reset_timer" data-tip="<?php _e('Reset', 'wc_crm'); ?>"><i class="ico_reset"></i></a>

	</div>
</form>