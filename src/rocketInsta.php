<?php

namespace RocketInsta;

class rocketInsta
{
    private $cookieFile;
    private $userAgent;
    private $session;
    private $debug;
    private $csrfToken = null;
    private $defaultPostOptions = [
        'caption' => '',
        'hideLikes' => false,
        'disableComments' => false,
        'location' => null,
        'location_id' => null,
        'autosize' => true,
        'width' => null,   // novo
        'height' => null,  // novo
    ];

    public function __construct($debug = false, $cookieFile = null)
    {
        $this->debug = $debug;
        if ($cookieFile === null) {
            $cookieFile = __DIR__ . '/insta_session.txt';
        }
        $this->cookieFile = $cookieFile;
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36';
        $this->session = curl_init();
    }

    public function login($username, $password, $saveSession = false)
    {

        if ($saveSession) {

            // Garante que o arquivo de cookies exista e esteja acessível

            if (!file_exists($this->cookieFile)) {
                file_put_contents($this->cookieFile, '');
            }

            if (!is_readable($this->cookieFile)) {
                die("Erro: Não foi possível ler o arquivo de cookies.");
            }

            // (Opcional) Garante que tenha permissão
            @chmod($this->cookieFile, 0666);

            // Sempre setar antes de qualquer requisição
            curl_setopt($this->session, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($this->session, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        // Captura o CSRF token antes de tentar o login
        $csrfToken = $this->getCsrfToken();

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
            "user-agent: " . $this->userAgent,
            "x-asbd-id: 359341",
            "x-csrftoken: " . $csrfToken,
            "x-ig-app-id: 936619743392459",
            "x-ig-www-claim: hmac.AR3hHCS5xR8ssjwi_S8xMmc92j6QpdInC8c7x8GUKIN2IiOK",
            "x-instagram-ajax: 1024758508",
            "x-requested-with: XMLHttpRequest",
            "x-web-session-id: c44ahz:tcyzde:o6u39j"
        ];

        // Parâmetros de POST que são enviados com a requisição
        $postFields = [
            'enc_password' => '#PWD_INSTAGRAM_BROWSER:0:' . time() . ':' . $password,
            'username' => $username,
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
        curl_setopt($this->session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->session, CURLOPT_HEADER, false);


        curl_setopt($this->session, CURLOPT_USERAGENT, $this->userAgent);

        if ($saveSession) {
            curl_setopt($this->session, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($this->session, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, false);

        // Executa a requisição e obtém a resposta
        $response = curl_exec($this->session);
        $data = json_decode($response, true);

        // Verifica se houve erro no cURL
        if (curl_errno($this->session)) {
            echo "cURL Error: " . curl_error($this->session);
        }

        if ($this->debug) {
            echo "<h2>[Login Response]</h2>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }

        if (isset($data['authenticated']) && $data['authenticated'] == true) {
            // Força o cURL a processar e salvar os cookies fazendo uma requisição GET
            curl_setopt($this->session, CURLOPT_URL, "https://www.instagram.com/");
            curl_setopt($this->session, CURLOPT_POST, false);
            curl_setopt($this->session, CURLOPT_HTTPGET, true);
            curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
            curl_exec($this->session);

            curl_close($this->session); // Fecha para forçar o flush dos cookies
            $this->session = curl_init(); // Reabre para uso futuro
            echo ('PASSOU AQUI');
            return true;  // Login bem-sucedido
        }

        if (isset($data['error_type']) && $data['error_type'] === 'checkpoint_challenge_required') {
            $url = isset($data['checkpoint_url']) ? 'https://www.instagram.com' . $data['checkpoint_url'] : '(sem URL)';
            return 'Desafio de segurança necessário. Acesse: ' . $url;
        }

        if (isset($data['message'])) {
            if (str_contains($data['message'], 'challenge')) {
                return 'Desafio de segurança';
            } elseif (str_contains($data['message'], 'password')) {
                return 'Senha incorreta';
            } else {
                return 'Erro desconhecido: ' . $data['message'];
            }
        }

        return 'Falha no login! (nenhum erro conhecido identificado)';
    }


    public function getCsrfToken()
    {

        if($this->csrfToken) {
            return $this->csrfToken; // Retorna o CSRF token já carregado
        }
        
        curl_setopt($this->session, CURLOPT_URL, "https://www.instagram.com/accounts/login/");
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_HEADER, true);
        curl_setopt($this->session, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, true);

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


        // Exibe a resposta para depuração
        if ($this->debug) {
            echo "<h2>[getCsrfToken]</h2>";
            echo "<pre>getCsrfToken: " . $csrfToken . "</pre>";
        }


        return $csrfToken;
    }

    public function loadSession()
    {
        // Verifica se o arquivo de cookies existe e é legível
        if (!file_exists($this->cookieFile) || !is_readable($this->cookieFile)) {
            return false;
        }

        // Configura o cURL para usar o cookie salvo
        curl_setopt($this->session, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_URL, "https://www.instagram.com/accounts/edit/");
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_HEADER, false);
        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->session, CURLOPT_USERAGENT, $this->userAgent);

        // Faz uma requisição para uma página que só logado acessa
        $response = curl_exec($this->session);

        // Verifica se houve erro no cURL
        if (curl_errno($this->session)) {
            if ($this->debug) {
                echo "cURL Error: " . curl_error($this->session);
            }
            return false;
        }

        // Se a resposta contiver o campo "username" ou algo típico de usuário logado, considera válido
        if (strpos($response, '"username"') !== false || strpos($response, 'Editar perfil') !== false) {
            $this->csrfToken = $this->extractCsrfTokenFromCookieFile();
            if ($this->debug) {
                echo "<h2>[loadSession]</h2>";
                echo "<pre>Sessão ativa!</pre>";
                echo "<pre>CSRF carregado: " . $this->csrfToken . "</pre>";
            }
            return true;
        }

        if ($this->debug) {
            echo "<h2>[loadSession]</h2>";
            echo "<pre>Sessão inválida ou expirada.</pre>";
        }
        return false;
    }

    public function me()
    {
        // Garante que o cookie está setado
        curl_setopt($this->session, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_URL, "https://www.instagram.com/accounts/edit/");
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_HEADER, false);
        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->session, CURLOPT_USERAGENT, $this->userAgent);

        $response = curl_exec($this->session);

        if (curl_errno($this->session)) {
            if ($this->debug) {
                echo "cURL Error: " . curl_error($this->session);
            }
            return false;
        }

        $return_me = [];

        // A resposta é um HTML, mas contém um JSON com os dados do usuário
        // Tenta extrair o JSON do HTML
        if (preg_match('/<script type="application\/json" id="__NEXT_DATA__">(.+?)<\/script>/', $response, $matches)) {
            $json = json_decode($matches[1], true);
            if ($json && isset($json['props']['pageProps']['formData'])) {
                return $json['props']['pageProps']['formData'];
            }
        }


        // Alternativamente, tente extrair campos básicos do HTML
        if (preg_match('/"username":"([^"]+)"/', $response, $matches)) {
            $return_me['username'] = $matches[1];
        }
        if (preg_match('/"biography":"([^"]+)"/', $response, $matches)) {
            $return_me['bio'] = $matches[1];
        }
        if (preg_match('/"external_url":"([^"]+)"/', $response, $matches)) {
            $return_me['external_url'] = $matches[1];
        }
        if (preg_match('/"full_name":"([^"]+)"/', $response, $matches)) {
            $return_me['full_name'] = $matches[1];
        }


        if ($this->debug) {
            echo "<h2>[me]</h2>";
            echo "<pre>" . htmlspecialchars(print_r($return_me, true)) . "</pre>";
        }

        return $return_me;
    }

    public function post($imagePath, $options = [])
    {
        if (!file_exists($imagePath)) {
            return 'Arquivo de imagem não encontrado!';
        }

        // Mescla opções recebidas com os padrões
        $opts = array_merge($this->defaultPostOptions, $options);

        $upload_id = strval(round(microtime(true) * 1000));
        $uploadUrl = "https://i.instagram.com/rupload_igphoto/fb_uploader_$upload_id";
        $imageData = file_get_contents($imagePath);
        $imageSize = filesize($imagePath);
        $mime = mime_content_type($imagePath);

        if ($opts['autosize']) {
            list($width, $height) = getimagesize($imagePath);
        } elseif ($opts['width'] && $opts['height']) {
            $width = $opts['width'];
            $height = $opts['height'];
        } else {
            $width = 1229;
            $height = 1229;
        }

        $headers = [
            "accept: */*",
            "content-type: $mime",
            "origin: https://www.instagram.com",
            "referer: https://www.instagram.com/",
            "user-agent: " . $this->userAgent,
            "x-entity-type: $mime",
            "x-entity-name: fb_uploader_$upload_id",
            "x-entity-length: $imageSize",
            "offset: 0",
            "x-ig-app-id: 936619743392459",
            "x-instagram-rupload-params: " . json_encode([
                "media_type" => 1,
                "upload_id" => $upload_id,
                "upload_media_height" => $height,
                "upload_media_width" => $width
            ]),
            "x-asbd-id: 359341",
            "x-instagram-ajax: 1024760320",
            "x-web-session-id: 6a7f31:tcyzde:uphubx",
            "priority: u=1, i"
        ];

        curl_setopt($this->session, CURLOPT_URL, $uploadUrl);
        curl_setopt($this->session, CURLOPT_POST, true);
        curl_setopt($this->session, CURLOPT_POSTFIELDS, $imageData);
        curl_setopt($this->session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_COOKIEJAR, $this->cookieFile);

        $uploadResponse = curl_exec($this->session);
        if ($this->debug) {
            echo "<h2>[Upload Response]</h2>";
            echo "<pre>" . htmlspecialchars($uploadResponse) . "</pre>";
        }

        // Agora configure o post
        $postUrl = 'https://www.instagram.com/api/v1/media/configure/';
        $postFieldsArr = [
            'upload_id' => $upload_id,
            'caption' => $opts['caption'],
            'media_share_flow' => 'creation_flow',
            'source_type' => 'library',
            'disable_comments' => $opts['disableComments'] ? '1' : '0',
            'like_and_view_counts_disabled' => $opts['hideLikes'] ? '1' : '0',
            'share_to_facebook' => '',
            'share_to_fb_destination_type' => 'USER',
            'is_unified_video' => '1',
            'is_meta_only_post' => '0',
            'archive_only' => 'false'
        ];


        $postFields = http_build_query($postFieldsArr);

        $headers = [
            "accept: */*",
            "content-type: application/x-www-form-urlencoded",
            "origin: https://www.instagram.com",
            "referer: https://www.instagram.com/accounts/edit/",
            "user-agent: " . $this->userAgent,
            "x-asbd-id: 359341",
            "x-csrftoken: " . $this->getCsrfToken(),
            "x-ig-app-id: 936619743392459",
            "x-instagram-ajax: 1024760320",
            "x-requested-with: XMLHttpRequest",
            "x-web-session-id: 6a7f31:tcyzde:uphubx"
        ];

        curl_setopt($this->session, CURLOPT_URL, $postUrl);
        curl_setopt($this->session, CURLOPT_POST, true);
        curl_setopt($this->session, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($this->session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);

        $postResponse = curl_exec($this->session);
        if ($this->debug) {
            echo "<h2>[Post Response]</h2>";
            echo "<pre>" . htmlspecialchars($postResponse) . "</pre>";
        }

        $postData = json_decode($postResponse, true);
        if (isset($postData['status']) && $postData['status'] === 'ok') {
            return true;
        }

        return 'Falha ao postar!';
    }

    private function extractCsrfTokenFromCookieFile()
    {
        if (!file_exists($this->cookieFile)) {
            return null;
        }
        $lines = file($this->cookieFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'csrftoken') !== false) {
                $parts = preg_split('/\s+/', $line);
                return end($parts);
            }
        }
        return null;
    }
}
