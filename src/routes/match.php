<?php

declare(strict_types = 1);

namespace functional\routes;

use FastRoute;

use function functional\dependencies\bootstrap;
use function functional\structures\route_match_not_found;
use function functional\structures\route_match_found;
use function functional\structures\route_match_method_not_allowed;
use function functional\helpers\format;

function match(...$parameters) {
    $function = bootstrap(
        "functional\\routes\\match", function(string $method, string $pattern) {
            $routes = store\all();

            $function = bootstrap(
                "unmentionables\\fastroute\\dispatcher",
                function() use ($routes) {
                    static $dispatcher;

                    if ($dispatcher) {
                        return $dispatcher;
                    }

                    $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $collector) use ($routes) {
                        foreach ($routes as $route) {
                            $collector->addRoute($route->method, $route->pattern, $route->handler);
                        }
                    });

                    return $dispatcher;
                }
            );

            $dispatcher = $function();

            $result = $dispatcher->dispatch($method, $pattern);

            if ($result[0] === FastRoute\Dispatcher::NOT_FOUND) {
                return route_match_not_found([
                    "method" => $method,
                    "pattern" => $pattern,
                ]);
            }

            if ($result[0] === FastRoute\Dispatcher::FOUND) {
                $matches = null;

                foreach ($routes as $route) {
                    if ($route->handler === $result[1]) {
                        $matched = $route;
                        break;
                    }
                }

                $parameters = route_match_found([
                    "route" => $matched,
                    "variables" => $result[2],
                ]);

                return $result[1]($parameters);
            }

            if ($result[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
                return route_match_method_not_allowed([
                    "allowed_methods" => $result[1],
                ]);
            }
        }
    );

    return $function(...$parameters);
}
