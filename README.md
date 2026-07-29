# Allhands Hotel — CMS (Site do Hotel)

Este repositório é o **site/CMS do hotel** do projeto **Allhands Hotel**. É um fork do [Atom CMS](https://github.com/ObjectRetros/atomcms), construído em Laravel, responsável pela parte "web" do hotel: cadastro/login de usuários, catálogo de mobílias e roupas visível no navegador, loja, comunidade, ranking, sistema de suporte, painel administrativo e integração via RCON com o servidor do jogo.

## Sobre o projeto Allhands Hotel

O Allhands Hotel é composto por 5 repositórios que trabalham juntos:

| Repositório | Função |
|---|---|
| [`allhands-hotel-server`](https://github.com/Anuudek/allhands-hotel-server) | Servidor do jogo (Java / Polaris) |
| [`allhands-hotel-client`](https://github.com/Anuudek/allhands-hotel-client) | Cliente do jogo (React / Nitro) |
| [`allhands-hotel-renderer`](https://github.com/Anuudek/allhands-hotel-renderer) | Motor de renderização do jogo (PixiJS) |
| [`allhands-hotel-cms`](https://github.com/Anuudek/allhands-hotel-cms) *(este)* | Site/CMS do hotel (Laravel / Atom CMS) |
| [`allhands-hotel-converter`](https://github.com/Anuudek/allhands-hotel-converter) | Ferramenta que baixa e converte os assets oficiais do Habbo |

## Stack

- **Laravel 13** (PHP 8.5)
- **Livewire 4** para componentes dinâmicos
- **TailwindCSS** + Vite (dois temas disponíveis: `atom` e `dusk`)
- **Filament** para o painel administrativo (housekeeping)
- Banco de dados compartilhado com o servidor do jogo (**MariaDB**)

## Rodando com Docker

O `Dockerfile` deste repositório é multi-estágio (Composer + Node) e gera duas imagens a partir do mesmo build:

- **`cms-fpm`**: roda o PHP-FPM, aplica migrations e popula as configurações padrão (`WebsiteSettingsSeeder`) automaticamente na inicialização.
- **`cms-nginx`**: serve os arquivos estáticos e encaminha requisições PHP pro `cms-fpm`.

```bash
docker build --target cms-fpm -t allhands-cms-fpm .
docker build --target cms-nginx -t allhands-cms-nginx .
```

Configurações sensíveis (senha do banco, chave de aplicação, etc) são passadas via variáveis de ambiente — veja `.env.docker` para a lista completa com comentários.

### Observação importante

O arquivo `.env` local (usado em desenvolvimento) é excluído do build via `.dockerignore` de propósito — ele nunca deve ir para dentro da imagem, para não sobrescrever silenciosamente as configurações de produção definidas em `.env.docker`.

## Créditos

Baseado no excelente trabalho da comunidade do [Atom CMS](https://github.com/ObjectRetros/atomcms). Este fork adapta o projeto para rodar em containers Docker como parte da infraestrutura do Allhands Hotel.
