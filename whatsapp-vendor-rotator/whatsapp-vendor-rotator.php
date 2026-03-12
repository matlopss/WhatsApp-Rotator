<?php
/**
 * Plugin Name: WhatsApp Vendor Rotator
 * Description: Rotação automática de vendedores de WhatsApp
 * Version: 1.2.1
 * Author: RetinaWeb
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WHATSAPP_ROTATOR_PLUGIN_FILE', __FILE__);
define('WHATSAPP_ROTATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHATSAPP_ROTATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHATSAPP_ROTATOR_VERSION', '1.2.1');

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
