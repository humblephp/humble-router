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

    public function add(string $requestMethod, string $requestUri, callable $callable)
    {
        $this->routes[$requestMethod][$requestUri] = $callable;
    }

    public function dispatch()
    {
        $requestMethod = $this->container->offsetGet('request')->getMethod();
        $requestUri = $this->container->offsetGet('request')->getUri()->getPath();

        if (isset($this->routes[$requestMethod][$requestUri])) {
            // static route
            return $this->routes[$requestMethod][$requestUri]($this->container);
        }

        foreach ($this->routes[$requestMethod] as $routeUri => $callback) {
            if (preg_match($this->getPattern($routeUri), $requestUri, $args)) {
                // dynamic route
                return $callback($this->container, $args);
            }
        }
    }

    private function getPattern($routeUri)
    {
        return '#^' . preg_replace(array_keys($this->patterns), array_values($this->patterns), $routeUri) . '$#';
    }
}
