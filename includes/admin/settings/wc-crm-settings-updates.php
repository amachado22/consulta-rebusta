<?php
/**
 * WooCommerce CRM Updates Settings
 *
 * @author     Adailton Machado
 * @category    Admin
 * @package     WC_CRM/Admin
 * @version     2.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Crm_Settings_Updates extends WC_Settings_Page
{

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->id = 'update_crm';
        $this->label = __('Updates', 'wc_crm');

        add_filter('wc_crm_settings_tabs_array', array($this, 'add_settings_page'), 20);
        add_action('wc_crm_settings_' . $this->id, array($this, 'output'));
        add_action('wc_crm_settings_save_' . $this->id, array($this, 'save'));

    }

    public function output()
    {

        $GLOBALS['hide_save_button'] = true;

        include_once(WC_CRM()->dir . '/updater/pages/index.php');
    }

    public function save()
    {
        $rm = strtoupper($_SERVER['REQUEST_METHOD']);
        if('POST' == $rm)
        {
            if(isset($_POST['envato-update-plugins_purchase_code']) ){
                $purchase_codes = array_map('trim', $_POST['envato-update-plugins_purchase_code']);
                update_option(AEBaseApi::PURCHASE_CODES_OPTION_KEY, $purchase_codes);
            }
        }
    }
}

return new WC_Crm_Settings_Updates();