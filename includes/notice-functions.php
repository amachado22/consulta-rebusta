<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get the count of notices added, either for all notices (default) or for one
 * particular notice type specified by $notice_type.
 *
 * @since 2.1
 * @param string $notice_type The name of the notice type - either error, success or notice. [optional]
 * @return int
 */
function wc_crm_notice_count( $notice_type = '' ) {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
		return;
	}

	$notice_count = 0;
	$all_notices  = wc_crm_get_notices();

	if ( isset( $all_notices[$notice_type] ) ) {

		$notice_count = absint( sizeof( $all_notices[$notice_type] ) );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			$notice_count += absint( sizeof( $all_notices ) );
		}

	}

	return $notice_count;
}

/**
 * Check if a notice has already been added
 *
 * @since 2.1
 * @param string $message The text to display in the notice.
 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 * @return bool
 */
function wc_crm_has_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
		return false;
	}

	$notices = wc_crm_get_notices();
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();
	return array_search( $message, $notices ) !== false;
}

/**
 * Add and store a notice
 *
 * @since 2.1
 * @param string $message The text to display in the notice.
 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 */
function wc_crm_add_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
		return;
	}

	$notices = wc_crm_get_notices();

	$notices[$notice_type][] = apply_filters( 'woocommerce_add_' . $notice_type, $message );

	$_SESSION['wc_crm_notices'] = $notices;
}

/**
 * Unset all notices
 *
 * @since 2.1
 */
function wc_crm_clear_notices() {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
		return;
	}
	if( isset($_SESSION['wc_crm_notices']) ){
		unset($_SESSION['wc_crm_notices']);
	}
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @since 2.1
 */
function wc_crm_print_notices() {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
		return;
	}

	$all_notices  = wc_crm_get_notices();
	$notice_types = apply_filters( 'woocommerce_notice_types', array( 'error', 'success', 'notice' ) );

	foreach ( $notice_types as $notice_type ) {
		if ( wc_crm_notice_count( $notice_type ) > 0 ) {
			
			foreach ($all_notices[$notice_type] as $message) {
				wc_crm_print_notice( $message, $notice_type );
			}

		}
	}

	wc_crm_clear_notices();
}

/**
 * Print a single notice immediately
 *
 * @since 2.1
 * @param string $message The text to display in the notice.
 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 */
function wc_crm_print_notice( $message, $notice_type = 'success' ) {
	if($notice_type != 'error'){
		$notice_type = 'updated';
	}
	if ( isset( $_REQUEST['trashed'] ) && isset( $_REQUEST['page']) && $_REQUEST['page'] == 'wc_crm' ) {
		
		$ids = preg_replace( '/[^0-9,]/', '', $_REQUEST['trashed'] );
		$message .= ' <a href="' . esc_url( wp_nonce_url( admin_url( add_query_arg( array( 'page' => WC_CRM_TOKEN, 'wc_crm_customer_action' => 'untrash', 'ids' => $ids ), 'admin.php' ) ) ) ) . '">' . __('Undo') . '</a>';
	}
	echo '<div class="'.$notice_type.' below-h2" id="message"  style="display: block;">';
		echo '<p>'.$message.'</p>';
	echo '</div>';
}

/**
 * Returns all queued notices, optionally filtered by a notice type.
 *
 * @since 2.1
 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 * @return array|mixed
 */
function wc_crm_get_notices( $notice_type = '' ) {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before woocommerce_init.', 'woocommerce' ), '2.3' );
		return;
	}

	
	$all_notices = array();	
	if( isset($_SESSION['wc_crm_notices']) ){
		$all_notices = $_SESSION['wc_crm_notices'];
	}

	if( !is_array($all_notices) ){
		$all_notices = array();
	}

	if ( empty ( $notice_type ) ) {
		$notices = $all_notices;
	} elseif ( isset( $all_notices[$notice_type] ) ) {
		$notices = $all_notices[$notice_type];
	} else {
		$notices = array();
	}

	return $notices;
}

/**
 * Add notices for WP Errors
 * @param  WP_Error $errors
 */
function wc_crm_add_wp_error_notices( $errors ) {
	if ( is_wp_error( $errors ) && $errors->get_error_messages() ) {
		foreach ( $errors->get_error_messages() as $error ) {
			wc_crm_add_notice( $error, 'error');
		}
	}
}
