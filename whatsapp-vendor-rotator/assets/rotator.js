(function () {
    'use strict';

    function isWhatsAppLink(href) {
        if (!href) {
            return false;
        }

        try {
            var parsed = new URL(href, window.location.origin);
            var host = parsed.hostname.replace(/^www\./, '').toLowerCase();
            return host === 'wa.me' || host === 'api.whatsapp.com';
        } catch (error) {
            return false;
        }
    }

    function extractTextParam(urlObj) {
        var text = urlObj.searchParams.get('text');
        return text ? text : '';
    }

    function buildDestinationUrl(numero, text) {
        var url = new URL('https://wa.me/' + encodeURIComponent(numero));

        if (text) {
            url.searchParams.set('text', text);
        }

        return url.toString();
    }

    function getAjaxUrl() {
        if (window.WhatsAppRotator && window.WhatsAppRotator.ajaxUrl) {
            return window.WhatsAppRotator.ajaxUrl;
        }

        return '/wp-admin/admin-ajax.php';
    }

    function fetchNextVendor() {
        var endpoint = getAjaxUrl();
        endpoint += (endpoint.indexOf('?') === -1 ? '?' : '&') + 'action=whatsapp_rotator_next';

        return fetch(endpoint, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Falha ao consultar rotacao.');
            }
            return response.json();
        });
    }

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');

        if (!link) {
            return;
        }

        var href = link.getAttribute('href');

        if (!isWhatsAppLink(href)) {
            return;
        }

        event.preventDefault();

        var originalUrl;
        try {
            originalUrl = new URL(href, window.location.origin);
        } catch (error) {
            window.open(href, '_blank', 'noopener');
            return;
        }

        var text = extractTextParam(originalUrl);
        var popup = window.open('about:blank', '_blank');

        // Evita acesso ao contexto da pagina original.
        if (popup) {
            try {
                popup.opener = null;
            } catch (error) {
                // Ignora se o navegador bloquear esta atribuicao.
            }
        }

        fetchNextVendor()
            .then(function (data) {
                if (!data || !data.success || !data.data || !data.data.numero) {
                    throw new Error('Resposta invalida do servidor.');
                }

                var destinationUrl = buildDestinationUrl(data.data.numero, text);
                if (popup && !popup.closed) {
                    popup.location.replace(destinationUrl);
                    return;
                }

                window.open(destinationUrl, '_blank', 'noopener');
            })
            .catch(function () {
                // Fallback em caso de erro: segue para o link original.
                if (popup && !popup.closed) {
                    popup.location.replace(href);
                    return;
                }

                window.location.href = href;
            });
    }, false);
})();
