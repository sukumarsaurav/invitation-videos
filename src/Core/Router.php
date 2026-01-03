<?php
/**
 * Invitation Videos - Lightweight Router
 * 
 * Simple routing system with middleware support
 */

class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $basePath = '';

    /**
     * Set base path for all routes
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/');
    }

    /**
     * Register GET route
     */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Register POST route
     */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Register route for any method
     */
    public function any(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('ANY', $path, $handler, $middleware);
    }

    /**
     * Add route to registry
     */
    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): void
    {
        $pattern = $this->convertToRegex($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex(string $path): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $this->basePath . $pattern . '$#';
    }

    /**
     * Register global middleware
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    /**
     * Dispatch request to matching route
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run global middleware
                foreach ($this->middleware as $middleware) {
                    $result = call_user_func($middleware);
                    if ($result === false) {
                        return;
                    }
                }

                // Run route-specific middleware
                foreach ($route['middleware'] as $middleware) {
                    $result = call_user_func($middleware);
                    if ($result === false) {
                        return;
                    }
                }

                // Call route handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $method], $params);
                } else {
                    call_user_func_array($handler, $params);
                }

                return;
            }
        }

        // No route found - 404
        $this->notFound();
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        if (file_exists(__DIR__ . '/../../templates/pages/404.php')) {
            include __DIR__ . '/../../templates/pages/404.php';
        } else {
            echo '<h1>404 - Page Not Found</h1>';
        }
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }

    /**
     * Get current URL path
     */
    public static function currentPath(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    /**
     * Check if current request is POST
     */
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if current request is AJAX
     */
    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Send JSON response
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
