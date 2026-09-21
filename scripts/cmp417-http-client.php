<?php
/**
 * CMP-417 живой HTTP POST test-client.
 *
 * ВАЖНО: frontend/web/index.php жёстко фиксирует YII_ENV='prod' даже локально
 * (защита от утечки debug-трейсов через YII_DEBUG/YII_ENV=dev из .env), поэтому
 * cookie `_csrf` и `_identity-admin` выставляются с флагом `secure`. curl
 * (корректно, по RFC 6265) не отправляет `secure`-cookie обратно по обычному
 * http://, поэтому CURLOPT_COOKIEFILE/COOKIEJAR из scripts/route-sweep.php
 * молча теряет авторизацию на каждом запросе после логина: `admin/*` GET-ы
 * в CMP-413-sweep-results.csv, где стоит 302, — это анонимные редиректы на
 * /admin/login, а не подтверждённые открытые формы (313 из ~400 admin-строк).
 *
 * Этот клиент ведёт cookie вручную (без учёта Secure/Domain атрибутов) —
 * это приемлемо только для localhost-тестирования поверх http; в проде куки
 * обязаны оставаться `secure`, менять infrastructure/config/web.php не нужно.
 */

class Cmp417Client
{
    private array $cookies = [];
    public string $base;

    public function __construct(string $base)
    {
        $this->base = rtrim($base, '/');
    }

    public function request(string $method, string $url, ?array $fields = null, bool $isJson = false): array
    {
        $ch = curl_init(str_starts_with($url, 'http') ? $url : $this->base . $url);
        $headers = [];
        if ($this->cookies) {
            $cookieStr = implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($this->cookies), array_values($this->cookies)));
            $headers[] = 'Cookie: ' . $cookieStr;
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        if ($fields !== null) {
            if ($isJson) {
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'X-Requested-With: XMLHttpRequest';
                $opts[CURLOPT_POSTFIELDS] = json_encode($fields);
            } else {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
            }
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err = curl_error($ch);
        curl_close($ch);
        $rawHeaders = substr((string) $resp, 0, $headerSize);
        $body = substr((string) $resp, $headerSize);

        foreach (preg_split('/\r?\n/', $rawHeaders) as $line) {
            if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $line, $m)) {
                $this->cookies[$m[1]] = $m[2];
            }
        }

        $location = null;
        if (preg_match('/^Location:\s*(\S+)/mi', $rawHeaders, $m)) {
            $location = $m[1];
        }

        return ['code' => $code, 'headers' => $rawHeaders, 'body' => $body, 'location' => $location, 'error' => $err];
    }

    public function get(string $url): array
    {
        return $this->request('GET', $url);
    }

    public function csrfFromBody(string $body): ?string
    {
        if (preg_match('/csrf-token"\s+content="([^"]+)"/', $body, $m)) {
            return $m[1];
        }
        return null;
    }

    public function postForm(string $url, array $fields): array
    {
        // Cookie `_csrf` хранит МАСКИРОВАННЫЙ/зашифрованный токен — это НЕ то
        // значение, которое ожидается в поле формы. Значение для формы всегда
        // берётся из <meta name="csrf-token"> актуальной GET-страницы.
        $csrf = $fields['_csrf'] ?? null;
        if ($csrf === null) {
            $r = $this->get($url);
            $csrf = $this->csrfFromBody($r['body']);
        }
        if ($csrf !== null) {
            $fields['_csrf'] = $csrf;
        }
        return $this->request('POST', $url, $fields);
    }

    public function login(string $user, string $pass): bool
    {
        $r = $this->get('/admin/login');
        $token = $this->csrfFromBody($r['body']);
        if ($token === null) {
            fwrite(STDERR, "no csrf token on /admin/login\n");
            return false;
        }
        $r = $this->request('POST', '/admin/login', [
            '_csrf' => $token,
            'LoginForm[username]' => $user,
            'LoginForm[password]' => $pass,
        ]);
        return $r['code'] === 302 && $r['location'] !== null && !str_contains($r['location'], '/login');
    }
}
