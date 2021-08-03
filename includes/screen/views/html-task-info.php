<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $action;
$task = new WC_CRM_Task($post->ID);
$r_opt = $task->repeat_options;
?>
<div class="task_data_column_container <?php echo $action; ?>">
	<input type="hidden" name="task_owner" id="task_owner" value="<?php echo $task->task_owner; ?>">
	
		<?php				
		foreach ( $fields_info as $key => $field) {
			$field['id'] = $key;
			if(!isset($field['type']))
				$field['type'] = 'text';

			if( $key == 'post_title' && $action != 'edit' )
				$field['value'] = '';

			if( function_exists('woocommerce_wp_'.$field['type'].'_input')){
				$f = 'woocommerce_wp_'.$field['type'].'_input';
				$f($field);
			}elseif( function_exists('woocommerce_wp_'.$field['type'])){
				$f = 'woocommerce_wp_'.$field['type'];
				$f($field);
			}else{
				woocommerce_wp_text_input( $field );
			}

		}		
		?>
		<div class="show_repeat_options">
			<div class="show_repeat_daily_options show_repeat_type_options">
					<ul class="wc-radios">
						<li><label><input type="radio" class="select short" value="1" name="repeat_daily_type" <?php checked('1', $r_opt['daily']['type']); ?> >
							<?php _e('Every day', 'wc_crm'); ?></label>
						</li>
						<li>
							<label>
								<input type="radio" class="select short" value="2" name="repeat_daily_type" <?php checked('2', $r_opt['daily']['type']); ?>> 
							<?php
								$input = '<input type="number" style="width:60px;" min="0" value="'.$r_opt['daily']['days'].'" id="repeat_daily_days" name="repeat_daily_days" >';
								printf( __("Repeat for every %s days", 'wc_crm'), $input);
							?>
							</label>
						</li>
					</ul>
			</div>
			<div class="show_repeat_weekly_options show_repeat_type_options">
					<ul class="wc-radios">
						<li>
							<?php
							$input = '<input type="number" min="0" style="width:60px;" value="'.$r_opt['weekly']['weeks'].'" id="repeat_weekly_weeks" name="repeat_weekly_weeks" />';
							printf( __("Repeat for every %s weeks", 'wc_crm'), $input); ?>
						</li>
						<li>
							<ul class="wc-radios">
							<?php
							$st = new DateTime('Saturday');
							for ($i=0; $i < 7; $i++) { 
								?>
								<li>
								<label>
									<input type="checkbox" class="checkbox short" value="<?php echo $i; ?>" name="repeat_weekly_weekdays[]" <?php checked(true, in_array($i, $r_opt['weekly']['weekdays']), true); ?>>
									<?php
									echo $st->modify('+1 day')->format('l'); ?>
								</label>
								</li>
								<?php
							}
							?>
							</ul>
						</li>
					</ul>
			</div>
			<div class="show_repeat_monthly_options show_repeat_type_options">
					<ul class="wc-radios">
						<li><label><input type="radio" class="select short" value="1" name="repeat_monthly_type" <?php checked('1', $r_opt['monthly']['type']); ?>>
							<?php
							$select = '<select name="repeat_monthly_day" id="repeat_monthly_day">';
								foreach (range(1, 31) as $nday) {
									$select .= '<option value="'.$nday.'" '.selected($nday, $r_opt['monthly']['day'], false).' >'.$nday.'</option>';
								}
							$select .= '</select>';
							
							$input = '<span><input type="number" min="0" style="width:60px;" value="'.$r_opt['monthly']['noMonths'].'" id="repeat_monthly_noMonths1" name="repeat_monthly_noMonths1" /></span>';
							printf( __("On day %s Of every %s months", 'wc_crm'), $select, $input); ?></label>
						</li>
						<li><label><input type="radio" class="select short" value="2" name="repeat_monthly_type" <?php checked('2', $r_opt['monthly']['type']); ?>> 
							<?php
							$select1 = '<select name="repeat_monthly_weeksequence" id="repeat_monthly_weeksequence">';
								$weeksequence = array(
									1 => __('First', 'wc_crm'),
									__('Second', 'wc_crm'),
									__('Third', 'wc_crm'),
									__('Fourth', 'wc_crm'),
									__('Last', 'wc_crm'),
								);
								foreach ($weeksequence as $k => $ws) {
									$select1 .= '<option value="'.$k.'" '.selected($k, $r_opt['monthly']['weeksequence'], false).'>'.$ws.'</option>';
								}
							$select1 .= '</select>';
							$select2 = '<select name="repeat_monthly_weekday" id="repeat_monthly_weekday">';
								$st = new DateTime('Saturday');
								for ($i=0; $i < 7; $i++) { 											
									$select2 .= '<option value="'.$i.'" '.selected($i, $r_opt['monthly']['weekday'], false).'>'.$st->modify('+1 day')->format('l').'</option>';
								}
							$select2 .= '</select>';
							$input = '<span><input type="number" min="0" style="width:60px;" value="'.$r_opt['monthly']['noMonths'].'" id="repeat_monthly_noMonths2" name="repeat_monthly_noMonths2" /></span>';
							printf( __("On %s %s Of every %s months", 'wc_crm'), $select1, $select2, $input); ?></label>
						</li>
					</ul>
			</div>
			<div class="show_repeat_yearly_options show_repeat_type_options">
					<ul class="wc-radios">
						<li><label><input type="radio" class="select short" value="1" name="repeat_yearly_type" <?php checked('1', $r_opt['yearly']['type']); ?>>
							<?php
							$select1 = '<select name="repeat_yearly_months" id="repeat_yearly_months">';
								for ($m=1; $m<=12; $m++) {
									$select1 .= '<option value="'.$m.'" '.selected($m, $r_opt['yearly']['months'], false).'>'.date('F', mktime(0,0,0,$m)).'</option>';
								}
							$select1 .= '</select>';

							$select2 = '<select name="repeat_yearly_day" id="repeat_yearly_day">';
								foreach (range(1, 31) as $nday) {
									$select2 .= '<option value="'.$nday.'" '.selected($nday, $r_opt['yearly']['days'], false).'>'.$nday.'</option>';
								}
							$select2 .= '</select>';
							printf( __("Of every %s %s", 'wc_crm'), $select1, $select2); ?>
							</label>
						</li>
						<li><label><input type="radio" class="select short" value="2" name="repeat_yearly_type" <?php checked('2', $r_opt['yearly']['type']); ?>> 
							<?php
							$select1 = '<select name="repeat_yearly_weeksequence" id="repeat_yearly_weeksequence">';
								foreach ($weeksequence as $k => $ws) {
									$select1 .= '<option value="'.$k.'" '.selected($k, $r_opt['yearly']['weeksequence'], false).'>'.$ws.'</option>';
								}
							$select1 .= '</select>';
							$select2 = '<select name="repeat_yearly_weekday" id="repeat_yearly_weekday">';
								$st = new DateTime('Saturday');
								for ($i=0; $i < 7; $i++) { 											
									$select2 .= '<option value="'.$i.'" '.selected($i, $r_opt['yearly']['weekday'], false).'>'.$st->modify('+1 day')->format('l').'</option>';
								}
							$select2 .= '</select>';
							$select3 = '<select name="repeat_yearly_months2" id="repeat_yearly_months2">';
								for ($m=1; $m<=12; $m++) {
									$select3 .= '<option value="'.$m.'" '.selected($m, $r_opt['yearly']['months'], false).'>'.date('F', mktime(0,0,0,$m)).'</option>';
								}
							$select3 .= '</select>';
							
							printf( __("On %s %s of %s", 'wc_crm'), $select1, $select2, $select3 ); ?></label>
						</li>
					</ul>
			</div>
			<!-- /**/ -->
		</div>	
</div>