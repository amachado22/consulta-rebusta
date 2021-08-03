<?php

if (!defined('ABSPATH')) exit;

class WC_CRM
{

    /**
     * The single instance of WC_CRM.
     * @var    object
     * @access  private
     * @since    2.7
     */
    private static $_instance = null;

    /**
     * Settings class object
     * @var     object
     * @access  public
     * @since    2.7.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since    2.7.0
     */
    public $script_suffix;

    /**
     * Constructor function.
     * @access  public
     * @return  void
     */
    public function __construct($file = '', $version = '1.0.0')
    {

        $this->statuses = array(
            'Customer' => 'Customer',
            'Lead' => 'Lead',
            'Follow-Up' => 'Follow-Up',
            'Prospect' => 'Prospect',
            'Favourite' => 'Favourite',
            'Blocked' => 'Blocked',
            'Flagged' => 'Flagged'
        );
        $this->tables = array();
        $this->_version = $version;
        $this->_token = 'wc_crm';

        // Load plugin environment variables
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        //$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $this->script_suffix = '';

        $this->define_constants();
        $this->load_plugin_textdomain();
        $this->includes();
        $this->init_hooks();

        do_action('wc_crm_loaded');
    } // End __construct ()

    /**
     * Define WC_CRM Constants
     * @since    2.7.0
     */
    private function define_constants()
    {
        $upload_dir = wp_upload_dir();

        $this->define('WC_CRM_FILE', $this->file);
        define('WC_CRM_BASENAME', plugin_basename($this->file));
        define('WC_CRM_DIR', $this->dir);
        define('WC_CRM_VERSION', $this->_version);
        define('WC_CRM_TOKEN', $this->_token);
    }

    /**
     * Define constant if not already set
     * @param  string $name
     * @since    2.7.0
     * @param  string|bool $value
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * What type of request is this?
     * string $type ajax, frontend or admin
     * @return bool
     * @since    2.7.0
     */
    private function is_request($type)
    {
        switch ($type) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined('DOING_AJAX');
            case 'cron' :
                return defined('DOING_CRON');
            case 'frontend' :
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     * @since    2.7.0
     */
    public function includes()
    {
        if (!class_exists('MailChimpAPI')) {
            require_once('api/MailChimpAPI.php');
        }
        include_once('class-wc-crm-autoloader.php');
        include_once('core-functions.php');
        include_once('customer-functions.php');
        include_once('notice-functions.php');
        include_once('group-functions.php');
        include_once('task-functions.php');
        include_once('call-functions.php');
        include_once('class-wc-crm-install.php');
        include_once('class-wc-crm-ajax.php');
        include_once('admin/class-wc-crm-admin.php');
        include_once('class-wc-crm-frontend.php');

        if(get_option("wc_crm_enable_validation") == "yes")
            include_once('class-wc-crm-validation.php');
    }

    /**
     * Hook into actions and filters
     * @since    2.7.0
     */
    private function init_hooks()
    {
        register_activation_hook($this->file, array($this, 'install'));
        add_action('init', array($this, 'calls_publish_status_fix'), 0);
        add_action('admin_head', array($this, 'message_shortcodes_button'));
        add_filter('woocommerce_json_search_found_customers', array($this, 'get_users_by_agent'));
        // Load frontend JS & CSS
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

        // Load admin JS & CSS
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);
        #add_action( 'admin_print_scripts', array( $this, 'admin_inline_js' ), 10, 1 );
        add_action('wp_insert_post', array($this, 'save_call_status'), 10, 3);
    }

    public function message_shortcodes_button()
    {
        $screen = isset($_GET['screen']) ? $_GET['screen'] : '';
        if ($screen == 'email') {
            add_filter("mce_external_plugins", array($this, "message_add_tinymce_plugin"));
            add_filter('mce_buttons', array($this, 'message_register_shortcodes_button'));
        }
    }

    public function message_add_tinymce_plugin($plugin_array)
    {
        $plugin_array['wc_crm_shorcodes_button'] = esc_url($this->assets_url) . 'js/shortcodes-button.js';
        return $plugin_array;
    }

    public function message_register_shortcodes_button($buttons)
    {
        array_push($buttons, "wc_crm_shorcodes_button");
        return $buttons;
    }

    public function get_users_by_agent($result)
    {
        global $wpdb;
        $current_user = wp_get_current_user();
        if (in_array('agent', $current_user->roles)) {
            $users_include = array();
            $users = $wpdb->get_results("SELECT um.`user_id` FROM {$wpdb->usermeta} um WHERE um.`meta_key` = 'customer_agent' AND um.`meta_value` = {$current_user->ID}");
            foreach ($users as $user) {
                $users_include[] = $user->user_id;
            }
            foreach ($result as $key => $val) {
                if (in_array($key, $users_include)) {
                    continue;
                } else {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }

    /**
     * Load frontend CSS.
     * @access  public
     * @since    2.7.0
     * @return void
     */
    public function enqueue_styles()
    {
        wp_register_style('dropzone-css', esc_url($this->assets_url) . 'plugins/dropzone/css/dropzone.css', [], '5.4.0' );
        wp_register_style('frontend-css', esc_url($this->assets_url) . 'css/frontend.css', [], $this->_version );
        wp_register_style('jquery-ui-css', esc_url($this->assets_url) . 'css/jquery-ui.css', [], $this->_version );
        if(is_account_page()){
            wp_enqueue_style('dropzone-css');
            wp_enqueue_style('frontend-css');
            wp_enqueue_style('jquery-ui-css');
        }

    } // End enqueue_styles ()

    /**
     * Load frontend Javascript.
     * @access  public
     * @since    2.7.0
     * @return  void
     */
    public function enqueue_scripts()
    {
        wp_register_script('dropzone-js', esc_url($this->assets_url) . 'plugins/dropzone/js/dropzone.js', array('jquery'), '5.4.0' );
        wp_register_script('frontend-js', esc_url($this->assets_url) . 'js/frontend.js', array('jquery', 'jquery-ui-datepicker'), $this->_version );


        if(is_account_page() || is_checkout()){
            wp_enqueue_style('jquery-ui-css');
            wp_enqueue_script('dropzone-js');
            wp_enqueue_script('frontend-js');

            $file_types = get_option('wc_crm_file_types_validation');
            if(!$file_types || !count($file_types)) $file_types = array('image/*', 'application/pdf');

            wp_localize_script('frontend-js', 'wc_crm_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'max_upload_size' => json_encode(wp_max_upload_size()),
                'file_types' => json_encode($file_types)
            ));
        }
    } // End enqueue_scripts ()

    /**
     * Load admin CSS.
     * @access  public
     * @since    2.7.0
     * @return  void
     */
    public function admin_enqueue_styles($hook = '')
    {

        $styles = array(
            $this->_token . '-fonts',
            $this->_token . '-textbox',
            $this->_token . '-current-call',
            'wp-color-picker',
            'thickbox',
            'jquery-ui-css'
        );
        wp_register_style('jquery-ui-css', esc_url($this->assets_url) . 'css/jquery-ui.css');
        wp_register_style($this->_token . '-textbox', esc_url($this->assets_url) . 'css/TextboxList.css');
        wp_register_style($this->_token . '-fonts', esc_url($this->assets_url) . 'css/fonts.css');
        wp_register_style($this->_token . '-current-call', esc_url($this->assets_url) . 'css/admin-current-call.css');
        wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', $styles, $this->_version);
        wp_enqueue_style($this->_token . '-admin');
        global $post_type;

        $hooks = array('toplevel_page_wc_crm', 'customers_page_wc_crm-new-customer', 'customers_page_wc_crm-settings', 'customers_page_wc_crm-logs', 'customers_page_wc_crm-groups');
        $pts = array('wc_crm_tasks', 'wc_crm_accounts', 'wc_crm_calls', 'wc_crm_validations');
        if (!in_array($hook, $hooks) && !in_array($post_type, $pts)) {
            return;
        }
        //if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_status_and_actions'){
        wp_enqueue_style('fonticonpicker_styles', esc_url($this->assets_url) . 'plugins/fontpicker/css/jquery.fonticonpicker.css');
        wp_enqueue_style('fonticonpicker_theme', esc_url($this->assets_url) . 'plugins/fontpicker/theme/jquery.fonticonpicker.grey.css');
        wp_enqueue_style('fonticonpicker_theme_darkgrey', esc_url($this->assets_url) . 'plugins/fontpicker/theme/jquery.fonticonpicker.darkgrey.css');

        wp_enqueue_style('fonticonpicker_fonts_fontello', esc_url($this->assets_url) . 'plugins/fontpicker/fonts/fontello/css/fontello.css');
        wp_enqueue_style('fonticonpicker_fonts_icomoon', esc_url($this->assets_url) . 'plugins/fontpicker/fonts/icomoon/style.css');

        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);

        if ($post_type == 'wc_crm_accounts') {
            wp_enqueue_style($this->_token . '-accounts', esc_url($this->assets_url) . 'css/admin-accounts.css', array(), WC_VERSION);
        }
        if ($post_type == 'wc_crm_tasks' || $hook == 'toplevel_page_wc_crm') {
            wp_enqueue_style($this->_token . '-tasks', esc_url($this->assets_url) . 'css/admin-tasks.css', array(), WC_VERSION);
        }
        if ($post_type == 'wc_crm_calls' || $hook == 'toplevel_page_wc_crm') {
            wp_enqueue_style($this->_token . '-calls', esc_url($this->assets_url) . 'css/admin-calls.css', array(), WC_VERSION);
        }
    } // End admin_enqueue_styles ()

    public function get_localize_script()
    {
        global $post;
        return apply_filters($this->_token . '_admin_localize_script', array(
            'copy_billing' => __('Copy billing information to shipping information? This will remove any currently entered shipping information.', 'wc_crm'),
            'load_billing' => __('Load the customer\'s billing information? This will remove any currently entered billing information.', 'wc_crm'),
            'load_shipping' => __('Load the customer\'s shipping information? This will remove any currently entered shipping information.', 'wc_crm'),
            'reset_default' => __('Reset default', 'wc_crm'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'search_customers_nonce' => wp_create_nonce('search-customers'),
            'get_customer_details_nonce' => wp_create_nonce('get-customer-details'),
            'add_order_note_nonce' => wp_create_nonce('add-order-note'),
            'delete_order_note_nonce' => wp_create_nonce('delete-order-note'),
            'post_id' => isset($post->ID) ? $post->ID : 0,
            'countries' => json_encode(array_merge(WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states())),

            'i18n_select_state_text' => esc_attr__('Select an option&hellip;', 'wc_crm'),
            'curent_time' => current_time('Y-m-d'),
            'curent_time_h' => current_time('g'),
            'curent_time_m' => current_time('i'),
            'curent_time_s' => current_time('s'),
        ));
    }

    /**
     * Load admin Javascript.
     * @access  public
     * @since    2.7.0
     * @return  void
     */
    public function admin_enqueue_scripts($hook = '')
    {
        global $post_type;

        wp_register_script($this->_token . '-account', esc_url($this->assets_url) . 'js/account.js');
        wp_register_script($this->_token . '-tasks', esc_url($this->assets_url) . 'js/tasks.js');
        wp_register_script($this->_token . '-timer', esc_url($this->assets_url) . 'js/jquery.timer.js');
        wp_register_script($this->_token . '-calls', esc_url($this->assets_url) . 'js/calls.js');
        wp_register_script($this->_token . '-groups', esc_url($this->assets_url) . 'js/groups.js');
        wp_register_script($this->_token . '-fonticonpicker', esc_url($this->assets_url) . 'plugins/fontpicker/jquery.fonticonpicker.js');
        wp_register_script($this->_token . '-settings', esc_url($this->assets_url) . 'js/settings.js', array($this->_token . '-fonticonpicker'));
        wp_register_script($this->_token . '-textboxlist', esc_url($this->assets_url) . 'js/TextboxList.js');
        wp_register_script($this->_token . '-growing_input', esc_url($this->assets_url) . 'js/GrowingInput.js');
        wp_register_script($this->_token . '-shortcodes_button', esc_url($this->assets_url) . 'js/shortcodes-button.js', array('tiny_mce'));

        wp_enqueue_script($this->_token . '-calls-top-bar', esc_url($this->assets_url) . 'js/calls-top-bar.js', array('jquery', $this->_token . '-timer'), $this->_version, true);

        $hooks = array('toplevel_page_wc_crm', 'customers_page_wc_crm-new-customer', 'customers_page_wc_crm-settings', 'customers_page_wc_crm-logs', 'customers_page_wc_crm-groups');
        $pts = array('wc_crm_tasks', 'wc_crm_accounts', 'wc_crm_calls', 'wc_crm_validations');
        if (!in_array($hook, $hooks) && !in_array($post_type, $pts)) {
            return;
        }
        wp_enqueue_media();
        $scripts = array(
            'editor',
            'thickbox',
            'custom-background',
            'media-upload',
            'jquery-tiptip',
            'jquery-ui-datepicker'
        );

        $screen = isset($_GET['screen']) ? $_GET['screen'] : '';
        switch ($screen) {
            case 'email':
                $scripts[] = $this->_token . '-textboxlist';
                $scripts[] = $this->_token . '-growing_input';
                $scripts[] = 'select2';
                break;
            default:
                $scripts[] = 'wc-enhanced-select';
                $scripts[] = 'jquery-blockui';
                if (!class_exists('acf')) {
                    $api_key = get_option('wc_crm_google_map_api_key');
                    wp_register_script('google_map', '//maps.google.com/maps/api/js?key=' . $api_key);
                    wp_register_script($this->_token . '-jquery-map', esc_url($this->assets_url) . 'js/jquery.ui.map.full.min.js', array('google_map'), $this->_version);
                    $scripts[] = $this->_token . '-jquery-map';
                }
                break;
        }
        #https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap"
        if ($hook == 'customers_page_wc_crm-settings') {
            $scripts[] = $this->_token . '-settings';
        }
        if ($hook == 'customers_page_wc_crm-logs') {
            $scripts[] = 'wc-enhanced-select';
        }
        if ($hook == 'customers_page_wc_crm-groups') {
            $scripts[] = $this->_token . '-groups';
        }
        if ($post_type == 'wc_crm_accounts') {
            $scripts[] = $this->_token . '-account';
        }
        if ($post_type == 'wc_crm_tasks') {
            $scripts[] = $this->_token . '-tasks';
            $scripts[] = 'woocommerce_admin';
        }
        if ($post_type == 'wc_crm_calls') {
            $scripts[] = $this->_token . '-timer';
            $scripts[] = $this->_token . '-calls';
            $scripts[] = 'woocommerce_admin';
        }
        if ($post_type == 'wc_crm_validations') {
            $scripts[] = 'jquery-ui-datepicker';
        }


        wp_enqueue_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', $scripts, $this->_version);

        $params = $this->get_localize_script();
        wp_localize_script($this->_token . '-admin', 'wc_crm_params', $params);
        wp_localize_script($this->_token . '-calls', 'wc_crm_calls', array(
            'customer_url' => get_admin_url() . 'admin.php?page=' . WC_CRM_TOKEN . '&c_id=%d',
            'post_url' => get_admin_url() . 'post.php?post=%d&action=edit',
            'call_date_txt' => __('Call Date:', 'wc_crm'),
            'call_save_txt' => __('Save Call', 'wc_crm'),
        ));
    } // End admin_enqueue_scripts ()


    /**
     * Print inline admin Javascript.
     * @access  public
     * @since   3.1.4.5
     * @return  void
     */
    public function admin_inline_js($hook = '')
    {
    }

    /**
     * Load plugin textdomain
     * @access  public
     * @since    2.7.0
     * @return  void
     */
    public function load_plugin_textdomain()
    {
        $domain = 'wc_crm';
        $locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
        $locale = apply_filters( 'plugin_locale', $locale, $domain );
        $mofile = $domain . '-' . $locale . '.mo';

        load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile );
        load_plugin_textdomain( $domain, false, $this->plugin_path() . '/lang/' . $mofile );
    }

    /**
     * Main WC_CRM Instance
     *
     * Ensures only one instance of WC_CRM is loaded or can be loaded.
     *
     * @since    2.7.0
     * @static
     * @see WC_CRM()
     * @return WC_CRM instance
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }
        return self::$_instance;
    } // End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since    2.7.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    } // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since    2.7.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    } // End __wakeup ()

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since    2.7.0
     * @return  void
     */
    public function install($networkwide)
    {
        global $wpdb;

        if (function_exists('is_multisite') && is_multisite()) {
            // check if it is a network activation - if so, run the activation function for each blog id
            if ($networkwide) {
                $old_blog = $wpdb->blogid;
                // Get all blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    WC_CRM_Install::install();
                }
                switch_to_blog($old_blog);
                return;
            }
        } else {
            WC_CRM_Install::install();
        }

        WC_CRM_VALIDATION::validation_endpoint();
    } // End install ()


    /**
     * Log the plugin version number.
     * @access  public
     * @since    2.7.0
     * @return  void
     */
    private function _log_version_number()
    {
        update_option($this->_token . '_version', $this->_version);
    } // End _log_version_number ()

    /** Helper functions ******************************************************/

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', WC_CRM_FILE));
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(WC_CRM_FILE));
    }

    /**
     * Check if current page is crm screen
     *
     * @return boolean
     */
    public function is_crm_page()
    {
        if (isset($_GET['page']) && (
                $_GET['page'] == 'wc_crm' ||
                $_GET['page'] == 'wc_crm-new-customer' ||
                $_GET['page'] == 'wc_crm-logs' ||
                $_GET['page'] == 'wc_crm-settings' ||
                $_GET['page'] == 'wc_crm_import' ||
                $_GET['page'] == 'wc_crm_accounts' ||
                $_GET['page'] == 'wc_crm-groups'
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Update Call status when creating
     *
     */
    public function save_call_status($post_id, $post, $update)
    {
        if ($post->post_type == 'wc_crm_calls' && $post->post_status == 'publish') {
            $call_data = array(
                'ID' => $post_id,
                'post_status' => 'wcrm-completed'
            );
            wp_update_post($call_data);
        }
    }

    public function calls_publish_status_fix()
    {
        global $wpdb;
        $sql = "UPDATE {$wpdb->posts} SET `post_status` = 'wcrm-completed' WHERE `post_type` = 'wc_crm_calls' AND `post_status` = 'publish'";
        $wpdb->query($sql);
    }


//    public function test()
//    {
//        global $pagenow;
//
//        $post_type_object = get_post_type_object()
//
//        /**
//         * Fires before the Add New button is added to screen.
//         *
//         * The before_add_new- hook fires in a number of contexts.
//         *
//         * The dynamic portion of the hook name, `$pagenow`, is a global variable
//         * referring to the filename of the current page, such as 'edit.php',
//         * 'post.php' etc. A complete hook for the latter would be
//         * 'before_add_new-post.php'.
//         */
//        do_action('before_add_new-'.$pagenow);
//        if ( current_user_can( $post_type_object->cap->create_posts ) )
//            echo ' <a href="' . esc_url( admin_url( $post_new_file ) ) . '" class="add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
//        /**
//         * Fires after the Add New button is added to screen.
//         *
//         * The after_add_new- hook fires in a number of contexts.
//         *
//         * The dynamic portion of the hook name, `$pagenow`, is a global variable
//         * referring to the filename of the current page, such as 'edit.php',
//         * 'post.php' etc. A complete hook for the latter would be
//         * 'before_add_new-post.php'.
//         */
//        do_action('after_add_new-'.$pagenow);
//    }
}