<<<<<<< HEAD
# WhatsApp Vendor Rotator

Plugin WordPress para rotacao automatica de vendedores de WhatsApp usando Round Robin global por clique.

## Visao Geral

Todos os links de WhatsApp do site (`wa.me` e `api.whatsapp.com`) sao interceptados no frontend.  
A cada clique, o plugin consulta o backend e retorna o proximo vendedor da fila global.

Regra principal:

- clique 1 -> vendedor 1
- clique 2 -> vendedor 2
- clique 3 -> vendedor 3
- clique 4 -> vendedor 1

A rotacao **nao muda no carregamento da pagina**, apenas no clique.

## Recursos

- Rotacao Round Robin global.
- Funciona para visitantes logados e nao logados.
- Preserva parametro `text` da URL original.
- Painel admin para adicionar, editar e remover vendedores.
- Persistencia via WordPress Options API.

## Estrutura

```text
whatsapp-vendor-rotator/
├─ whatsapp-vendor-rotator.php
├─ admin/
│  └─ admin-page.php
└─ assets/
   └─ rotator.js
```

## Instalacao

1. Faça upload da pasta `whatsapp-vendor-rotator` para `wp-content/plugins/`, ou envie o `.zip` pelo painel WordPress.
2. Ative o plugin em **Plugins**.
3. Acesse **WhatsApp Rotator** no admin.
4. Cadastre os vendedores com:
   - Nome
   - Numero WhatsApp no formato internacional (ex: `5511999999999`)

## Como Funciona Tecnicamente

### Backend

- Options:
  - `whatsapp_rotator_vendedores`
  - `whatsapp_rotator_index`
- Endpoint AJAX:
  - `action=whatsapp_rotator_next`
  - hooks: `wp_ajax_whatsapp_rotator_next` e `wp_ajax_nopriv_whatsapp_rotator_next`

### Frontend

- Intercepta links com host `wa.me` e `api.whatsapp.com`.
- Chama `admin-ajax.php?action=whatsapp_rotator_next`.
- Recebe numero do vendedor atual.
- Monta URL final:
  - `https://wa.me/NUMERO_DO_VENDEDOR?text=MENSAGEM`

## Compatibilidade de Links

- `https://wa.me/NUMERO`
- `https://wa.me/NUMERO?text=mensagem`
- `https://api.whatsapp.com/send?phone=NUMERO`
- `https://api.whatsapp.com/send?phone=NUMERO&text=mensagem`

## Versao Atual

- `1.2.1`

## Autor

- RetinaWeb

## Licenca

Uso privado/comercial conforme necessidade do projeto.  
Se quiser publicar como open source, recomendo adicionar uma licenca MIT.
