<?php

class Router
{
    private $routes = [];

    public function add(string $route, string $controller, string $action)
    {
        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action
        ];
    }

    public function dispatch(string $url)
    {
        $params = [];
        $match = $this->match($url, $params);

        if ($match === null) {
            $this->notFound("No route matched for $url");
            return;
        }

        $controllerName = $match['controller'];
        $action = $match['action'];

        $controllerFile = APP_ROOT . '/app/Controllers/' . $controllerName . '.php';
        if (!file_exists($controllerFile)) {
            $this->notFound("Controller class $controllerName not found");
            return;
        }

        require_once $controllerFile;

        $controller = new $controllerName();
        if (!is_callable([$controller, $action])) {
            $this->notFound("Method $action not found in controller $controllerName");
            return;
        }

        $controller->setParams($params);
        $controller->$action();
    }

    // Exact match first, then routes carrying a {placeholder} such as api/comments/{id}
    private function match(string $url, array &$params)
    {
        $params = [];

        if (array_key_exists($url, $this->routes)) {
            return $this->routes[$url];
        }

        $parts = explode('/', $url);

        foreach ($this->routes as $pattern => $route) {
            if (!str_contains($pattern, '{')) {
                continue;
            }

            $patternParts = explode('/', $pattern);
            if (count($patternParts) !== count($parts)) {
                continue;
            }

            $found = [];
            $matched = true;

            foreach ($patternParts as $i => $piece) {
                if (str_starts_with($piece, '{') && str_ends_with($piece, '}')) {
                    $found[substr($piece, 1, -1)] = $parts[$i];
                } elseif ($piece !== $parts[$i]) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                $params = $found;
                return $route;
            }
        }

        return null;
    }

    private function notFound($msg = '')
    {
        http_response_code(404);
        echo "404 Not Found - " . (function_exists('e') ? e($msg) : htmlspecialchars($msg));
        exit;
    }
}
