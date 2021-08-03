<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CRM_Admin_ACF {
	

	/**
	 * @var WC_Crm_Customer The single instance of the class
	 * @since 2.4.3
	 */
	protected static $_instance = null;
	public $user_id = 0;

	/**
	 * Main WC_Crm_Customer Instance
	 *
	 * Ensures only one instance of WC_Crm_Customer is loaded or can be loaded.
	 *
	 * @since 2.4.3
	 * @static
	 * @return WC_Shipping Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.4.3' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.4.3' );
	}

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
	    add_filter( 'acf/location/rule_types', array($this, 'acf_location_rule_types'), 10, 1  );
	    add_filter( 'acf/location/rule_values/ef_crm_customers', array($this, 'ef_crm_customers_rule_values'), 10, 1  );
	    
		if( isset($_GET['page']) && ( ( $_GET['page'] == WC_CRM_TOKEN && isset($_GET['c_id']) ) || $_GET['page'] == WC_CRM_TOKEN . '-new-customer' )  ){

			if ( isset($_GET['c_id']) ) {
				$this->user_id = $this->get_user_id( $_GET['c_id'] );
			}

			if($this->user_id > 0 || $_GET['page'] == WC_CRM_TOKEN . '-new-customer' ){
			    add_filter( 'init', array($this, 'init__')  );
			    add_action('admin_enqueue_scripts',	array($this, 'admin_enqueue_scripts'), 1);
			    add_filter('acf/location/match_field_groups', array($this, 'match_field_groups'), 15, 2);
			    add_filter('acf/location/rule_match/ef_crm_customers', array($this, 'rule_match_customer_type'), 10, 3);
			    add_filter('acf/get_post_id', array($this, 'get_post_id'), 20, 1);
			    add_filter('acf/input/admin_head', array($this, 'admin_head'), 20);
			    add_filter('acf/load_value', array($this, 'set_field_value'), 20, 3);
			}
   		}
	}
  function init__(){
  	global $post;
  	$post = NULL;
	if($this->user_id > 0 ){
	    $post = new stdClass();
	    $post->ID = 'user_' . $this->user_id;
	}
  	else{
    	$post = new stdClass();
    	$post->ID = 'user_';
  	}
  }
	function get_post_id($post_id){
		if($this->user_id > 0 ){
            $post_id = 'user_' . $this->user_id;
		}
     	else{
        	$post_id = 'user_';
     	}
		return $post_id;
	}
	function admin_head(){
		global $post;
		$post = NULL;
	}

	function rule_match_customer_type( $match, $rule, $options )
	{
		$user_id = $this->user_id;

		if($user_id > 0 ){
  			if($rule['operator'] == "==")
	        {
	        	$match = ( user_can($user_id, $rule['value']) );
	        	
	        	// override for "all"
	          if( $rule['value'] === "all" )
  				{
  					$match = true;
  				}
	        }
	        elseif($rule['operator'] == "!=")
	        {
	        	$match = ( !user_can($user_id, $rule['value']) );
	        	
	        	// override for "all"
	  	      	if( $rule['value'] === "all" )
  				{
  					$match = false;
  				}
	        }
  		}else{
        	$match = true;
     	}
    return $match;
        
  }
  
  public function acf_location_rule_types($choices)
  {
   $choices[__("Other",'acf')]['ef_crm_customers'] = __("Customer",'wc_customer_relationship_manager');
   return $choices;
  }
  
  public function ef_crm_customers_rule_values($choices)
  {
  	global $wp_roles;
   	$choices = array_merge( array('all' => __('All', 'acf')), $wp_roles->get_names() );
   	return $choices;
  }

  	public function admin_enqueue_scripts()
	{
		global $typenow, $post;
		if( is_null( $post ) ){
			$post = new WP_Query;
		}
		if($this->user_id > 0){
			$post->ID = 'user_' . $this->user_id;
		}
		else{
			$post->ID = 'user_';
		}
		$typenow = 'crm_customers';

        do_action('acf/enqueue_scripts');
        do_action('acf/admin_enqueue_scripts');
        do_action('acf/input/admin_enqueue_scripts');
        do_action('load-post.php');

	}

	public function get_user_id($c_id)
	{
		global $wpdb;

		if( is_array($c_id) ){
			$c_id = implode(',', $c_id);
		}
		return $wpdb->get_var("SELECT user_id FROM {$wpdb->prefix}wc_crm_customer_list WHERE c_id IN({$c_id}) ");
	}

    public function set_field_value($value, $post_id, $field)
    {
        $user_meta = get_user_meta($this->user_id, $field['name'], true);
        if(!empty($user_meta)){
            return $user_meta;
        }

        return $value;
	}

} //end class
new WC_CRM_Admin_ACF;