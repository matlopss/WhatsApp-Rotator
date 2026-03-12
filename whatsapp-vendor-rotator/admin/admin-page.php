<?php
if (!defined('ABSPATH')) {
    exit;
}

$vendedores = get_option('whatsapp_rotator_vendedores', array());
if (!is_array($vendedores)) {
    $vendedores = array();
}
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
