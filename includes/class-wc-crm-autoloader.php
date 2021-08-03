<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_CRM Autoloader
 *
 * @class 		WC_CRM_Autoloader
 * @version		1.0.0
 * @package		WC_CRM/Classes/
 * @category	Class
 * @since    2.7.0
 */
class WC_CRM_Autoloader {

	/**
	 * Path to the includes directory
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		if ( function_exists( "__autoload" ) ) {
			spl_autoload_register( "__autoload" );
		}

		spl_autoload_register( array( $this, 'autoload' ) );

		$this->include_path = untrailingslashit( plugin_dir_path( WC_CRM_FILE ) ) . '/includes/';
	}

	/**
	 * Take a class name and turn it into a file name
	 * @param  string $class
	 * @return string
	 */
	private function get_file_name_from_class( $class ) {
		return 'class-' . str_replace( '_', '-', $class ) . '.php';
	}

	/**
	 * Include a class file
	 * @param  string $path
	 * @return bool successful or not
	 */
	private function load_file( $path ) {
		if ( $path && is_readable( $path ) ) {
			include_once( $path );
			return true;
		}
		return false;
	}

	/**
	 * Auto-load WC classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class = strtolower( $class );
		$file  = $this->get_file_name_from_class( $class );
		$path  = '';

		if ( strpos( $class, 'wc_crm_screen' ) === 0 ) {
			$path = $this->include_path . 'screen/';
		}else if ( strpos( $class, 'wc_crm_table' ) === 0 ) {
			$path = $this->include_path . 'screen/tables/';
		} elseif ( strpos( $class, 'wc_crm_meta_box' ) === 0 ) {
			$path = $this->include_path . 'admin/meta-boxes/';
		} elseif ( strpos( $class, 'wc_crm_admin' ) === 0 ) {
			$path = $this->include_path . 'admin/';
		}

		if ( empty( $path ) || ( ! $this->load_file( $path . $file ) && strpos( $class, 'wc_crm_' ) === 0 ) ) {
			$this->load_file( $this->include_path . $file );
		}
	}
}

new WC_CRM_Autoloader();
