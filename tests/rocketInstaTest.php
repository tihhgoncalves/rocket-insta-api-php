<?php

require '../vendor/autoload.php';

use RocketInsta\rocketInsta;

$insta = new rocketInsta(true);

if ($insta->loadSession()) {
    echo "Sessão já ativa!";
} else {
    $loginResult = $insta->login('poucafe.oficial', 'ZSefZFUWMaGu5R', true);

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
