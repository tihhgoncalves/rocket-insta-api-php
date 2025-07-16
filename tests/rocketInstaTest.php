<?php

require '../vendor/autoload.php';

// Carrega credenciais do arquivo .env localizado nesta pasta
$envFile = __DIR__ . '/.env';
$env = [];
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
}

$username = $env['USERNAME'] ?? '';
$password = $env['PASSWORD'] ?? '';

use RocketInsta\rocketInsta;

$insta = new rocketInsta(true);

if ($insta->loadSession()) {
    echo "Sessão já ativa!";
} else {
    $loginResult = $insta->login($username, $password, true);

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


// Busca usuário
$user1 = $insta->searchUser('cafecomnews');
$user2 = $insta->searchUser('rocketprodutoradigital');


$result = $insta->post('photo.jpg', [
    'caption' => 'Minha legenda! ' . date('Y-m-d H:i:s'), // legenda do post
    'hideLikes' => true, // esconder likes
    'disableComments' => true, // desabilita comentários
    'autosize' => true, // autoajuste de tamanho
    'collab_user_ids' => [
        [
            "position" => [0.5, 0.5],
            "user_id" => $user1['id']
        ]
    ],
    'mention_user_ids' => [
        [
            "position" => [0.1, 0.1],
            "user_id" => $user2['id']
        ]
    ],
]);


if ($result === true) {
    echo "Post feito com sucesso!";
} else {
    echo "Erro ao postar: $result";
}


$result = $insta->story('photo.jpg', [
    'caption' => 'Minha legenda! ' . date('Y-m-d H:i:s'), // legenda do post
    'autosize' => true, // autoajuste de tamanho
    'mention_user_ids' => [
        [
            "position" => [0.1, 0.1],
            "user_id" => $user2['id']
        ]
    ],
]);


if ($result === true) {
    echo "Story feito com sucesso!";
} else {
    echo "Erro ao postar story: $result";
}

