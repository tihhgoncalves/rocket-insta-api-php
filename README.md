<img src="https://raw.githubusercontent.com/filipedeschamps/rss-feed-emitter/master/content/logo.gif">

# 🚀 Rocket Insta API PHP

Uma biblioteca em PHP para integrar de forma simples com a API do Instagram.

[![Versão Mais Recente](https://img.shields.io/github/release/tihhgoncalves/rocket-insta-api-php.svg?style=flat)]()
[![Último Commit](https://img.shields.io/github/last-commit/tihhgoncalves/rocket-insta-api-php.svg?style=flat)]()
[![Downloads Totais](https://img.shields.io/packagist/dt/rocket/insta-api.svg?style=flat)](https://packagist.org/packages/rocket/insta-api)
[![Contribuidores do GitHub](https://img.shields.io/github/contributors/tihhgoncalves/rocket-insta-api-php.svg?style=flat)]()
[![Licença MIT](https://img.shields.io/badge/Licença-MIT-yellow.svg)](https://opensource.org/licenses/)

---

## 📥 Instalação

Instale via Composer:

```sh
composer require tihhgoncalves/rocket-insta-api-php
```

---

## 📚 Principais Funcionalidades

- Autenticação simplificada com o Instagram.
- Endpoints para recuperar informações de perfis e mídias.
- Pronto para expandir conforme necessidade do projeto.

---

## ⚙️ Uso Básico

```php
<?php

require '../vendor/autoload.php';

use Rocket\rocketInstaAPI;

$insta = new rocketInstaAPI(true);

if ($insta->loadSession()) {
    echo "Sessão já ativa!";
} else {
    $loginResult = $insta->login('seu_usuario', 'sua_senha', true);

    if ($loginResult === true) {
        echo "Login bem-sucedido!";
    } else {
        echo "Falha no login: " . $loginResult;  // Exibe o erro específico
    }
}

$userInfo = $insta->me();
if ($userInfo) {
    print_r($userInfo);
} else {
    echo "Não foi possível obter informações do usuário.";
}

$result = $insta->post('photo.jpg', [
    'caption' => 'Minha legenda! ' . date('Y-m-d H:i:s'), // legenda do post
    'hideLikes' => true, // esconder likes
    'disableComments' => true, // desabilita comentários
    'autosize' => true, // autoajuste de tamanho
]);

if ($result === true) {
    echo "Post feito com sucesso!";
} else {
    echo "Erro ao postar: $result";
}
```

$storyResult = $insta->story('photo.jpg', [
    'caption' => 'Minha story!',
    'mention_user_ids' => [
        [
            'user_id' => 123456,
            'position' => [0.5, 0.5],
        ]
    ],
]);

if ($storyResult === true) {
    echo "Story publicado!";
} else {
    echo "Erro ao postar story: $storyResult";
}
```

---

## 🙌 Contribuições

Contribuições são bem-vindas! Abra uma [issue](https://github.com/tihhgoncalves/rocket-insta-api-php/issues) para relatar problemas ou sugerir melhorias.

---

## 👨‍💻 Mantenedor

Este projeto é mantido por [@tihhgoncalves](https://github.com/tihhgoncalves).

---

## 🔗 Redes e Contato

[![GitHub](https://img.shields.io/badge/GitHub-181717.svg?style=for-the-badge&logo=GitHub&logoColor=white)](https://github.com/tihhgoncalves)
[![Telegram](https://img.shields.io/badge/Telegram-26A5E4.svg?style=for-the-badge&logo=Telegram&logoColor=white)](https://t.me/tihhgoncalves)

---

## 🚀 Rocket Produtora Digital

Criado com ♥ pela [Rocket Produtora Digital](https://www.produtorarocket.com)


---

## 📄 Licença

Distribuído sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

