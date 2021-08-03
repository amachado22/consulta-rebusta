<?php
/**
 * Logic related to displaying accounts page.
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WC_Crm_Accounts Class
 */
class WC_Crm_Accounts
{

    private static $saved_meta_boxes = false;

    /**
     * Billing fields
     *
     * @var array
     */
    protected static $billing_fields = array();

    /**
     * Shipping fields
     *
     * @var array
     */
    protected static $shipping_fields = array();

    protected static $general_fields = array();

    /**
     * Init billing and shipping fields we display + save
     */
    public static function init_fields()
    {

        self::$billing_fields = Aplicar_filters('wc_crm_admin_accounts_billing_fields', array(
            'first_name' => array(
                'label' => __('Primeiro nome', 'wc_crm'),
                'show' => false
            ),
            'last_name' => array(
                'label' => __('Sobrenome', 'wc_crm'),
                'show' => false
            ),
            'company' => array(
                'label' => __('Empresa', 'wc_crm'),
                'show' => false
            ),
            'address_1' => array(
                'label' => __('Endereço 1', 'wc_crm'),
                'show' => false
            ),
            'address_2' => array(
                'label' => __('Endereço 2', 'wc_crm'),
                'show' => false
            ),
            'city' => array(
                'label' => __('Cidade', 'wc_crm'),
                'show' => false
            ),
            'postcode' => array(
                'label' => __('CEP', 'wc_crm'),
                'show' => false
            ),
            'country' => array(
                'label' => __('País', 'wc_crm'),
                'show' => false,
                'class' => 'js_field-country wc-enhanced-select short',
                'type' => 'select',
                'options' => array('' => __('Select a country&hellip;', 'woocommerce')) + WC()->countries->get_allowed_countries()
            ),
            'state' => array(
                'label' => __('State/County', 'wc_crm'),
                'class' => 'js_field-state select short',
                'show' => false
            ),
            'billing_cpf' => array(
                    'label'         => __('CPF', 'wc_crm'),
                    'show' => false
            ),
            'billing_cnpj' => array(
                    'label'         => __('CNPJ', 'wc_crm'),
                    'show' => false
            )));

        self::$shipping_fields = Aplicar_filters('wc_crm_admin_accounts_shipping_fields', array(
            'first_name' => array(
                'label' => __('Primeiro nome', 'wc_crm'),
                'show' => false
            ),
            'last_name' => array(
                'label' => __('Sobrenome', 'wc_crm'),
                'show' => false
            ),
            'company' => array(
                'label' => __('Empresa', 'wc_crm'),
                'show' => false
            ),
            'address_1' => array(
                'label' => __('Endereço 1', 'wc_crm'),
                'show' => false
            ),
            'address_2' => array(
                'label' => __('Endereço 2', 'wc_crm'),
                'show' => false
            ),
            'city' => array(
                'label' => __('Cidade', 'wc_crm'),
                'show' => false
            ),
            'postcode' => array(
                'label' => __('CEP', 'wc_crm'),
                'show' => false
            ),
            'country' => array(
                'label' => __('País', 'wc_crm'),
                'show' => false,
                'type' => 'select',
                'class' => 'js_field-country wc-enhanced-select short',
                'options' => array('' => __('Select a country&hellip;', 'woocommerce')) + WC()->countries->get_allowed_countries()
            ),
            'state' => array(
                'label' => __('Estado/País', 'wc_crm'),
                'class' => 'js_field-state select short',
                'show' => false
            ),
        ));

        self::$general_fields = Aplicar_filters('wc_crm_admin_accounts_general_fields', array(
            'phone' => array(
                'label' => __('Telefone', 'wc_crm'),
                'show' => false
            ),
            'fax' => array(
                'label' => __('Fax', 'wc_crm'),
                'show' => false
            ),
            'website' => array(
                'label' => __('Website', 'wc_crm'),
                'show' => false
            ),
            'ticker_symbol' => array(
                'label' => __('Símbolo do ticker', 'wc_crm'),
                'show' => false
            ),
            'account_type' => array(
                'label' => __('tipo de conta', 'wc_crm'),
                'show' => false,
                'type' => 'select',
                'class' => 'wc-enhanced-select form-field-wide',
                'options' => wc_crm_get_account_types()
            ),
            'ownership' => array(
                'label' => __('Propriedade', 'wc_crm'),
                'show' => false,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'options' => wc_crm_get_account_ownerships()
            ),
            'industry' => array(
                'label' => __('Indústria', 'wc_crm'),
                'show' => false,
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'custom_attributes' => array(
                    'data-allow_clear' => true,
                    'data-placeholder' => __('Select an Industry', 'wc_crm'),
                ),
                'options' => array("" => "") + wc_crm_get_industries()
            ),
            'employees' => array(
                'label' => __('Funcionários', 'wc_crm'),
                'show' => false,
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => 1,
                    'min' => 0,
                ),
            ),
            'annual_revenue' => array(
                'label' => __('Receita anual', 'wc_crm'),
                'show' => false,
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min' => 0,
                ),
            ),
            'sic_code' => array(
                'label' => __('Código SIC', 'wc_crm'),
                'show' => false,
                'type' => 'number',
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min' => 0,
                ),
            ),
        ));
    }
   

    public static function output($post)
    {
        $account_id = isset($_GET['post']) && !empty($_GET['post']) ? $_GET['post'] : 0;
        self::init_fields();
        wp_nonce_field('wc_crm_save_data', 'wc_crm_meta_nonce');
        ?>
        <style>
            #post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv {
                display: none;
            }
        </style>
        <div class="panel-wrap woocommerce">
            <div id="order_data" class="panel">
                <?php if ($account_id) { ?>
	                <div class="account_icon_box"><span class="account_icon"></span></div>
                    <h2><?php echo __('Conta', 'wc_crm') . ' #' . $account_id . ' '; ?><?php _e('details', 'wc_crm'); ?></h2>
                <?php } else { ?>
                	<h2><?php _e('Detalhes da Conta', 'wc_crm'); ?></h2>
                    <p class="order_number"></p>
                <?php } ?>
                <p class="order_number">
                    <?php
                    $account_owner = 0;
                    if ($account_id) {
                        $account_owner = get_post_meta($account_id, '_account_owner', true);;
                    } else {
                        $current_user = wp_get_current_user();
                        $account_owner = $current_user->ID;
                    }
                    $user_meta = (object)get_user_meta($account_owner);
                    ?>
                <p class="order_number">
                    <?php _e('Dono da conta', 'wc_crm'); ?>:
                    <a href="<?php echo get_edit_user_link($account_owner); ?>" target="_blank">
                        <?php echo $user_meta->first_name[0]; ?> <?php echo $user_meta->last_name[0]; ?>
                    </a>
                </p>
                <input type="hidden" name="account_owner" id="account_owner" value="<?php echo $account_owner; ?>">
                </p>
                <div class="order_data_column_container">
                    <div class="order_data_column account_data_column">
                        <h4><?php _e('Detalhes Gerais', 'wc_crm'); ?></h4>
                        <p class="form-field form-field-wide">
                            <label for="post_title"><?php _e('Nome da conta', 'wc_crm'); ?></label>
                            <input type="text" value="<?php echo $post->post_title; ?>" name="post_title"
                                   id="post_title" required>
                        </p>
                        <?php
                        if (self::$general_fields) {
                            foreach (self::$general_fields as $key => $field) {
                                if (!isset($field['type'])) {
                                    $field['type'] = 'text';
                                }
                                if (!isset($field['id'])) {
                                    $field['id'] = '_' . $key;
                                }

                                switch ($field['type']) {
                                    case 'select' :
                                        woocommerce_wp_select($field);
                                        break;
                                    default :
                                        woocommerce_wp_text_input($field);
                                        break;
                                }
                            }
                        }
                        ?>
                    </div>
                    <div id="order_data_column_billing" class="order_data_column">
                        <?php if ($account_id) { ?>
                            <h4>
                                <?php _e('Detalhes de cobrança', 'wc_crm'); ?>
                                <a class="edit_address" href="#"><img
                                            src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/edit.png"
                                            alt="<?php _e('Edit', 'wc_crm'); ?>" width="14"/></a>
                            </h4>
                            <div class="address">
                                <?php
                                if (self::getFormatedBillingAdress($account_id)) {
                                    echo '<p><strong>' . __('Endereço', 'wc_crm') . ':</strong>' . wp_kses(self::getFormatedBillingAdress($account_id), array('br' => array())) . '</p>';
                                } else {
                                    echo '<p class="none_set"><strong>' . __('Address', 'wc_crm') . ':</strong> ' . __('Nenhum endereço de cobrança definido.', 'wc_crm') . '</p>';
                                }
                                ?>
                            </div>
                        <?php } else { ?>
                            <h4>
                                <?php _e('Detalhes de cobrança', 'wc_crm'); ?>
                            </h4>
                        <?php } ?>
                        <div class="edit_address" <?php echo $account_id == 0 ? 'style="display: block;"' : ''; ?> >
                            <?php
                            foreach (self::$billing_fields as $key => $field) {
                                if (!isset($field['type'])) {
                                    $field['type'] = 'text';
                                }
                                if (!isset($field['id'])) {
                                    $field['id'] = '_billing_' . $key;
                                }
                                switch ($field['type']) {
                                    case 'select' :
                                        woocommerce_wp_select($field);
                                        break;
                                    default :
                                        woocommerce_wp_text_input($field);
                                        break;
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div id="order_data_column_shipping" class="order_data_column">
                        <?php if ($account_id) { ?>
                            <h4>
                                <?php _e('Shipping Details', 'wc_crm'); ?>
                                <a class="edit_address" href="#"><img
                                            src="<?php echo WC()->plugin_url(); ?>/assets/images/icons/edit.png"
                                            alt="<?php _e('Edit', 'wc_crm'); ?>" width="14"/></a>
                            </h4>
                            <div class="address">
                                <?php
                                if (self::getFormatedShippingAdress($account_id)) {
                                    echo '<p><strong>' . __('Endereço', 'wc_crm') . ':</strong>' . wp_kses(self::getFormatedShippingAdress($account_id), array('br' => array())) . '</p>';
                                } else {
                                    echo '<p class="none_set"><strong>' . __('Endereço', 'wc_crm') . ':</strong> ' . __('Nenhum endereço de entrega definido.', 'wc_crm') . '</p>';
                                }
                                ?>
                            </div>
                        <?php } else { ?>
                            <h4>
                                <?php _e('Detalhes de envio', 'wc_crm'); ?>
                            </h4>
                        <?php } ?>
                        <div class="edit_address" <?php echo $account_id == 0 ? 'style="display: block;"' : ''; ?> >
                            <?php
                            if (self::$shipping_fields) {
                                foreach (self::$shipping_fields as $key => $field) {
                                    if (!isset($field['type'])) {
                                        $field['type'] = 'text';
                                    }
                                    if (!isset($field['id'])) {
                                        $field['id'] = '_shipping_' . $key;
                                    }

                                    switch ($field['type']) {
                                        case 'select' :
                                            woocommerce_wp_select($field);
                                            break;
                                        default :
                                            woocommerce_wp_text_input($field);
                                            break;
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <?php
    }

    public static function output_actions($post)
    {
        ?>
        <ul class="order_actions submitbox">
            <li id="actions" class="wide">
                <select id="wc_crm_account_action" name="wc_crm_account_action">
                    <option value="">Actions</option>
                </select>
                <button class="button wc-reload wc_crm_new_action" title="<?php _e('Aplicar', 'wc_crm'); ?>">
					<span>
						<?php _e('Aplicar', 'wc_crm'); ?>
					</span>
                </button>
            </li>
            <li class="wide">
                <input type="submit" class="button save_customer button-primary wc_crm_new_action" style="float: right;"
                       name="save" value="<?php _e('Salvar Conta', 'wc_crm'); ?>">
            </li>
        </ul>
        <?php
    }

    public static function output_notes($post)
    {
        global $post, $wpdb;

        $sql = "SELECT * FROM {$wpdb->comments} as comments
				LEFT JOIN {$wpdb->commentmeta} as commentmeta ON(comments.comment_ID = commentmeta.comment_id AND commentmeta.meta_key = 'comment_post_ID')
				WHERE (comment_post_ID = 0 AND commentmeta.meta_value = {$post->ID}) OR comment_post_ID = {$post->ID}";

        $notes = $wpdb->get_results($sql);

        echo '<ul class="order_notes">';

        if ($notes) {

            foreach ($notes as $note) {

                $note_classes = get_comment_meta($note->comment_ID, 'is_customer_note', true) ? array('customer-note', 'note') : array('note');

                ?>
                <li rel="<?php echo absint($note->comment_ID); ?>" class="<?php echo implode(' ', $note_classes); ?>">
                    <div class="note_content">
                        <?php echo wpautop(wptexturize(wp_kses_post($note->comment_content))); ?>
                    </div>
                    <p class="meta">
                        <abbr class="exact-date"
                              title="<?php echo $note->comment_date; ?>"><?php printf(__('added on %1$s at %2$s', 'woocommerce'), date_i18n(wc_date_format(), strtotime($note->comment_date)), date_i18n(wc_time_format(), strtotime($note->comment_date))); ?></abbr>
                        <?php if ($note->comment_author !== __('WooCommerce', 'woocommerce')) printf(' ' . __('by %s', 'woocommerce'), $note->comment_author); ?>
                        <a href="#" class="delete_note"><?php _e('Deletar nota', 'woocommerce'); ?></a>
                    </p>
                </li>
                <?php
            }

        } else {
            echo '<li>' . __('Ainda não há notas.', 'woocommerce') . '</li>';
        }

        echo '</ul>';
        ?>
        <div class="add_note">
            <h4><?php _e('Add nota', 'woocommerce'); ?> </h4>
            <p>
                <textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20"
                          rows="5"></textarea>
            </p>
            <p>
                <select name="order_note_type" id="order_note_type" style="display: none;">
                    <option value=""><?php _e('Nota Privada', 'woocommerce'); ?></option>
                </select>
                <a href="#" class="add_account_note button"><?php _e('Add', 'woocommerce'); ?></a>
            </p>
        </div>
        <?php
    }

    public static function output_customers($post)
    {
        require_once('screen/tables/class-wc-crm-table-customers.php');
        $wc_crm_order_list = new WC_CRM_Table_Customers();
        $wc_crm_order_list->prepare_accounts_items();
        $wc_crm_order_list->display();
        ?>
        <hr>
        <a href="<?php echo admin_url('admin.php?page=' . WC_CRM_TOKEN . '-new-customer&account=' . $post->ID); ?>"
           class="button" id="asign_customer_to_account">
            <?php _e("Add Cliente", "wc_crm"); ?>
        </a>
        <div class="clear"></div>
        <?php
    }

    public static function getFormatedBillingAdress($id)
    {
        // Formatted Addresses
        $address = Aplicar_filters('wc_crm_accounts_formatted_billing_address', array(
            'first_name' => get_post_meta($id, '_billing_first_name', true),
            'last_name' => get_post_meta($id, '_billing_last_name', true),
            'company' => get_post_meta($id, '_billing_company', true),
            'address_1' => get_post_meta($id, '_billing_address_1', true),
            'address_2' => get_post_meta($id, '_billing_address_2', true),
            'city' => get_post_meta($id, '_billing_city', true),
            'state' => get_post_meta($id, '_billing_state', true),
            'postcode' => get_post_meta($id, '_billing_postcode', true),
            'country' => get_post_meta($id, '_billing_country', true),
            'cpf' => get_post_meta($id, '_billing_cpf', true),
            'cnpj' => get_post_meta($id, '_billing_cnpj', true)
        ), $id);

        $formatted_billing_address = WC()->countries->get_formatted_address($address);

        return $formatted_billing_address;
    }

    public static function getFormatedShippingAdress($id)
    {
        // Formatted Addresses
        $address = Aplicar_filters('wc_crm_accounts_formatted_billing_address', array(
            'first_name' => get_post_meta($id, '_shipping_first_name', true),
            'last_name' => get_post_meta($id, '_shipping_last_name', true),
            'company' => get_post_meta($id, '_shipping_company', true),
            'address_1' => get_post_meta($id, '_shipping_address_1', true),
            'address_2' => get_post_meta($id, '_shipping_address_2', true),
            'city' => get_post_meta($id, '_shipping_city', true),
            'state' => get_post_meta($id, '_shipping_state', true),
            'postcode' => get_post_meta($id, '_shipping_postcode', true),
            'country' => get_post_meta($id, '_shipping_country', true)
        ), $id);

        $formatted_shipping_address = WC()->countries->get_formatted_address($address);

        return $formatted_shipping_address;
    }

    public static function save_meta_boxes($post_id, $post)
    {
        // $post_id and $post are required
        if (empty($post_id) || empty($post) || self::$saved_meta_boxes) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if (defined('DOING_AUTOSAVE') || is_int(wp_is_post_revision($post)) || is_int(wp_is_post_autosave($post))) {
            return;
        }

        // Check the nonce
        if (empty($_POST['wc_crm_meta_nonce']) || !wp_verify_nonce($_POST['wc_crm_meta_nonce'], 'wc_crm_save_data')) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if (empty($_POST['post_ID']) || $_POST['post_ID'] != $post_id) {
            return;
        }

        // Check user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        self::$saved_meta_boxes = true;

        // Check the post type
        if ($post->post_type == 'wc_crm_accounts') {
            do_action('wc_crm_process_accounts_meta', $post_id, $post);
        }
    }

    public static function save($post_id, $post)
    {
        global $wpdb;

        self::init_fields();
        update_post_meta($post_id, '_account_owner', absint($_POST['account_owner']));

        if (self::$general_fields) {
            foreach (self::$general_fields as $key => $field) {
                if (!isset($field['id'])) {
                    $field['id'] = '_' . $key;
                }
                update_post_meta($post_id, $field['id'], wc_clean($_POST[$field['id']]));
            }
        }
        if (self::$billing_fields) {
            foreach (self::$billing_fields as $key => $field) {
                if (!isset($field['id'])) {
                    $field['id'] = '_billing_' . $key;
                }
                update_post_meta($post_id, $field['id'], wc_clean($_POST[$field['id']]));
            }
        }

        if (self::$shipping_fields) {
            foreach (self::$shipping_fields as $key => $field) {
                if (!isset($field['id'])) {
                    $field['id'] = '_shipping_' . $key;
                }
                update_post_meta($post_id, $field['id'], wc_clean($_POST[$field['id']]));
            }
        }

        $acc = array(
            'ID' => $post_id,
            'post_status' => 'publish'
        );

        // Update the post into the database
        wp_update_post($acc);

    }

    public static function get_account_by_title($term)
    {
        global $wpdb;
        $sql = "SELECT `ID`, `post_title` FROM {$wpdb->posts} WHERE `post_title` LIKE LOWER('%$term%') AND `post_type` = 'wc_crm_accounts'";
        return $wpdb->get_results($sql);
    }

    public static function get_customers_of_account($account_id = 0)
    {
        global $wpdb;
        $sql = "SELECT p1.*
                FROM wp_postmeta p1
                INNER JOIN
                (
                    SELECT max(meta_id) MaxMetaID, meta_value
                    FROM wp_postmeta
                    WHERE meta_key = '_wc_crm_customer_id'
                    GROUP BY meta_value
                ) p2
                  ON p1.meta_value = p2.meta_value AND p1.meta_id = p2.MaxMetaID
                  WHERE meta_key = '_wc_crm_customer_id' and p1.post_id = {$account_id}
                  ORDER BY p1.meta_id desc";
        $result = $wpdb->get_col($sql, 3);
        return $result;
    }
}
