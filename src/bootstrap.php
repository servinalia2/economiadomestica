<?php
session_start();

$config = require __DIR__ . '/../config/config.php';
$localConfig = __DIR__ . '/../config/local.php';
if (is_file($localConfig)) {
    $config = array_replace_recursive($config, require $localConfig);
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

function config(?string $key = null, mixed $default = null): mixed
{
    global $config;
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function db(): PDO
{
    return App\Database\Connection::get(config('db'));
}

function redirect(string $route): never
{
    header('Location: ?route=' . urlencode($route));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login');
    }
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/../views/' . $view . '.php';
    require __DIR__ . '/../views/partials/header.php';
    require $viewFile;
    require __DIR__ . '/../views/partials/footer.php';
}

function money(float|string|null $amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' €';
}
