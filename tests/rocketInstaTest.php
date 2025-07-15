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