<?php
if (!defined('ABSPATH')) {
    exit;
}

$vendedores = get_option('whatsapp_rotator_vendedores', array());
if (!is_array($vendedores)) {
    $vendedores = array();
}

$floating_defaults = function_exists('whatsapp_rotator_default_floating_settings')
    ? whatsapp_rotator_default_floating_settings()
    : array(
        'enabled' => 1,
        'desktop_right' => '10px',
        'desktop_bottom' => '45%',
        'mobile_right' => '10px',
        'mobile_bottom' => '10px',
        'message_text' => 'Ola! Quero atendimento.',
    );
$floating_settings = get_option('whatsapp_rotator_floating_settings', array());
if (!is_array($floating_settings)) {
    $floating_settings = array();
}
$floating_settings = wp_parse_args($floating_settings, $floating_defaults);
?>
<div class="wrap">
    <h1>WhatsApp Rotator</h1>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1') : ?>
        <div class="notice notice-success is-dismissible">
            <p>Vendedores atualizados com sucesso.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="whatsapp_rotator_save_vendors" />
        <?php wp_nonce_field('whatsapp_rotator_save_vendors'); ?>

        <table class="widefat striped" id="whatsapp-rotator-table" style="max-width: 900px; margin-top: 20px;">
            <thead>
                <tr>
                    <th style="width: 40%;">Nome</th>
                    <th style="width: 40%;">Numero WhatsApp (5511999999999)</th>
                    <th style="width: 20%;">Acao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vendedores)) : ?>
                    <tr>
                        <td><input type="text" name="vendor_nome[]" class="regular-text" placeholder="Ex: Joao" /></td>
                        <td><input type="text" name="vendor_numero[]" class="regular-text" placeholder="5511999999999" /></td>
                        <td><button type="button" class="button button-link-delete whatsapp-rotator-remove">Remover</button></td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($vendedores as $vendedor) : ?>
                        <tr>
                            <td>
                                <input
                                    type="text"
                                    name="vendor_nome[]"
                                    class="regular-text"
                                    value="<?php echo esc_attr(isset($vendedor['nome']) ? $vendedor['nome'] : ''); ?>"
                                    placeholder="Ex: Joao"
                                />
                            </td>
                            <td>
                                <input
                                    type="text"
                                    name="vendor_numero[]"
                                    class="regular-text"
                                    value="<?php echo esc_attr(isset($vendedor['numero']) ? preg_replace('/\D+/', '', (string) $vendedor['numero']) : ''); ?>"
                                    placeholder="5511999999999"
                                />
                            </td>
                            <td><button type="button" class="button button-link-delete whatsapp-rotator-remove">Remover</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p style="margin-top: 12px;">
            <button type="button" class="button" id="whatsapp-rotator-add">Adicionar vendedor</button>
        </p>

        <hr style="margin: 24px 0;">

        <h2>Icone flutuante do WhatsApp</h2>
        <p>Esse botao usa a mesma roleta de vendedores dos links do site.</p>

        <table class="form-table" role="presentation" style="max-width: 900px;">
            <tbody>
                <tr>
                    <th scope="row">Ativar icone flutuante</th>
                    <td>
                        <label>
                            <input type="checkbox" name="floating_enabled" value="1" <?php checked((int) $floating_settings['enabled'], 1); ?> />
                            Exibir botao flutuante no frontend
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Desktop - Right</th>
                    <td>
                        <input
                            type="text"
                            name="floating_desktop_right"
                            class="regular-text"
                            value="<?php echo esc_attr($floating_settings['desktop_right']); ?>"
                            placeholder="10px"
                        />
                        <p class="description">Exemplo: 10px, 2rem, 5vw</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Desktop - Bottom</th>
                    <td>
                        <input
                            type="text"
                            name="floating_desktop_bottom"
                            class="regular-text"
                            value="<?php echo esc_attr($floating_settings['desktop_bottom']); ?>"
                            placeholder="45%"
                        />
                        <p class="description">Exemplo: 45%, 80px</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mobile - Right</th>
                    <td>
                        <input
                            type="text"
                            name="floating_mobile_right"
                            class="regular-text"
                            value="<?php echo esc_attr($floating_settings['mobile_right']); ?>"
                            placeholder="10px"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mobile - Bottom</th>
                    <td>
                        <input
                            type="text"
                            name="floating_mobile_bottom"
                            class="regular-text"
                            value="<?php echo esc_attr($floating_settings['mobile_bottom']); ?>"
                            placeholder="10px"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Mensagem padrao</th>
                    <td>
                        <input
                            type="text"
                            name="floating_message_text"
                            class="regular-text"
                            value="<?php echo esc_attr($floating_settings['message_text']); ?>"
                            placeholder="Ola! Quero atendimento."
                        />
                        <p class="description">Essa mensagem vai no parametro text do link do botao.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">Salvar vendedores</button>
        </p>
    </form>
</div>

<script>
(function () {
    var tableBody = document.querySelector('#whatsapp-rotator-table tbody');
    var addButton = document.getElementById('whatsapp-rotator-add');

    if (!tableBody || !addButton) {
        return;
    }

    function removeRow(button) {
        var row = button.closest('tr');
        if (!row) {
            return;
        }

        row.remove();

        if (tableBody.children.length === 0) {
            addRow();
        }
    }

    function addRow() {
        var tr = document.createElement('tr');
        tr.innerHTML = '' +
            '<td><input type="text" name="vendor_nome[]" class="regular-text" placeholder="Ex: Joao" /></td>' +
            '<td><input type="text" name="vendor_numero[]" class="regular-text" placeholder="5511999999999" /></td>' +
            '<td><button type="button" class="button button-link-delete whatsapp-rotator-remove">Remover</button></td>';
        tableBody.appendChild(tr);
    }

    addButton.addEventListener('click', function () {
        addRow();
    });

    tableBody.addEventListener('click', function (event) {
        if (event.target && event.target.classList.contains('whatsapp-rotator-remove')) {
            removeRow(event.target);
        }
    });
})();
</script>
