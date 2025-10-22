<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . '/../app/config/ErrorHandler.php';
ErrorHandler::init();

session_start();

try {
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $requestUri = str_replace($scriptName, '', $requestUri);
    $requestUri = strtok($requestUri, '?');
    $requestUri = rtrim($requestUri, '/');

    if (empty($requestUri) || $requestUri === '/') {
        $requestUri = '/events';
    }

    $controllerName = null;
    $action = null;
    $params = [];

    $routes = [
        'GET /events' => ['EventsController', 'index'],
        'GET /events/create' => ['EventsController', 'create'],
        'POST /events' => ['EventsController', 'store'],
        'GET /events/{id}' => ['EventsController', 'show'],
        'GET /events/{id}/edit' => ['EventsController', 'edit'],
        'POST /events/{id}/update' => ['EventsController', 'update'],
        'POST /events/{id}/delete' => ['EventsController', 'destroy'],
    ];

    $matched = false;
    foreach ($routes as $route => $handler) {
        list($method, $path) = explode(' ', $route, 2);

        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        if ($method === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
            $controllerName = $handler[0];
            $action = $handler[1];

            // grab the route params like the id
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $params[] = $value;
                }
            }

            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $routeParts = explode('/', trim($requestUri, '/'));
        $controllerName = ucfirst($routeParts[0] ?? 'Home') . 'Controller';
        $action = $routeParts[1] ?? 'index';
        $params = array_slice($routeParts, 2);
    }

    $controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

    if (file_exists($controllerFile)) {
        require_once $controllerFile;

        if (class_exists($controllerName)) {
            $controller = new $controllerName();

            if (method_exists($controller, $action)) {
                call_user_func_array([$controller, $action], $params);
            } else {
                throw new Exception("Action '$action' not found in controller '$controllerName'");
            }
        } else {
            throw new Exception("Controller class '$controllerName' not found");
        }
    } else {
        http_response_code(404);
        require_once __DIR__ . '/../app/views/errors/404.php';
        exit;
    }
} catch (Exception $e) {
    error_log($e->getMessage());

    http_response_code(404);
    require_once __DIR__ . '/../app/views/errors/404.php';
    exit;
}
