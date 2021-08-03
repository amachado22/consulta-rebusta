<?php
/**
 * Installation related functions and actions.
 *
 * @category Admin
 * @package  WC_CRM/Classes
 * @version  1.0.0
 * @since    2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_CRM_Install Class
 */
class WC_CRM_Install
{

    /** @var array DB updates that need to be run */
    private static $db_updates = array(
        '3.0.0' => 'updates/wc_crm-update-3.0.php',
        '3.0.6' => 'updates/wc_crm-update-3.0.6.php',
        '3.4.1' => 'updates/wc_crm-update-3.4.1.php',
    );

    /**
     * Hook in tabs.
     */
    public static function init()
    {
        add_action('admin_init', array(__CLASS__, 'check_version'), 5);
        add_action('admin_init', array(__CLASS__, 'install_actions'), 6);
        add_filter('plugin_action_links_' . WC_CRM_BASENAME, array(__CLASS__, 'plugin_action_links'));
        add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);

        // Run this on activation.
        add_action('wpmu_new_blog', array(__CLASS__, 'new_blog'), 10, 6);
    }

    /**
     * check_version function.
     */
    public static function check_version()
    {
        if (!defined('IFRAME_REQUEST') && (get_option(WC_CRM_TOKEN . '_version') != WC_CRM_VERSION)) {
            self::install();
            do_action('wc_crm_updated');
        }
    }

    /**
     * Install actions such as installing pages when a button is clicked.
     */
    public static function install_actions()
    {
        if (!empty($_GET['do_update_wc_crm'])) {
            self::update();

            // Update complete
            WC_CRM_Admin_Notices::remove_notice('crm_update');

            // What's new redirect
            delete_transient('_wc_crm_activation_redirect');
            wp_redirect(admin_url('admin.php?page=' . WC_CRM_TOKEN . '-about&' . WC_CRM_TOKEN . '-updated=true'));
            exit;
        }
    }


    /**
     * Install WC
     */
    public static function install()
    {
        global $wpdb;

        if (!defined('WC_CRM_INSTALLING')) {
            define('WC_CRM_INSTALLING', true);
        }

        // Queue upgrades/setup wizard
        $current_wc_crm_version = get_option(WC_CRM_TOKEN . '_version', null);
        $current_db_version = get_option(WC_CRM_TOKEN . '_db_version', null);
        $major_wc_version = substr(WC_CRM()->_version, 0, strrpos(WC_CRM()->_version, '.'));
        $major_cur_version = substr($current_wc_crm_version, 0, strrpos($current_wc_crm_version, '.'));

        if (!is_null($current_wc_crm_version) && version_compare($major_cur_version, $major_wc_version, '>')) {
            self::remove_tables();
        }
        self::create_tables();
        self::create_roles();

        WC_CRM_Admin_Notices::remove_all_notices();


        // No versions? This is a new install :)
        if (is_null($current_wc_crm_version) && is_null($current_db_version) && apply_filters('wc_crm_enable_setup_wizard', true)) {
            WC_CRM_Admin_Notices::add_notice('crm_install');
            set_transient('_wc_crm_activation_redirect', 1, 30);

        } elseif (!is_null($current_wc_crm_version) && version_compare($major_cur_version, $major_wc_version, '>')) {
            WC_CRM_Admin_Notices::add_notice('crm_install');
            set_transient('_wc_crm_activation_redirect', 1, 30);
            $current_db_version = '2.6.0';
            self::update_db_version($current_db_version);

            // No customers? Let user run wizard again..
        } elseif (!get_option('wc_crm_customers_loaded')) {
            WC_CRM_Admin_Notices::add_notice('crm_install');

            // Show welcome screen for major updates only
        } elseif (version_compare($current_wc_crm_version, $major_wc_version, '<')) {
            set_transient('_wc_crm_activation_redirect', 1, 30);
        }

        if (!is_null($current_db_version) && version_compare($current_db_version, max(array_keys(self::$db_updates)), '<')) {
            WC_CRM_Admin_Notices::add_notice('crm_update');
        } else {
            self::update_db_version();
        }

        self::update_crm_version();

        /*
         * Deletes all expired transients. The multi-table delete syntax is used
         * to delete the transient record from table a, and the corresponding
         * transient_timeout record from table b.
         *
         * Based on code inside core's upgrade_network() function.
         */
        $sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d";
        $wpdb->query($wpdb->prepare($sql, $wpdb->esc_like('_transient_') . '%', $wpdb->esc_like('_transient_timeout_') . '%', time()));
        // Hide agent column
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            update_user_meta($admin->ID, 'managetoplevel_page_wc_crmcolumnshidden', array('customer_agent'));
        }
        //Set default options
        update_option('wc_crm_total_spent_indication', '1000');
        // Trigger action
        do_action('wc_crm_installed');
    }

    /**
     * Handle updates
     */
    private static function update()
    {
        $current_db_version = get_option(WC_CRM_TOKEN . '_db_version');

        foreach (self::$db_updates as $version => $updater) {
            if (version_compare($current_db_version, $version, '<')) {
                include($updater);
                self::update_db_version($version);
            }
        }

        self::update_db_version();
    }

    /**
     * Update WC version to current
     */
    private static function update_crm_version()
    {
        delete_option(WC_CRM_TOKEN . '_version');
        update_option(WC_CRM_TOKEN . '_version', WC_CRM_VERSION);
    }

    /**
     * Update DB version to current
     */
    private static function update_db_version($version = null)
    {
        delete_option(WC_CRM_TOKEN . '_db_version');
        add_option(WC_CRM_TOKEN . '_db_version', is_null($version) ? WC_CRM_VERSION : $version);
    }

    private static function remove_tables()
    {
        global $wpdb;
        $wpdb->hide_errors();

        $wpdb->query("DROP TABLE {$wpdb->prefix}wc_crm_customer_list");
    }

    /**
     * Set up the database tables which the plugin needs to function.
     */
    private static function create_tables()
    {
        global $wpdb;

        $wpdb->hide_errors();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        /**
         * Before updating with DBDELTA, remove any primary keys which could be modified due to schema updates
         */
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}wc_crm_groups_relationships';")) {
            if ($wpdb->get_var("SHOW INDEX FROM `{$wpdb->prefix}wc_crm_groups_relationships` WHERE Key_name LIKE 'customer_email';")) {
                $wpdb->query("DROP INDEX `customer_email` ON {$wpdb->prefix}wc_crm_groups_relationships;");
            }
            if (!$wpdb->get_var("SHOW COLUMNS FROM `{$wpdb->prefix}wc_crm_groups_relationships` LIKE 'ID';")) {

                if (!$wpdb->get_var("SHOW INDEX FROM `{$wpdb->prefix}wc_crm_groups_relationships` WHERE Key_name = 'PRIMARY';")) {
                    $wpdb->query("ALTER TABLE {$wpdb->prefix}wc_crm_groups_relationships ADD `ID` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;");
                    #$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_crm_groups_relationships DROP PRIMARY KEY, ADD `ID` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT;" );
                }
            }
        }


        dbDelta(self::get_schema());
    }

    /**
     * Create roles and capabilities
     */
    public static function create_roles()
    {
        global $wp_roles;

        if (!class_exists('WP_Roles')) {
            return;
        }

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // agent role
        add_role('agent', __('Agent', 'wc_crm'), array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'list_users' => true
        ));

        $shop_manager = $wp_roles->get_role('shop_manager');

        foreach ($shop_manager->capabilities as $cap => $status) {
            if ($status == true)
                $wp_roles->add_cap('agent', $cap);
        }

        // Customer role
        /*add_role( 'customer', __( 'Customer', 'woocommerce' ), array(
            'read' 						=> true,
            'edit_posts' 				=> false,
            'delete_posts' 				=> false
        ) );*/
    }

    /**
     * Get Table schema
     * @return string
     */
    private static function get_schema()
    {
        global $wpdb;

        $collate = '';

        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) {
                $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
            }
            if (!empty($wpdb->collate)) {
                $collate .= " COLLATE $wpdb->collate";
            }
        }

        return "
CREATE TABLE {$wpdb->prefix}wc_crm_log (
  	ID bigint(20) NOT NULL AUTO_INCREMENT,
	subject text NOT NULL,
	activity_type VARCHAR(50) DEFAULT '' NOT NULL,
	user_id bigint(20) NOT NULL,
	created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	message text NOT NULL,
	phone text NOT NULL,
	call_type text NOT NULL,
	call_purpose text NOT NULL,
	related_to text NOT NULL,
	number_order_product text NOT NULL,
	call_duration text NOT NULL,
	log_status text NOT NULL,
	PRIMARY KEY  (ID)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_crm_groups (
	ID bigint(20) NOT NULL AUTO_INCREMENT,
	group_name VARCHAR(200) NOT NULL,
	group_slug TEXT,
	group_type VARCHAR(200) NOT NULL,
	group_match int(2) DEFAULT 0,
	group_total_spent_mark VARCHAR(200) NOT NULL,
	group_total_spent FLOAT(20) NOT NULL,
	group_user_role VARCHAR(200) NOT NULL,
	group_customer_status LONGTEXT NOT NULL,
	group_product_categories LONGTEXT NOT NULL,
	group_order_status LONGTEXT NOT NULL,
	group_last_order VARCHAR(200) NOT NULL,
	group_last_order_from DATE NOT NULL,
	group_last_order_to DATE NOT NULL,
	PRIMARY KEY  (ID)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_crm_groups_relationships (
	ID bigint(20) NOT NULL AUTO_INCREMENT,
    group_id bigint(20) NOT NULL,
    c_id bigint(20) NOT NULL,
    PRIMARY KEY  (ID)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_crm_customer_list (
	c_id bigint(20) NOT NULL AUTO_INCREMENT,
	email TEXT NOT NULL,
	user_id bigint(20),
	first_name TEXT,
	last_name TEXT,
	capabilities TEXT,
	state TEXT,
	city TEXT,
	country TEXT,
	status TEXT,
	order_id bigint(20),
	last_purchase datetime DEFAULT '0000-00-00 00:00:00',
	order_value FLOAT DEFAULT  '0',
	num_orders bigint(20),
	PRIMARY KEY  (c_id)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_crm_customermeta (
    meta_id bigint(20) unsigned NOT NULL auto_increment,
    wc_crm_customer_id bigint(20) unsigned NOT NULL default '0',
    meta_key varchar(255) default NULL,
    meta_value longtext,
    PRIMARY KEY  (meta_id),
    KEY post_id (wc_crm_customer_id),
    KEY meta_key (meta_key)
) $collate;
CREATE TABLE {$wpdb->prefix}wc_crm_statuses (
	status_id bigint(20) NOT NULL AUTO_INCREMENT,
	status_name   VARCHAR(200),
	status_slug   VARCHAR(200),
	status_icon   VARCHAR(50),
	status_colour VARCHAR(7),
	PRIMARY KEY  (status_id)
) $collate;
		";
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param    mixed $links Plugin Action links
     * @return    array
     */
    public static function plugin_action_links($links)
    {
        $plugin_links = array();

        $plugin_links['settings'] = sprintf('<a href="%s" title="%s">%s</a>', '' . admin_url('admin.php?page=' . WC_CRM_TOKEN . '-settings') . '', __('View Settings', 'wc_crm'), __('Settings', 'wc_crm'));

        return array_merge($plugin_links, $links);
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param mixed $links Plugin Row Meta
     * @param mixed $file Plugin Base file
     * @return  array
     */
    public static function plugin_row_meta($links, $file)
    {
        if ($file == plugin_basename(WC_CRM_BASENAME)) {
            $row_meta = array(
                'docs' => '<a href="' . esc_url(apply_filters('wc_crm_docs_url', 'http://actualityextensions.com/documentation/woocommerce-customer-relationship-manager/')) . '" title="' . esc_attr(__('View Documentation', 'wc_crm')) . '">' . __('Docs', 'wc_crm') . '</a>',
                'support' => '<a href="' . esc_url(apply_filters('wc_crm_docs_url', 'http://actualityextensions.com/contact/')) . '" title="' . esc_attr(__('Visit Support', 'wc_crm')) . '">' . __('Support', 'wc_crm') . '</a>',
            );

            return array_merge($links, $row_meta);
        }

        return (array)$links;
    }


    /**
     * Uninstall tables when MU blog is deleted.
     * @param  array $tables
     * @return array
     */
    public static function wpmu_drop_tables($tables)
    {
        global $wpdb;

        $tables[] = $wpdb->prefix . 'wc_crm_log';
        $tables[] = $wpdb->prefix . 'wc_crm_groups';
        $tables[] = $wpdb->prefix . 'wc_crm_groups_relationships';
        $tables[] = $wpdb->prefix . 'wc_crm_customer_list';
        $tables[] = $wpdb->prefix . 'wc_crm_statuses';

        return $tables;
    }

    public function new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta)
    {
        global $wpdb;
        $crm_path = basename(WC_CRM_FILE);
        if (is_plugin_active_for_network($crm_path . '/woocommerce-crm.php')) {
            $old_blog = $wpdb->blogid;
            switch_to_blog($blog_id);
            self::install();
            switch_to_blog($old_blog);
        }
    }

}

WC_CRM_Install::init();
