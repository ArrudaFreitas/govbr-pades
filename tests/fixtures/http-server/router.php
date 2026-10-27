<?php


declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/oauth/token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    echo json_encode([
        'method' => $_SERVER['REQUEST_METHOD'],
        'contentType' => $_SERVER['CONTENT_TYPE'] ?? null,
        'rawBody' => file_get_contents('php://input'),
    ]);

    return;
}

if ($path === '/api/assinarPKCS7' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/octet-stream');
    header('X-Received-Authorization: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

    echo implode('', array_map('chr', range(0, 255)));

    return;
}

http_response_code(404);
header('Content-Type: text/plain');

echo 'unknown fixture path: ' . $path;
