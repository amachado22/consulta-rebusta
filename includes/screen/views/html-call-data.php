<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $action;
$call = new WC_CRM_Call($post->ID);
?>
<div class="call_data_column_container <?php echo $action; ?>">
	<input type="hidden" name="call_owner" id="call_owner" value="<?php echo $call->call_owner; ?>">
	
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
</div>