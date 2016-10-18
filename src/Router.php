<?php

namespace Humble\Router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router
{
    private $container;
    private $routes;

    private $patterns = [
        '/s:(\w+)/' => '(?<$1>\w+)', // one or more of any word character (letter, number, underscore)
        '/d:(\w+)/' => '(?<$1>\d+)', // one or more of any digit
    ];

    public function __construct(\ArrayAccess $container)
    {
        $this->container = $container;
    }

    public function add(string $method, string $path, callable $callable, array $middleware = array())
    {
        $this->routes[$method][$path] = [
            'callable' => $callable,
            'middleware' => $middleware,
        ];
    }

    public function middleware(): array
    {
        $method = $this->container->request->getMethod();
        $path = $this->container->request->getUri()->getPath();

        return $this->routes[$method][$path]['middleware'] ?? array();
    }

    public function dispatch(): ResponseInterface
    {
        $method = $this->container->request->getMethod();
        $path = $this->container->request->getUri()->getPath();

        if (isset($this->routes[$method][$path]['callable'])) {
            return $this->routes[$method][$path]['callable']($this->container); // static route
        }

        foreach ($this->routes[$method] as $routePath => $route) {
            if (preg_match($this->getPattern($routePath), $path, $args)) {
                return $route['callable']($this->container, $args); // dynamic route
            }
        }

        throw new RouteNotFoundException('Route Not Found');
    }

    private function getPattern($path): string
    {
        return '#^' . preg_replace(array_keys($this->patterns), array_values($this->patterns), $path) . '$#';
    }
}
