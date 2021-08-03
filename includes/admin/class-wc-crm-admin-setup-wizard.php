<?php
/**
 * Setup Wizard Class
 *
 * Conduz os novos usuários por meio de algumas etapas básicas para configurar sua loja.
 *
 * @author     Adailton Machado
 * @category    Admin
 * @package     WC_CRM/Admin
 * @version     1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_CRM_Admin_Setup_Wizard class
 */
class WC_CRM_Admin_Setup_Wizard
{

    /** @var string Etapa Atual */
    private $step = '';

    /** @var array Etapas para o assistente de configuração */
    private $steps = array();


    /**
     * Hook em abas.
     */
    public function __construct()
    {
        if (current_user_can('manage_woocommerce')) {
            add_action('admin_menu', array($this, 'admin_menus'));
            add_action('admin_init', array($this, 'setup_wizard'));
        }
    }

    /**
     * Adicione menus / telas de administração.
     */
    public function admin_menus()
    {
        add_dashboard_page('', '', 'manage_options', WC_CRM_TOKEN . '-setup', '');
    }

    /**
     * Mostra o assistente de configuração
     */
    public function setup_wizard()
    {
        wc_crm_clear_transient();
        if (empty($_GET['page']) || WC_CRM_TOKEN . '-setup' !== $_GET['page']) {
            return;
        }
        $this->steps = array(
            'introduction' => array(
                'name' => __('Introdução', 'wc_crm'),
                'view' => array($this, 'wc_crm_setup_introduction'),
                'handler' => ''
            ),
            'general_options' => array(
                'name' => __('Opções gerais', 'wc_crm'),
                'view' => array($this, 'wc_crm_setup_general_options'),
                'handler' => array($this, 'wc_crm_setup_general_options_save')
            ),
            'fetch_customers' => array(
                'name' => __('Buscar clientes', 'wc_crm'),
                'view' => array($this, 'wc_crm_setup_fetch_customers'),
                'handler' => array($this, 'wc_crm_setup_fetch_customers_save')
            ),
            'load_customers' => array(
                'name' => __('Carregar clientes', 'wc_crm'),
                'view' => array($this, 'wc_crm_setup_load_customers'),
                'handler' => array($this, 'wc_crm_setup_load_customers_save')
            ),
            'next_steps' => array(
                'name' => __('Finalizado!', 'wc_crm'),
                'view' => array($this, 'wc_crm_setup_ready'),
                'handler' => ''
            )
        );
        $this->step = isset($_GET['step']) ? sanitize_key($_GET['step']) : current(array_keys($this->steps));
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_register_script('jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array('jquery'), '2.70', true);
        wp_register_script('select2', WC()->plugin_url() . '/assets/js/select2/select2' . $suffix . '.js', array('jquery'), '3.5.2');
        wp_enqueue_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array('jquery', 'select2'), WC_VERSION);

        wp_localize_script('wc-enhanced-select', 'wc_enhanced_select_params', array(
            'i18n_matches_1' => _x('Um resultado está disponível, pressione Enter para selecioná-lo.', 'enhanced select', 'wc_crm'),
            'i18n_matches_n' => _x('%qty% resultados estão disponíveis, use as teclas de seta para cima e para baixo para navegar.', 'enhanced select', 'wc_crm'),
            'i18n_no_matches' => _x('Nenhuma correspondência encontrada ',' seleção aprimorada', 'wc_crm'),
            'i18n_ajax_error' => _x('Falha ao carregar ',' seleção aprimorada ', 'wc_crm'),
            'i18n_input_too_short_1' => _x('Por favor, insira 1 ou mais caracteres ',' seleção avançada', 'wc_crm'),
            'i18n_input_too_short_n' => _x('Por favor, digite% qty% ou mais caracteres ',' seleção aprimorada ', 'wc_crm'),
            'i18n_input_too_long_1' => _x('Por favor, exclua 1 caractere', 'seleção aprimorada', 'wc_crm'),
            'i18n_input_too_long_n' => _x('Por favor, exclua% qty% caracteres', 'seleção aprimorada', 'wc_crm'),
            'i18n_selection_too_long_1' => _x('Você só pode selecionar 1 item', 'seleção aprimorada', 'wc_crm'),
            'i18n_selection_too_long_n' => _x('Você só pode selecionar% qty% itens', 'seleção aprimorada', 'wc_crm'),
            'i18n_load_more' => _x('Carregando mais resultados & hellip; ',' seleção aprimorada ', 'wc_crm'),
            'i18n_searching' => _x('Pesquisando & hellip;', 'seleção aprimorada', 'wc_crm'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'search_products_nonce' => wp_create_nonce('search-products'),
            'search_customers_nonce' => wp_create_nonce('search-customers')
        ));
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION);
        wp_enqueue_style(WC_CRM_TOKEN . '-setup', esc_url(WC_CRM()->assets_url) . 'css/wc-crm-setup.css', array('dashicons', 'install'), WC_VERSION);
        wp_enqueue_script(WC_CRM_TOKEN . '-setup-js', esc_url(WC_CRM()->assets_url) . 'js/wc-crm-setup.js', array('jquery', 'select2'));

        wp_register_script(WC_CRM_TOKEN . '-setup', WC()->plugin_url() . '/assets/js/admin/wc-setup.min.js', array('jquery', 'wc-enhanced-select', 'jquery-blockui', 'jquery-ui-progressbar', 'jquery-tiptip'), WC_VERSION);
        wp_localize_script(WC_CRM_TOKEN . '-setup', 'wc_setup_params', array(
            'locale_info' => json_encode(include(WC()->plugin_path() . '/i18n/locale-info.php'))
        ));

        if (!empty($_POST['save_step']) && isset($this->steps[$this->step]['handler'])) {
            call_user_func($this->steps[$this->step]['handler']);
        }

        header('Content-Type: text/html; charset=utf-8');
        ob_start();
        $this->setup_wizard_header();
        if ($this->step != 'update_customers') {
            $this->setup_wizard_steps();
            $this->setup_wizard_content();
        } else {
            $this->wc_crm_setup_update_customers();
        }
        $this->setup_wizard_footer();
        exit;
    }

    public function get_next_step_link()
    {
        $keys = array_keys($this->steps);
        return add_query_arg('step', $keys[array_search($this->step, array_keys($this->steps)) + 1], remove_query_arg('translation_updated'));
    }

    /**
     * Setup Wizard Header
     */
    public function setup_wizard_header()
    {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width"/>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title><?php _e('WooCommerce &rsaquo; Setup Wizard', 'wc_crm'); ?></title>
            <?php wp_print_scripts(array(
                'jquery-ui-progressbar',
                'jquery-blockui',
                WC_CRM_TOKEN . '-setup',
                WC_CRM_TOKEN . '-setup-js'
            )); ?>
            <?php do_action('admin_print_styles'); ?>
            <?php #do_action( 'admin_head' );
            ?>
        </head>
        <body class="wc-setup wp-core-ui">
        <h2 id="logos">
            <img id="ae-logo" src="<?php echo esc_url(WC_CRM()->assets_url); ?>img/ae-logo.svg"
                 alt="Actuality Extensions"/>
        </h2>
        <?php
    }

    /**
     * Setup Wizard Footer
     */
    public function setup_wizard_footer()
    {
        ?>
        <a class="wc-return-to-dashboard"
           href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php _e('Not right now', 'wc_crm'); ?></a>
        <?php
    }

    /**
     * Output the steps
     */
    public function setup_wizard_steps()
    {
        $ouput_steps = $this->steps;
        array_shift($ouput_steps);
        ?>
        <ol class="wc-setup-steps">
            <?php foreach ($ouput_steps as $step_key => $step) : ?>
                <li class="<?php
                if ($step_key === $this->step) {
                    echo 'active';
                } elseif (array_search($this->step, array_keys($this->steps)) > array_search($step_key, array_keys($this->steps))) {
                    echo 'done';
                }
                ?>"><?php echo esc_html($step['name']); ?></li>
            <?php endforeach; ?>
        </ol>
        <?php
    }

    /**
     * Output the content for the current step
     */
    public function setup_wizard_content()
    {
        echo '<div class="wc-setup-content">';
        call_user_func($this->steps[$this->step]['view']);
        echo '</div>';
    }

    /**
     * Introduction step
     */
    public function wc_crm_setup_introduction()
    {
        ?>
        <h1><?php _e('Bem vindo Ao Consulta Rebusta MobSale', 'wc_crm'); ?></h1>
        <p><?php _e('Obrigado por escolher oConsulta Rebusta MobSale para gerenciar seus clientes! Este assistente de configuração rápida irá ajudá-lo a definir as configurações básicas.', 'wc_crm'); ?></p>
        <p><?php _e('Sem tempo agora? Se você não quiser seguir o assistente, pode pular e retornar ao painel do WordPress. Volte a qualquer hora se mudar de ideia!', 'wc_crm'); ?></p>
        <p class="wc-setup-actions step">
            <a href="<?php echo esc_url($this->get_next_step_link()); ?>"
               class="button-primary button button-large button-next"><?php _e('Vamos!', 'wc_crm'); ?></a>
        </p>
        <?php
    }


    /**
     * General settings
     */
    public function wc_crm_setup_general_options()
    {

        // Defaults
        $username = get_option('wc_crm_username_add_customer', 1);
        $total_value = get_option('wc_crm_total_value', array('wc-completed'));
        ?>
        <h1><?php _e('Opções gerais', 'wc_crm'); ?></h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php echo _e('Nome de usuário', 'wc_crm'); ?></th>
                    <td>
                        <select style="width: 100%;" id="wc_crm_username_add_customer" name="wc_crm_username_add_customer">
                            <option value="1" <?php selected($username, 1); ?>><?php echo __('Nome e sobrenome, por exemplo joão maria', 'wc_crm'); ?></option>
                            <option value="2" <?php selected($username, 2); ?>><?php echo __('Separado por hífen, por ex. joao-maria', 'wc_crm'); ?></option>
                            <option value="3" <?php selected($username, 3); ?>><?php echo __('Email', 'wc_crm'); ?></option>
                        </select>
                        <span class="description">
							<?php _e('Escolha o nome de usuário quando os clientes forem adicionados.', 'wc_crm'); ?>
						</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo _e('Dinheiro gasto', 'wc_crm'); ?></th>
                    <td>
                        <select id="wc_crm_total_value" name="wc_crm_total_value[]" required style="width:100%;"
                                multiple="true"
                                data-placeholder="<?php esc_attr_e('Choose a statuses&hellip;', 'wc_crm'); ?>">
                            <option value=""><?php _e('Escolha um status&hellip;', 'wc_crm'); ?></option>
                            <?php
                            foreach (wc_get_order_statuses() as $slug => $name) {
                                echo '<option value="' . esc_attr($slug) . '" ' . selected(in_array($slug, $total_value), true, false) . '>' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                        <span class="description">
							<?php _e('Escolha em que status os pedidos devem estar antes de contar para o valor do dinheiro gasto.', 'wc_crm'); ?>
						</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="wc_crm_automatic_emails_new_customer"><?php echo _e('Emails Automaticos ', 'wc_crm'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox"
                               id="wc_crm_automatic_emails_new_customer" <?php checked(get_option('wc_crm_automatic_emails_new_customer', 'yes'), 'yes'); ?>
                               name="wc_crm_automatic_emails_new_customer" class="input-checkbox" value="1"/>
                        <label for="wc_crm_automatic_emails_new_customer"><?php _e('Marque esta caixa para enviar um e-mail com nome de usuário e senha ao criar um novo cliente.', 'wc_crm'); ?></label>
                    </td>
                </tr>
            </table>
            <p class="wc-setup-actions step">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e('Continue', 'wc_crm'); ?>" name="save_step"/>
                <?php wp_nonce_field(WC_CRM_TOKEN . '-setup'); ?>
            </p>
        </form>
        <?php
    }

    /**
     * Save Locale Settings
     */
    public function wc_crm_setup_general_options_save()
    {
        check_admin_referer(WC_CRM_TOKEN . '-setup');

        $username = $_POST['wc_crm_username_add_customer'];
        $total_value = $_POST['wc_crm_total_value'];
        $automatic_emails = isset($_POST['wc_crm_automatic_emails_new_customer']) ? 'yes' : 'no';

        update_option('wc_crm_username_add_customer', $username);
        update_option('wc_crm_total_value', $total_value);
        update_option('wc_crm_automatic_emails_new_customer', $automatic_emails);

        wp_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Fetch customers setup
     */
    public function wc_crm_setup_fetch_customers()
    {
        global $wp_roles;

        $user_roles = get_option('wc_crm_user_roles', array('customer'));
        $customer_name = get_option('wc_crm_customer_name', 'fl');
        ?>
        <h1><?php _e('Fetch Customers', 'wc_crm'); ?></h1>
        <form method="post">
            <p><?php _e('As opções a seguir afetam como os clientes na tabela de clientes devem ser buscados', 'wc_crm'); ?></p>
            <table class="form-table" cellspacing="0">
                <tbody>
                <tr>
                    <th scope="row"><?php echo _e('User Roles', 'wc_crm'); ?></th>
                    <td>
                        <select id="wc_crm_user_roles" name="wc_crm_user_roles[]" required style="width:100%;"
                                multiple="true"
                                data-placeholder="<?php esc_attr_e('Escolha um roles&hellip;', 'wc_crm'); ?>"
                                class="wc-enhanced-select">
                            <option value=""><?php _e('Escolha um role&hellip;', 'wc_crm'); ?></option>
                            <?php
                            foreach ($wp_roles->role_names as $role => $name) {
                                echo '<option value="' . esc_attr($role) . '" ' . selected(in_array($role, $user_roles), true, false) . '>' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                        <span class="description">
								<?php _e('Escolha quais funções de usuário dos clientes / usuários serão mostradas na tabela de clientes.', 'wc_crm'); ?>
							</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label
                                for="wc_crm_guest_customers"><?php echo _e('Guest Customers', 'wc_crm'); ?></label></th>
                    <td>
                        <input type="checkbox"
                               id="wc_crm_guest_customers" <?php checked(get_option('wc_crm_guest_customers', 'no'), 'yes'); ?>
                               name="wc_crm_guest_customers" class="input-checkbox" value="1"/>
                        <label for="wc_crm_guest_customers"><?php _e('Selecione se os clientes Convidados aparecem na mesa de clientes', 'wc_crm'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo _e('Formato do nome do cliente', 'wc_crm'); ?></th>
                    <td>
                        <select style="width: 100%;" id="wc_crm_customer_name" name="wc_crm_customer_name" class="wc-enhanced-select">
                            <option value="fl" <?php selected($customer_name, 'fl'); ?>><?php echo __('First, Last', 'wc_crm'); ?></option>
                            <option value="lf" <?php selected($customer_name, 'lf'); ?>><?php echo __('Last, First', 'wc_crm'); ?></option>
                        </select>
                        <span class="description">
								<?php _e('Escolha o formato dos nomes exibidos na página Clientes.', 'wc_crm'); ?>
							</span>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="wc-setup-actions step">
                <input type="submit" class="button-primary button button-large button-next"
                       value="<?php esc_attr_e('Continue', 'wc_crm'); ?>" name="save_step"/>
            </p>
        </form>
        <?php
    }

    /**
     * Fetch customers Settings
     */
    public function wc_crm_setup_fetch_customers_save()
    {
        $user_roles = $_POST['wc_crm_user_roles'];
        $guest_customers = isset($_POST['wc_crm_guest_customers']) ? 'sim' : 'não';
        $customer_name = $_POST['wc_crm_customer_name'];

        update_option('wc_crm_user_roles', $user_roles);
        update_option('wc_crm_guest_customers', $guest_customers);
        update_option('wc_crm_customer_name', $customer_name);

        wp_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }

    /**
     * Fetch customers setup
     */
    public function wc_crm_setup_load_customers()
    {
        ?>
        <h1><?php _e('Carregar clientes', 'wc_crm'); ?></h1>
        <p><?php _e("Por favor, seja paciente enquanto os clientes são carregados. Você será notificado por meio desta página quando o carregamento for concluído.", 'wc_crm'); ?></p>
        <noscript><p><em><?php _e("Você deve habilitar o Javascript para continuar!", 'wc_crm'); ?></em></p></noscript>

        <?php include_once 'views/html-reload-customers.php'; ?>

        <a href="#" class="button" id="togle_options"><?php _e('Opções avançadas', 'wc_crm'); ?></a>
        <table class="form-table" cellspacing="0" style="display: none;" id="advanced_options">
            <tbody>
            <tr>
                <th scope="row"><?php _e('Desvio', 'wc_crm'); ?></th>
                <td>
                    <input type="number" step="1" min="0" id="offset" style="width:100px;">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Limite', 'wc_crm'); ?></th>
                <td>
                    <input type="number" step="1" min="0" id="limit" style="width:100px;">
                </td>
            </tr>
            </tbody>
        </table>
        <p class="wc-setup-actions step" id="load_customers_buttons">
            <input type="button" id="force_reload_customers" name="force_reload_customers"
                   class="button-primary hide-if-no-js button-large"
                   value="<?php esc_attr_e('Start load', 'wc_crm'); ?>"/>
            <a href="<?php echo esc_url($this->get_next_step_link()); ?>"
               class="hidden button-primary button button-large button-next"><?php _e('Continue', 'wc_crm'); ?></a>
        </p>
        <?php
    }

    /**
     * Fetch customers setup
     */
    public function wc_crm_setup_update_customers()
    {
        ?>
        <div class="wc-setup-content">
            <h1><?php _e('Editar Cliente', 'wc_crm'); ?></h1>
            <p><?php _e("Por favor, seja paciente enquanto os clientes são carregados. Você será notificado por meio desta página quando o carregamento for concluído.", 'wc_crm'); ?></p>
            <noscript><p><em><?php _e("Você deve habilitar o Javascript para continuar!", 'wc_crm'); ?></em></p>
            </noscript>

            <?php include_once 'views/html-reload-customers.php'; ?>

            <a href="#" class="button" id="togle_options"><?php _e('Opções Avançadas', 'wc_crm'); ?></a>
            <table class="form-table" cellspacing="0" style="display: none;" id="advanced_options">
                <tbody>
                <tr>
                    <th scope="row"><?php _e('Desvio', 'wc_crm'); ?></th>
                    <td>
                        <input type="number" step="1" min="0" id="offset" style="width:100px;">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Limite', 'wc_crm'); ?></th>
                    <td>
                        <input type="number" step="1" min="0" id="limit" style="width:100px;">
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="wc-setup-actions step" id="load_customers_buttons">
                <input type="button" id="force_reload_customers" name="force_reload_customers"
                       class="button-primary hide-if-no-js button-large"
                       value="<?php esc_attr_e('Start load', 'wc_crm'); ?>"/>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . WC_CRM_TOKEN)); ?>"
                   class="hidden button-primary button button-large button-next"><?php _e('Return to the customers list', 'wc_crm'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Fetch customers Settings
     */
    public function wc_crm_setup_load_customers_save()
    {
        wp_redirect(esc_url_raw($this->get_next_step_link()));
        exit;
    }


    /**
     * Final step
     */
    public function wc_crm_setup_ready()
    {
        $user = wp_get_current_user();
        WC_CRM_Admin_Notices::remove_notice('crm_install');
        update_option('wc_crm_customers_loaded', 'sim');
        ?>

        <h1><?php esc_html_e( "Você está pronto para começar a gerenciar seus clientes!", 'wc_crm' ); ?></h1>
		
		<div class="woocommerce-message woocommerce-newsletter">
			<p><?php esc_html_e( "
Mantenha-se atualizado - obtenha as últimas correções e atualizações de recursos diretamente em sua caixa de correio.", 'wc_crm' ); ?></p>
			<form action="//actualityextensions.us7.list-manage.com/subscribe?u=d360506c406997bb1eb300ec9&id=1906859e78" method="post" target="_blank" novalidate>
				<div class="newsletter-form-container">
					<input
						class="newsletter-form-email"
						type="email"
						value="<?php echo esc_attr( $user->user_email ); ?>"
						name="EMAIL"
						placeholder="<?php esc_attr_e( 'Email', 'woocommerce' ); ?>"
						required
					>
					<p class="wc-setup-actions step newsletter-form-button-container">
						<button
							type="submit"
							value="<?php esc_html_e( 'Sim, Por Favor', 'woocommerce' ); ?>"
							name="subscribe"
							id="mc-embedded-subscribe"
							class="button-primary button newsletter-form-button"
						><?php esc_html_e( 'Sim, Por Favor', 'woocommerce' ); ?></button>
					</p>
				</div>
			</form>
		</div>
		<ul class="wc-wizard-next-steps">
			<li class="wc-wizard-next-step-item">
				<div class="wc-wizard-next-step-description">
					<p class="next-step-heading"><?php _e('Próxima Etapa', 'wc_crm'); ?></p>
					<h3 class="next-step-description"><?php _e('Interaja com seus clientes', 'wc_crm'); ?></h3>
					<p class="next-step-extra-info"><?php esc_html_e( "Você está pronto para gerenciar seus clientes.", 'wc_crm' ); ?></p>
				</div>
				<div class="wc-wizard-next-step-action">
					<p class="wc-setup-actions step">
						<a class="button button-primary button-large"
                                                 href="<?php echo esc_url(admin_url('admin.php?page=' . WC_CRM_TOKEN)); ?>"><?php _e('Gerencie seus clientes', 'wc_crm'); ?></a>
					</p>
				</div>
			</li>
		</ul>
        <div class="wc-setup-next-steps">
            <div class="wc-setup-next-steps-last">
                <ul>
                    <li class="learn-more"><a href="http://actualityextensions.com/documentation/"
                                              target="_blank"><?php _e('Leia mais sobre como começar', 'wc_crm'); ?></a>
                    </li>
                    <li class="shop-more"><a href="http://codecanyon.net/user/actualityextensions/portfolio/"
                                             target="_blank"><?php _e('Explore nossas outras extensões poderosas', 'wc_crm'); ?></a>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }
}

new WC_CRM_Admin_Setup_Wizard();
