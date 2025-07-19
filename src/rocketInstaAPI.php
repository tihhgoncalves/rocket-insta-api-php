<?php

namespace Rocket;

class rocketInstaAPI
{
    private $cookieFile;
    private $userAgent;
    private $session;
    private $debug;
    private $csrfToken = null;
    private $proxyUrl;
    private $proxyType;
    private $proxyAuth;
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

    private $defaultStoryOptions = [
        'caption' => '',
        'autosize' => true,
        'width' => null,
        'height' => null,
        'mention_user_ids' => []
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

    public function setProxy(string $proxyUrl, string $proxyType = 'http', string $proxyAuth = null)
    {
        $this->proxyUrl = $proxyUrl;
        $this->proxyType = strtolower($proxyType); // http, socks4, socks5
        $this->proxyAuth = $proxyAuth; // Ex: "usuario:senha" ou null
    }


    private function request($url, $method = 'GET', $headers = [], $body = null, $contentType = 'form', $extra = [], $disableDefaultHeaders = false)
    {
        curl_setopt($this->session, CURLOPT_URL, $url);
        curl_setopt($this->session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->session, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->session, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($this->session, CURLOPT_FOLLOWLOCATION, $extra['follow'] ?? false);
        curl_setopt($this->session, CURLOPT_HEADER, $extra['header'] ?? false);

        // Header de conteúdo
        if ($body !== null) {
            if ($contentType === 'json') {
                $body = json_encode($body);
                $headers[] = 'Content-Type: application/json';
            } elseif ($contentType === 'form' && is_array($body)) {
                $body = http_build_query($body);
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
            curl_setopt($this->session, CURLOPT_POSTFIELDS, $body);
        }

        // Cabeçalhos padrão, exceto se desativado
        if (!$disableDefaultHeaders) {
            $headers[] = 'User-Agent: ' . $this->userAgent;
            if ($this->csrfToken) {
                $headers[] = 'x-csrftoken: ' . $this->csrfToken;
            }
        }

        // Aplica headers finais
        if (!empty($headers)) {
            curl_setopt($this->session, CURLOPT_HTTPHEADER, $headers);
        }

        // Aplica proxy, se configurado
        if (!empty($this->proxyUrl)) {
            curl_setopt($this->session, CURLOPT_PROXY, $this->proxyUrl);

            switch ($this->proxyType) {
                case 'socks4':
                    curl_setopt($this->session, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                    break;
                case 'socks5':
                    curl_setopt($this->session, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                    break;
                default:
                    curl_setopt($this->session, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    break;
            }

            if (!empty($this->proxyAuth)) {
                curl_setopt($this->session, CURLOPT_PROXYUSERPWD, $this->proxyAuth);
            }
        }

        $response = curl_exec($this->session);

        if (curl_errno($this->session)) {
            if ($this->debug) {
                echo "cURL Error: " . curl_error($this->session);
            }
            return false;
        }

        return $response;
    }


    public function login($username, $password, $saveSession = false)
    {

        if ($saveSession) {

            // Garante que o arquivo de cookies exista (ou cria ele) e esteja acessível
            if (!file_exists($this->cookieFile)) {
                file_put_contents($this->cookieFile, '');
            }

            if (!is_readable($this->cookieFile)) {
                die("Erro: Não foi possível ler o arquivo de cookies.");
            }

            // Força permissão do arquivo
            @chmod($this->cookieFile, 0666);
        }

        // Captura o CSRF token antes de tentar o login
        $csrfToken = $this->getCsrfToken();

        if (!$csrfToken) {
            return 'Erro: Não foi possível obter o CSRF Token';
        }

        $url = "https://www.instagram.com/api/v1/web/accounts/login/ajax/";
        $postFields = [
            'enc_password' => '#PWD_INSTAGRAM_BROWSER:0:' . time() . ':' . $password,
            'username' => $username,
            'queryParams' => '{"flo":"true"}',
            'optIntoOneTap' => 'false',
            'trustedDeviceRecords' => '{}',
            'isPrivacyPortalReq' => 'false',
            'loginAttemptSubmissionCount' => '0',
            'caaF2DebugGroup' => '0',
        ];

        $headers = [
            "accept: */*",
            "accept-language: pt-BR,pt;q=0.9",
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
            "x-asbd-id: 359341",
            "x-ig-app-id: 936619743392459",
            "x-ig-www-claim: hmac.AR3hHCS5xR8ssjwi_S8xMmc92j6QpdInC8c7x8GUKIN2IiOK",
            "x-instagram-ajax: 1024758508",
            "x-requested-with: XMLHttpRequest",
            "x-web-session-id: c44ahz:tcyzde:o6u39j"
        ];

        $response = $this->request(
            $url,
            'POST',
            $headers,
            $postFields,
            'form',
            ['follow' => false, 'header' => false]
        );

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
            $this->flushCookies();

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
        if ($this->csrfToken) {
            return $this->csrfToken; // Retorna o CSRF token já carregado
        }

        $response = $this->request(
            "https://www.instagram.com/accounts/login/",
            'GET',
            null,
            'raw',
            ['header' => true, 'follow' => true]
        );


        // Verifica se houve erro no cURL
        if (curl_errno($this->session)) {
            echo "cURL Error: " . curl_error($this->session);
        }

        $httpCode = curl_getinfo($this->session, CURLINFO_HTTP_CODE);

        if ($httpCode == 429) {
            die("Falha no login: você foi temporariamente bloqueado pelo Instagram por excesso de requisições (HTTP 429). Tente novamente mais tarde.");
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

        // Salva o token na propriedade da classe
        $this->csrfToken = $csrfToken;

        return $csrfToken;
    }

    private function flushCookies()
    {
        $this->request(
            'https://www.instagram.com/',
            'GET',
            [],
            null,
            'raw',
            ['follow' => false]
        );

        curl_close($this->session);
        $this->session = curl_init();
    }


    public function loadSession()
    {
        // Verifica se o arquivo de cookies existe e é legível
        if (!file_exists($this->cookieFile) || !is_readable($this->cookieFile)) {
            return false;
        }

        $response = $this->request(
            'https://www.instagram.com/accounts/edit/',
            'GET',
            [],         // headers (pode colocar se quiser)
            null,       // sem corpo
            'raw',      // tipo 'raw' se quiser deixar o corpo intacto
            [
                'follow' => true,
                'header' => false
            ]
        );


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
        // Requisição para pegar os dados do usuário logado
        $response = $this->request(
            "https://www.instagram.com/accounts/edit/",
            'GET',
            [],
            null,
            'raw',
            ['follow' => true, 'header' => false]
        );


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

        $uploadResponse = $this->request(
            $uploadUrl,
            'POST',
            $headers,
            $imageData, // binário
            'raw',
            ['follow' => false, 'header' => false]
        );

        if ($this->debug) {
            echo "<h2>[Upload Response]</h2>";
            echo "<pre>" . htmlspecialchars($uploadResponse) . "</pre>";
        }

        /** Prepara MENCIONADOS e COLLAB */
        $invite_coauthor_user_ids_string = '';
        $usertags = [];

        if (!empty($opts['collab_user_ids']) && is_array($opts['collab_user_ids'])) {
            $coauthor_ids = [];

            foreach ($opts['collab_user_ids'] as $collab_user) {
                if (!empty($collab_user['user_id'])) {
                    $coauthor_ids[] = $collab_user['user_id'];
                    $usertags[] = [
                        'user_id' => $collab_user['user_id'],
                        'position' => $collab_user['position'] ?? [0.5, 0.5],
                    ];
                }
            }

            if (!empty($coauthor_ids)) {
                $invite_coauthor_user_ids_string = json_encode($coauthor_ids);
            }
        }

        if (!empty($opts['mention_user_ids']) && is_array($opts['mention_user_ids'])) {
            foreach ($opts['mention_user_ids'] as $mention_user) {
                if (!empty($mention_user['user_id'])) {
                    $usertags[] = [
                        'user_id' => $mention_user['user_id'],
                        'position' => $mention_user['position'] ?? [0.5, 0.5],
                    ];
                }
            }
        }

        // Gera o JSON final com todos os usertags
        $usertags = !empty($usertags) ? json_encode(['in' => $usertags]) : '';



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

        // Campos opcionais
        if ($invite_coauthor_user_ids_string) {
            $postFieldsArr['invite_coauthor_user_ids_string'] = $invite_coauthor_user_ids_string;
        }
        if ($usertags) {
            $postFieldsArr['usertags'] = $usertags;
        }

        $headers = [
            "origin: https://www.instagram.com",
            "referer: https://www.instagram.com/accounts/edit/",
            "x-asbd-id: 359341",
            "x-ig-app-id: 936619743392459",
            "x-instagram-ajax: 1024760320",
            "x-requested-with: XMLHttpRequest",
            "x-web-session-id: 6a7f31:tcyzde:uphubx"
        ];

        $postResponse = $this->request(
            $postUrl,
            'POST',
            $headers,
            $postFieldsArr, // pode ser array
            'form',
            ['follow' => false, 'header' => false]
        );

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

    public function story($imagePath, $options = [])
    {
        if (!file_exists($imagePath)) {
            return 'Arquivo de imagem não encontrado!';
        }

        $opts = array_merge($this->defaultStoryOptions, $options);

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
            $width = 720;
            $height = 1280;
        }

        $headers = [
            "accept: */*",
            "content-type: $mime",
            "origin: https://www.instagram.com",
            "referer: https://www.instagram.com/",
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

        $uploadResponse = $this->request(
            $uploadUrl,
            'POST',
            $headers,
            $imageData,
            'raw',
            ['follow' => false, 'header' => false]
        );

        if ($this->debug) {
            echo "<h2>[Story Upload Response]</h2>";
            echo "<pre>" . htmlspecialchars($uploadResponse) . "</pre>";
        }

        $mentions = [];
        if (!empty($opts['mention_user_ids']) && is_array($opts['mention_user_ids'])) {
            foreach ($opts['mention_user_ids'] as $mention) {
                if (!empty($mention['user_id'])) {
                    $mentions[] = [
                        'user_id' => $mention['user_id'],
                        'position' => $mention['position'] ?? [0.5, 0.5],
                    ];
                }
            }
        }
        $reel_mentions = !empty($mentions) ? json_encode($mentions) : '';

        $postUrl = 'https://www.instagram.com/api/v1/web/create/configure_to_story/';
        $postFieldsArr = [
            'upload_id' => $upload_id,
            'caption' => $opts['caption']
        ];

        if ($reel_mentions) {
            $postFieldsArr['reel_mentions'] = $reel_mentions;
        }

        $headers = [
            "origin: https://www.instagram.com",
            "referer: https://www.instagram.com/create/story/",
            "x-asbd-id: 359341",
            "x-ig-app-id: 936619743392459",
            "x-instagram-ajax: 1024760320",
            "x-requested-with: XMLHttpRequest",
            "x-web-session-id: 6a7f31:tcyzde:uphubx"
        ];

        $postResponse = $this->request(
            $postUrl,
            'POST',
            $headers,
            $postFieldsArr,
            'form',
            ['follow' => false, 'header' => false]
        );

        if ($this->debug) {
            echo "<h2>[Story Response]</h2>";
            echo "<pre>" . htmlspecialchars($postResponse) . "</pre>";
        }

        $postData = json_decode($postResponse, true);
        if (isset($postData['status']) && $postData['status'] === 'ok') {
            return true;
        }

        return 'Falha ao postar Story!';
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

    public function searchUsers($query)
    {
        $url = "https://www.instagram.com/api/v1/web/search/topsearch/?context=user&include_reel=true&query=" . urlencode($query);

        $headers = [
            "referer: https://www.instagram.com/",
            "x-ig-app-id: 936619743392459",
            "x-requested-with: XMLHttpRequest",
            "x-asbd-id: 359341",
            "x-web-session-id: x1r9nc:1ungui:knycvf"
        ];

        $response = $this->request(
            $url,
            'GET',
            $headers,
            null,
            'raw',
            ['follow' => false, 'header' => false]
        );

        if ($this->debug) {
            $json = json_decode($response, true);
            echo "<h2>[searchUser]</h2>";
            echo "<pre>" . print_r($json, true) . "</pre>";
        }
        $data = json_decode($response, true);
        return $data['users'] ?? [];
    }

    public function searchUser($username)
    {
        $users = $this->searchUsers($username);

        foreach ($users as $user) {
            if (isset($user['user']) && $user['user']['username'] === $username) {
                return $user['user'];
            }
        }
        return null; // Retorna null se o usuário não for encontrado
    }
}
