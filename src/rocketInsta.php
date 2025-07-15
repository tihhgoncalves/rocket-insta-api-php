<?php

namespace RocketInsta;

class rocketInsta
{
    private $cookieFile;
    private $userAgent;
    private $session;
    private $debug;

    public function __construct($debug = false, $cookieFile = 'insta_cookie.txt')
    {
        $this->debug = $debug;  // Definindo o parâmetro de debug
        $this->cookieFile = $cookieFile;
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
        $this->session = curl_init();
    }

    public function login($username, $password)
    {
        // Captura o CSRF token antes de tentar o login
        $csrfToken = $this->getCsrfToken();

        die('PAROU!');

        if (!$csrfToken) {
            return 'Erro: Não foi possível obter o CSRF Token';
        }

        // Cabeçalhos de requisição que você pode ter capturado
        $headers = [
            "accept: */*",
            "accept-language: pt-BR,pt;q=0.9",
            "content-type: application/x-www-form-urlencoded",
            "origin: https://www.instagram.com",
            "referer: https://www.instagram.com/?flo=true",
            "sec-ch-prefers-color-scheme: light",
            "sec-ch-ua: ^\"Not)A;Brand^\";v=\"8\", ^\"Chromium^\";v=\"138\", ^\"Google Chrome^\";v=\"138\"",
            "sec-ch-ua-full-version-list: ^\"Not)A;Brand^\";v=\"8.0.0.0\", ^\"Chromium^\";v=\"138.0.7204.101\", ^\"Google Chrome^\";v=\"138.0.7204.101\"",
            "sec-ch-ua-mobile: ?0",
            "sec-ch-ua-model: ^\"^\"",
            "sec-ch-ua-platform: ^\"Windows^\"",
            "sec-ch-ua-platform-version: ^\"19.0.0^\"",
            "sec-fetch-dest: empty",
            "sec-fetch-mode: cors",
            "sec-fetch-site: same-origin",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
            "x-asbd-id: 359341",
            "x-csrftoken: " . $csrfToken, // CSRF token capturado
            "x-ig-app-id: 936619743392459",
            "x-ig-www-claim: hmac.AR3hHCS5xR8ssjwi_S8xMmc92j6QpdInC8c7x8GUKIN2IiOK",
            "x-instagram-ajax: 1024758508",
            "x-requested-with: XMLHttpRequest",
            "x-web-session-id: c44ahz:tcyzde:o6u39j"
        ];

        // Parâmetros de POST que são enviados com a requisição
        $postFields = [
            'enc_password' => '%23PWD_INSTAGRAM_BROWSER%3A10%3A1752580094%3AARBQAAwac08aIT1ky8r92Rl%2BWfogcuF%2FTwNLoYpL2O7aycwGjIX%2FlgJsPt1hwGkzkQAmDWxSAy3yRefcwafECbazEDqPePDTOyrtooaCyTCPwaQlVRSMt3IJW%2BTNasaItuTB8VY96MlZQK5GCfVH', // Senha codificada
            'username' => $username,  // Nome de usuário
            'queryParams' => '{"flo":"true"}',  // Parâmetros de consulta
            'optIntoOneTap' => 'false',  // Opcional
            'trustedDeviceRecords' => '{}',  // Device records, pode ser opcional
            'isPrivacyPortalReq' => 'false',  // Privacidade, opcional
            'loginAttemptSubmissionCount' => '0', // Tentativas de login
            'caaF2DebugGroup' => '0',  // Grupo de depuração, opcional
        ];

        // Inicializa a requisição cURL
        curl_setopt($this->session, CURLOPT_URL, "https://www.instagram.com/api/v1/web/accounts/login/ajax/");
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_POST, true);
        curl_setopt($this->session, CURLOPT_POSTFIELDS, http_build_query($postFields));  // Envia os parâmetros no corpo da requisição
        curl_setopt($this->session, CURLOPT_HTTPHEADER, $headers);  // Cabeçalhos HTTP

        // Executa a requisição e obtém a resposta
        $response = curl_exec($this->session);

        // Verifica se houve erro no cURL
        if (curl_errno($this->session)) {
            echo "cURL Error: " . curl_error($this->session);
        }

        // Exibe os detalhes da requisição e a resposta (para debugar)
        echo "<pre>";
        print_r(curl_getinfo($this->session)); // Detalhes da requisição cURL
        print_r($response); // Resposta da requisição (erro ou sucesso)
        echo "</pre>";

        // Decodifica a resposta JSON
        $data = json_decode($response, true);

        // Verifica se o login foi bem-sucedido
        if (isset($data['authenticated']) && $data['authenticated'] == true) {
            return true;  // Login bem-sucedido
        }

        // Caso contrário, verifica o tipo de erro
        if (isset($data['message'])) {
            if (strpos($data['message'], 'challenge') !== false) {
                return 'Desafio de segurança';  // Desafio de segurança (2FA ou outro)
            } elseif (strpos($data['message'], 'password') !== false) {
                return 'Senha incorreta';  // Senha errada
            } else {
                return 'Erro desconhecido: ' . $data['message'];  // Outro erro
            }
        }

        return 'Falha no login!';  // Caso não seja possível identificar o erro
    }


    public function getCsrfToken()
    {
        curl_setopt($this->session, CURLOPT_URL, "https://www.instagram.com/accounts/login/");
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_HEADER, true);  // Para capturar os cabeçalhos e cookies
        curl_setopt($this->session, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->session, CURLOPT_COOKIEJAR, 'cookies.txt');
        curl_setopt($this->session, CURLOPT_COOKIEFILE, 'cookies.txt');



        // Modificar o cabeçalho User-Agent para simular um navegador
        $headers = [
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        ];
        curl_setopt($this->session, CURLOPT_HTTPHEADER, $headers);

        // Faz a requisição GET
        $response = curl_exec($this->session);

        // Verifica se houve erro no cURL
        if (curl_errno($this->session)) {
            echo "cURL Error: " . curl_error($this->session);
        }

        // Exibe a resposta para depuração
        echo "<pre>" . htmlentities($response) . "</pre>";  // Exibe a resposta HTML para depuração

        // Captura os cookies da resposta (onde o CSRF token normalmente é armazenado)
        $cookies = [];
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $cookies);

        // Encontra o CSRF Token no cookie
        $csrfToken = null;
        foreach ($cookies[1] as $cookie) {
            if (strpos($cookie, 'csrftoken') === 0) {
                $csrfToken = substr($cookie, 10);  // Extrai o valor do csrfToken
                break;
            }
        }

        // Se não encontrar no cookie, tenta no HTML
        if (!$csrfToken) {
            preg_match('/"csrf_token":"([^"]+)"/', $response, $matches);
            if (isset($matches[1])) {
                $csrfToken = $matches[1];
            }
        }

        return $csrfToken;
    }




    public function __destruct()
    {
        curl_close($this->session);
    }
}
