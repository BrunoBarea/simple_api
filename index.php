<?php

declare(strict_types=1);

require_once __DIR__ . '/src/AccountController.php';

$controller = new AccountController();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch (true) {
    case $uri === '/reset' && $method === 'POST':
        $controller->reset();
        break;

    case $uri === '/accounts' && $method === 'GET':
        $controller->list();
        break;

    case $uri === '/accounts' && $method === 'POST':
        $controller->create();
        break;

    case preg_match('#^/account/(\d+)$#', $uri, $matches) === 1 && $method === 'GET':
        $controller->get((int) $matches[1]);
        break;

    case preg_match('#^/account/(\d+)$#', $uri, $matches) === 1 && $method === 'PATCH':
        $controller->update((int) $matches[1]);
        break;

    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Route not found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
}
