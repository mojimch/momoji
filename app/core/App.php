<?php

class App
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $path = $basePath !== '' && str_starts_with($path, $basePath)
            ? substr($path, strlen($basePath))
            : $path;
        $path = $this->normalize($path);

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            foreach ($this->routes[$method] ?? [] as $route => $candidate) {
                $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
                $pattern = '#^' . str_replace('/', '\\/', $pattern) . '$#';
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches);
                    $handler = $candidate;
                    $params = $matches;
                    break;
                }
            }
        } else {
            $params = [];
        }

        if ($handler === null) {
            http_response_code(404);
            require APP_PATH . '/views/layouts/error.php';
            return;
        }

        if (is_array($handler)) {
            [$className, $methodName] = $handler;
            $controller = new $className();
            call_user_func_array([$controller, $methodName], $params ?? []);
            return;
        }

        call_user_func_array($handler, $params ?? []);
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
