<?php
/**
 * Setup menus in WP admin.
 *
 * @version		1.0
 * @category	Class
 * @author     Adailton Machado
 
 * @since       2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_CRM_Welcome' ) ) :

/**
 * WC_CRM_Welcome Class
 */
class WC_CRM_Welcome {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus
		add_action( 'admin_menu', array($this, 'about_page') );
	    add_action( 'admin_head', array( $this, 'admin_head' ) );

	}

	public function about_page()
	{
		$welcome_page_name  = __( 'About Customer Relationship Manager', 'wc_crm' );
		$welcome_page_title = __( 'About Customer Relationship Manager', 'wc_crm' );
		if( isset($_GET['page']) && $_GET['page'] == WC_CRM_TOKEN.'-about'){
			//jquery-ui-progressbar
			$page = add_submenu_page( WC_CRM_TOKEN, $welcome_page_title, $welcome_page_name, 'manage_woocommerce', WC_CRM_TOKEN.'-about', array( $this, 'about_screen' ) );
			add_action( 'admin_print_styles-' . $page, array( $this, 'admin_css' ) );			
		}
	}

	public function admin_head()
	{
		remove_submenu_page( WC_CRM_TOKEN, WC_CRM_TOKEN.'-about' );
	}

	/**
	 * admin_css function.
	 */
	public function admin_css() {
		wp_enqueue_style( 'wc-crm-activation', WC_CRM()->assets_url . 'css/activation.css', array(), WC_VERSION );
	}

	/**
	 * Intro text/links shown on all about pages.
	 */
	private function intro() {
		// Drop minor version if 0
		$major_version = WC_CRM()->_version;
		?>
		<h1><?php _e( 'Welcome!', 'wc_crm' ); ?></h1>

		<div class="about-text woocommerce-about-text">
			<?php
				if ( ! empty( $_GET['wc-installed'] ) ) {
					$message = __( 'Thanks, all done!', 'wc_crm' );
				} elseif ( ! empty( $_GET['wc-updated'] ) ) {
					$message = __( 'Thank you for updating to the latest version!', 'wc_crm' );
				} else {
					$message = __( 'Thanks for installing!', 'wc_crm' );
				}

				printf( __( '%s WooCommerce Customer Relationship Manager %s, which helps you manage your customers from your WooCommerce store.', 'wc_crm' ), $message, $major_version );
			?>
		</div>

		<div class="wc-badge"><?php printf( __( 'Version %s', 'wc_crm' ), WC_CRM()->_version ); ?></div>
			<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => WC_CRM_TOKEN.'-settings' ), 'admin.php' ) ) ); ?>" class="button button-primary"><?php _e( 'Settings', 'wc_crm' ); ?></a>
		<?php
	}

  	/**
	* Output the about screen.
	*/
	public function about_screen() {
	?>
		<div class="wrap about-wrap full-width-layout">

			<?php $this->intro(); ?>
						
			<?php include_once 'views/html-about-news.php'; ?>

			<div class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => WC_CRM_TOKEN ), 'admin.php' ) ) ); ?>"><?php _e( 'Go to Customers', 'wc_crm' ); ?></a>
			</div>
		
		</div>
	<?php
  	}


}

endif;

return new WC_CRM_Welcome();
