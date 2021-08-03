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

if ( ! class_exists( 'WC_CRM_Admin_Menus' ) ) :

/**
 * WC_CRM_Admin_Menus Class
 */
class WC_CRM_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus
		add_filter( 'set-screen-option', array(&$this, 'set_screen'), 10, 3 );
		add_action( 'admin_menu', array($this, 'add_menu') );		
	    add_filter( 'admin_head', array( $this, 'submenu_order' ) );
        add_action( 'admin_head', array( $this, 'menu_order_count' ) );
        add_action( 'updated_option', array( $this, 'remove_validation_from_menu' ), 10, 3 );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	/**
   * Add the menu item
   */
	public function add_menu() {
		$hook = add_menu_page(
		  __( 'Clientes ', 'wc_crm' ), // page title
		  __( 'Consulta Rebusta', 'wc_crm' ), // menu title
		  'manage_woocommerce', // capability
		  WC_CRM_TOKEN, // unique menu slug
		  array($this, 'customers_list'),
		  'dashicons-admin-wc-crm',
		  '56.3'
		);

		$new_customer_hook = add_submenu_page(
			WC_CRM_TOKEN,
			__( "Add Novo Cliente", 'wc_crm' ),
			__( "Add Novo", 'wc_crm'),
			'manage_woocommerce',
			WC_CRM_TOKEN.'-new-customer',
			array($this, 'new_customer')
		);

		$logs_hook = add_submenu_page(
			WC_CRM_TOKEN,
			__( "Emails", 'wc_crm' ),
			__( "Emails", 'wc_crm'),
			'manage_woocommerce',
			WC_CRM_TOKEN.'-logs',
			array($this, 'logs_page')
		);

		$accounts_hook = add_submenu_page(
		  WC_CRM_TOKEN,
		  __('Contas', 'wc_crm'),
		  __('Contas', 'wc_crm'),
		  'manage_woocommerce',
		  'edit.php?post_type=wc_crm_accounts'
		);

		$tasks_hook = add_submenu_page(
		  WC_CRM_TOKEN,
		  __('Tarefas', 'wc_crm'),
		  __('Tarefas', 'wc_crm'),
		  'manage_woocommerce',
		  'edit.php?post_type=wc_crm_tasks'
		);

		$calls_hook = add_submenu_page(
		  WC_CRM_TOKEN,
		  __('Ligaçoes', 'wc_crm'),
		  __('Ligações', 'wc_crm'),
		  'manage_woocommerce',
		  'edit.php?post_type=wc_crm_calls'
		);

		$groups_hook = add_submenu_page(
			WC_CRM_TOKEN,
			__( "Grupos", 'wc_crm' ),
			__( "Grupos", 'wc_crm'),
			'manage_woocommerce',
			WC_CRM_TOKEN.'-groups',
			array($this, 'groups_page')
		 );

        if(get_option('wc_crm_enable_validation') == 'yes'){
            add_submenu_page(
                WC_CRM_TOKEN,
                __('Documentos', 'wc_crm'),
                __('Documentos', 'wc_crm'),
                'manage_woocommerce',
                'edit.php?post_type=wc_crm_validations'
            );
        }

		add_submenu_page( WC_CRM_TOKEN,
			__( "Configuração", 'wc_crm' ),
			__( "Configuração", 'wc_crm'),
			'manage_woocommerce',
			WC_CRM_TOKEN.'-settings',
			array($this, 'settings')
			);


		add_action( "load-$hook", array($this, 'c_screen_option') );
		add_action( "load-$logs_hook", array($this, 'a_screen_option') );
		add_action( "load-$groups_hook", array($this, 'g_screen_option') );
	}

	public function c_screen_option()
	{
		if( !isset($_GET['c_id']) ){
			$option = 'per_page';
			$args = array(
				'label' => __( 'Lista de Clientes', 'wc_crm' ),
				'default' => 20,
				'option' => 'customers_per_page'
			);
			add_screen_option( $option, $args );
			WC_CRM()->tables['customers'] = new WC_CRM_Table_Customers();
		}
	}
	public function a_screen_option()
	{
		if( !isset($_GET['c_id']) ){
			$option = 'per_page';
			$args = array(
				'label' => __( 'Log Records', 'wc_crm' ),
				'default' => 20,
				'option' => 'logs_per_page'
			);
			add_screen_option( $option, $args );
			WC_CRM()->tables['activity'] = new WC_CRM_Table_Activity();
		}
	}

	public function g_screen_option()
	{
		if( !isset($_GET['c_id']) ){
			$option = 'per_page';
			$args = array(
				'label' => __( 'Groups', 'wc_crm' ),
				'default' => 20,
				'option' => 'logs_per_page'
			);
			add_screen_option( $option, $args );
			WC_CRM()->tables['groups'] = new WC_CRM_Table_Groups();
		}
	}

  /**
   *
   * @param mixed $menu_order
   * @return array
   */
  public function submenu_order( ) {
    global $menu, $submenu, $parent_file, $submenu_file, $post_type;
    $pts = array('wc_crm_accounts', 'wc_crm_tasks', 'wc_crm_calls', 'wc_crm_validations');
    if( in_array($post_type, $pts) ){
      $parent_file = WC_CRM_TOKEN;
      $submenu_file = 'edit.php?post_type='.$post_type;
    }
    
  }


  	
	public function customers_list() {
		$screen = isset($_GET['screen']) ? $_GET['screen'] : '';
		switch ($screen) {
			case 'customer_notes':
				WC_CRM_Screen_Customers_Edit::display_notes($_GET['c_id']);
				break;

			case 'email':
				WC_CRM_Screen_Activity::display_email_form();
				break;			
			
			default:
				if( isset($_GET['c_id']) && !empty($_GET['c_id'])){
					WC_CRM_Screen_Customers_Edit::output($_GET['c_id']);
				}else{
					WC_CRM_Screen_Customers_List::output();
				}
				break;
		}
	
  	}
  	public function new_customer() {
		WC_CRM_Screen_Customers_Edit::output();
  	}

  	public function logs_page() {
  		if(isset($_GET['log_id']) && !empty($_GET['log_id'])){
			WC_CRM_Screen_Activity::display_activity_data();
		}else{
			WC_CRM_Screen_Activity::output();
		}
  	}

  	public function groups_page() {
		WC_CRM_Screen_Groups::output();		
  	}

    public function validations_page() {
        WC_CRM_VALIDATION::output();
    }

  	public function settings() {
		WC_CRM_Admin_Settings::output();		
  	}

    public function menu_order_count()
    {
        global $submenu;

        if ( isset( $submenu['wc_crm'] ) ) {

            $validation_count = WC_CRM_VALIDATION::get_validations();

            foreach ( $submenu['wc_crm'] as $key => $menu_item ) {
                if ( 0 === strpos( $menu_item[0], _x( 'Documentos', 'Admin menu name', 'wc_crm' ) ) ) {
                    $submenu['wc_crm'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . esc_attr( $validation_count ) . '"><span class="processing-count">' . number_format_i18n( $validation_count ) . '</span></span>';
                    break;
                }
            }
        }
    }

    public function remove_validation_from_menu($option, $old_value, $value)
    {   if($option == 'wc_crm_enable_validation'){
            wp_safe_redirect(admin_url() . 'admin.php?page=wc_crm-settings&tab=validation');
        }
    }


}

endif;

return new WC_CRM_Admin_Menus();
