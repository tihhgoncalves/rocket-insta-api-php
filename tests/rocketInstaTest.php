<?php

require '../vendor/autoload.php';

use RocketInsta\rocketInsta;

$insta = new rocketInsta(true);


$loginResult = $insta->login('poucafe.oficial', 'ZSefZFUWMaGu5R');

if ($loginResult === true) {
    echo "Login bem-sucedido!";
} else {
    echo "Falha no login: " . $loginResult;  // Exibe o erro específico
}