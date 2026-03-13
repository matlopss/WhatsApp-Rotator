<?php
/**
 * Plugin Name: WhatsApp Vendor Rotator
 * Description: Rotação automática de vendedores de WhatsApp
 * Version: 1.3.0
 * Author: RetinaWeb
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WHATSAPP_ROTATOR_PLUGIN_FILE', __FILE__);
define('WHATSAPP_ROTATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATSAPP_ROTATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHATSAPP_ROTATOR_VERSION', '1.3.0');

/**
 * Defaults das configuracoes do botao flutuante.
 */
function whatsapp_rotator_default_floating_settings()
{
    return array(
        'enabled' => 1,
        'desktop_right' => '10px',
        'desktop_bottom' => '45%',
        'mobile_right' => '10px',
        'mobile_bottom' => '10px',
        'message_text' => 'Ola! Quero atendimento.',
    );
}

/**
 * Permite apenas medidas CSS simples (px, %, rem, em, vw, vh).
 */
function whatsapp_rotator_sanitize_css_size($value, $fallback)
{
    $value = strtolower(trim((string) $value));

    if (preg_match('/^-?\\d+(\\.\\d+)?(px|%|rem|em|vw|vh)$/', $value)) {
        return $value;
    }

    return $fallback;
}

/**
 * Recupera configuracoes do botao flutuante com fallback de defaults.
 */
function whatsapp_rotator_get_floating_settings()
{
    $defaults = whatsapp_rotator_default_floating_settings();
    $saved = get_option('whatsapp_rotator_floating_settings', array());

    if (!is_array($saved)) {
        $saved = array();
    }

    return wp_parse_args($saved, $defaults);
}

/**
 * Cria as options iniciais no momento da ativação.
 */
function whatsapp_rotator_activate()
{
    if (get_option('whatsapp_rotator_vendedores', null) === null) {
        add_option('whatsapp_rotator_vendedores', array());
    }

    if (get_option('whatsapp_rotator_index', null) === null) {
        add_option('whatsapp_rotator_index', 0);
    }

    if (get_option('whatsapp_rotator_floating_settings', null) === null) {
        add_option('whatsapp_rotator_floating_settings', whatsapp_rotator_default_floating_settings());
    }
}
register_activation_hook(__FILE__, 'whatsapp_rotator_activate');

/**
 * Registra menu administrativo.
 */
function whatsapp_rotator_admin_menu()
{
    add_menu_page(
        'WhatsApp Rotator',
        'WhatsApp Rotator',
        'manage_options',
        'whatsapp-rotator',
        'whatsapp_rotator_render_admin_page',
        'dashicons-whatsapp',
        56
    );
}
add_action('admin_menu', 'whatsapp_rotator_admin_menu');

/**
 * Processa envio do formulário e salva vendedores.
 */
function whatsapp_rotator_handle_admin_post()
{
    if (!current_user_can('manage_options')) {
        wp_die('Permissao negada.');
    }

    check_admin_referer('whatsapp_rotator_save_vendors');

    $nomes = isset($_POST['vendor_nome']) && is_array($_POST['vendor_nome']) ? $_POST['vendor_nome'] : array();
    $numeros = isset($_POST['vendor_numero']) && is_array($_POST['vendor_numero']) ? $_POST['vendor_numero'] : array();

    $vendedores = array();
    $total = max(count($nomes), count($numeros));

    for ($i = 0; $i < $total; $i++) {
        $nome = isset($nomes[$i]) ? sanitize_text_field(wp_unslash($nomes[$i])) : '';
        $numero_raw = isset($numeros[$i]) ? wp_unslash($numeros[$i]) : '';

        // Mantem somente digitos para padrao WhatsApp internacional.
        $numero = preg_replace('/\D+/', '', $numero_raw);

        if ($nome === '' && $numero === '') {
            continue;
        }

        if ($numero === '') {
            continue;
        }

        $vendedores[] = array(
            'nome' => $nome,
            'numero' => $numero,
        );
    }

    update_option('whatsapp_rotator_vendedores', $vendedores);

    // Garante que o indice fique valido apos alteracoes.
    $indice_atual = (int) get_option('whatsapp_rotator_index', 0);
    $total_vendedores = count($vendedores);

    if ($total_vendedores <= 0) {
        $indice_atual = 0;
    } elseif ($indice_atual >= $total_vendedores) {
        $indice_atual = 0;
    }

    update_option('whatsapp_rotator_index', $indice_atual);

    $floating_defaults = whatsapp_rotator_default_floating_settings();
    $floating_settings = array(
        'enabled' => isset($_POST['floating_enabled']) ? 1 : 0,
        'desktop_right' => whatsapp_rotator_sanitize_css_size(
            isset($_POST['floating_desktop_right']) ? wp_unslash($_POST['floating_desktop_right']) : '',
            $floating_defaults['desktop_right']
        ),
        'desktop_bottom' => whatsapp_rotator_sanitize_css_size(
            isset($_POST['floating_desktop_bottom']) ? wp_unslash($_POST['floating_desktop_bottom']) : '',
            $floating_defaults['desktop_bottom']
        ),
        'mobile_right' => whatsapp_rotator_sanitize_css_size(
            isset($_POST['floating_mobile_right']) ? wp_unslash($_POST['floating_mobile_right']) : '',
            $floating_defaults['mobile_right']
        ),
        'mobile_bottom' => whatsapp_rotator_sanitize_css_size(
            isset($_POST['floating_mobile_bottom']) ? wp_unslash($_POST['floating_mobile_bottom']) : '',
            $floating_defaults['mobile_bottom']
        ),
        'message_text' => isset($_POST['floating_message_text'])
            ? sanitize_text_field(wp_unslash($_POST['floating_message_text']))
            : $floating_defaults['message_text'],
    );

    update_option('whatsapp_rotator_floating_settings', $floating_settings);

    $redirect_url = add_query_arg(
        array(
            'page' => 'whatsapp-rotator',
            'updated' => '1',
        ),
        admin_url('admin.php')
    );

    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_whatsapp_rotator_save_vendors', 'whatsapp_rotator_handle_admin_post');

/**
 * Inclui arquivo da pagina administrativa.
 */
function whatsapp_rotator_render_admin_page()
{
    require WHATSAPP_ROTATOR_PLUGIN_DIR . 'admin/admin-page.php';
}

/**
 * Carrega script do frontend para interceptar links de WhatsApp.
 */
function whatsapp_rotator_enqueue_scripts()
{
    wp_enqueue_script(
        'whatsapp-rotator-js',
        WHATSAPP_ROTATOR_PLUGIN_URL . 'assets/rotator.js',
        array(),
        WHATSAPP_ROTATOR_VERSION,
        true
    );

    wp_localize_script(
        'whatsapp-rotator-js',
        'WhatsAppRotator',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
        )
    );
}
add_action('wp_enqueue_scripts', 'whatsapp_rotator_enqueue_scripts');

/**
 * Renderiza botao flutuante de WhatsApp no frontend.
 */
function whatsapp_rotator_render_floating_button()
{
    $settings = whatsapp_rotator_get_floating_settings();

    if (empty($settings['enabled'])) {
        return;
    }

    $message = isset($settings['message_text']) ? trim((string) $settings['message_text']) : '';
    $href = 'https://wa.me/0000000000000';
    if ($message !== '') {
        $href .= '?text=' . rawurlencode($message);
    }
    ?>
    <style>
        .whatsapp-rotator-floating-button {
            position: fixed;
            right: <?php echo esc_html($settings['desktop_right']); ?>;
            bottom: <?php echo esc_html($settings['desktop_bottom']); ?>;
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            line-height: 0;
            border-radius: 999px;
            text-decoration: none;
        }
        .whatsapp-rotator-floating-button svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        @media (max-width: 767px) {
            .whatsapp-rotator-floating-button {
                right: <?php echo esc_html($settings['mobile_right']); ?>;
                bottom: <?php echo esc_html($settings['mobile_bottom']); ?>;
            }
        }
    </style>
    <a
        class="whatsapp-rotator-floating-button"
        href="<?php echo esc_url($href); ?>"
        target="_blank"
        rel="noopener"
        aria-label="Conversar no WhatsApp"
    >
        <svg style="pointer-events:none; display:block; height:50px; width:50px;" width="50px" height="50px" viewBox="0 0 1219.547 1225.016" aria-hidden="true" focusable="false">
            <path style="fill:#E0E0E0;" d="M1041.858 178.02C927.206 63.289 774.753.07 612.325 0 277.617 0 5.232 272.298 5.098 606.991c-.039 106.986 27.915 211.42 81.048 303.476L0 1225.016l321.898-84.406c88.689 48.368 188.547 73.855 290.166 73.896h.258.003c334.654 0 607.08-272.346 607.222-607.023.056-162.208-63.052-314.724-177.689-429.463zm-429.533 933.963h-.197c-90.578-.048-179.402-24.366-256.878-70.339l-18.438-10.93-191.021 50.083 51-186.176-12.013-19.087c-50.525-80.336-77.198-173.175-77.16-268.504.111-278.186 226.507-504.503 504.898-504.503 134.812.056 261.519 52.604 356.814 147.965 95.289 95.36 147.728 222.128 147.688 356.948-.118 278.195-226.522 504.543-504.693 504.543z"></path>
            <linearGradient id="whatsapp-rotator-gradient" gradientUnits="userSpaceOnUse" x1="609.77" y1="1190.114" x2="609.77" y2="21.084">
                <stop offset="0" stop-color="#20b038"></stop>
                <stop offset="1" stop-color="#60d66a"></stop>
            </linearGradient>
            <path style="fill:url(#whatsapp-rotator-gradient);" d="M27.875 1190.114l82.211-300.18c-50.719-87.852-77.391-187.523-77.359-289.602.133-319.398 260.078-579.25 579.469-579.25 155.016.07 300.508 60.398 409.898 169.891 109.414 109.492 169.633 255.031 169.57 409.812-.133 319.406-260.094 579.281-579.445 579.281h-.258c-96.977-.031-192.266-24.375-276.898-70.5l-307.188 80.548z"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" style="fill:#FFFFFF;" d="M462.273 349.294c-11.234-24.977-23.062-25.477-33.75-25.914-8.742-.375-18.75-.352-28.742-.352-10 0-26.25 3.758-39.992 18.766-13.75 15.008-52.5 51.289-52.5 125.078 0 73.797 53.75 145.102 61.242 155.117 7.5 10 103.758 166.266 256.203 226.383 126.695 49.961 152.477 40.023 179.977 37.523s88.734-36.273 101.234-71.297c12.5-35.016 12.5-65.031 8.75-71.305-3.75-6.25-13.75-10-28.75-17.5s-88.734-43.789-102.484-48.789-23.75-7.5-33.75 7.516c-10 15-38.727 48.773-47.477 58.773-8.75 10.023-17.5 11.273-32.5 3.773-15-7.523-63.305-23.344-120.609-74.438-44.586-39.75-74.688-88.844-83.438-103.859-8.75-15-.938-23.125 6.586-30.602 6.734-6.719 15-17.508 22.5-26.266 7.484-8.758 9.984-15.008 14.984-25.008 5-10.016 2.5-18.773-1.25-26.273s-32.898-81.67-46.234-111.326z"></path>
            <path style="fill:#FFFFFF;" d="M1036.898 176.091C923.562 62.677 772.859.185 612.297.114 281.43.114 12.172 269.286 12.039 600.137 12 705.896 39.633 809.13 92.156 900.13L7 1211.067l318.203-83.438c87.672 47.812 186.383 73.008 286.836 73.047h.255c330.812 0 600.109-269.219 600.25-600.055.055-160.343-62.328-311.108-175.649-424.53zm-424.601 923.242h-.195c-89.539-.047-177.344-24.086-253.93-69.531l-18.227-10.805-188.828 49.508 50.414-184.039-11.875-18.867c-49.945-79.414-76.312-171.188-76.273-265.422.109-274.992 223.906-498.711 499.102-498.711 133.266.055 258.516 52 352.719 146.266 94.195 94.266 146.031 219.578 145.992 352.852-.118 274.999-223.923 498.749-498.899 498.749z"></path>
        </svg>
    </a>
    <?php
}
add_action('wp_footer', 'whatsapp_rotator_render_floating_button');

/**
 * Endpoint AJAX que retorna o proximo vendedor seguindo Round Robin global.
 */
function whatsapp_rotator_next_vendor()
{
    $vendedores = get_option('whatsapp_rotator_vendedores', array());

    if (!is_array($vendedores) || empty($vendedores)) {
        wp_send_json_error(
            array('message' => 'Nenhum vendedor cadastrado.'),
            404
        );
    }

    $total = count($vendedores);
    $index = (int) get_option('whatsapp_rotator_index', 0);

    if ($index < 0 || $index >= $total) {
        $index = 0;
    }

    $vendedor = $vendedores[$index];
    $numero = isset($vendedor['numero']) ? preg_replace('/\D+/', '', (string) $vendedor['numero']) : '';

    if ($numero === '') {
        wp_send_json_error(
            array('message' => 'Vendedor atual sem numero valido.'),
            500
        );
    }

    $next_index = $index + 1;
    if ($next_index >= $total) {
        $next_index = 0;
    }

    update_option('whatsapp_rotator_index', $next_index);

    wp_send_json_success(
        array(
            'numero' => $numero,
        )
    );
}
add_action('wp_ajax_whatsapp_rotator_next', 'whatsapp_rotator_next_vendor');
add_action('wp_ajax_nopriv_whatsapp_rotator_next', 'whatsapp_rotator_next_vendor');
