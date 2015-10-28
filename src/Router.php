<?php
/**
 * DashExpressive
 *
 * @link      http://github.com/DASPRiD/DashExpressive For the canonical source repository
 * @copyright 2015 Ben Scholzen 'DASPRiD'
 * @license   http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */

namespace Dash\Expressive;

use Dash\Exception\OutOfBoundsException;
use Dash\MatchResult\MethodNotAllowed;
use Dash\MatchResult\SuccessfulMatch;
use Dash\Parser\Segment;
use Dash\Route\Generic;
use Dash\Router as DashRouter;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Expressive\Exception\RuntimeException;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\RouterInterface;

class Router implements RouterInterface
{
    /**
     * @var Route[]
     */
    protected $routesToInject = [];

    /**
     * @var DashRouter
     */
    protected $router;

    /**
     * @var DynamicRouteCollection
     */
    protected $routeCollection;

    /**
     * @param string $baseUri
     */
    public function __construct($baseUri = 'http://example.com/')
    {
        $this->routeCollection = new DynamicRouteCollection();
        $this->router          = new Router($this->routeCollection, $baseUri);
    }

    /**
     * {@inheritdoc}
     */
    public function addRoute(Route $route)
    {
        $this->routesToInject[] = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function match(ServerRequestInterface $request)
    {
        $this->injectRoutes();

        $matchResult = $this->router->match($request);

        if ($matchResult instanceof SuccessfulMatch) {
            return RouteResult::fromRouteMatch(
                $matchResult->getRouteName(),
                $matchResult->getParam('middleware'),
                $matchResult->getParams()
            );
        }

        if ($matchResult instanceof MethodNotAllowed) {
            return RouteResult::fromRouteFailure($matchResult->getAllowedMethods());
        }

        return RouteResult::fromRouteFailure();
    }

    /**
     * {@inheritdoc}
     */
    public function generateUri($name, array $substitutions = array())
    {
        $this->injectRoutes();

        try {
            return $this->router->assemble($name, $substitutions);
        } catch (OutOfBoundsException $e) {
            throw new RuntimeException(sprintf(
                'Cannot generate URI based on route "%s"; route not found',
                $name
            ), null, $e);
        }
    }

    /**
     * Injects any unprocessed routes into the underlying router implementation.
     */
    protected function injectRoutes()
    {
        foreach ($this->routes as $index => $route) {
            $this->injectRoute($route);
            unset($this->routesToInject[$index]);
        }
    }

    /**
     * Injects route into the underlying router implemetation.
     *
     * @param Route $route
     */
    protected function injectRoute(Route $route)
    {
        $pathParser = new Segment('/', $route->getPath(), []);
        $options    = $route->getOptions();
        $defaults   = isset($options['defaults']) ? $options['defaults'] : [];
        $methods    = Route::HTTP_METHOD_ANY === $route->getAllowedMethods() ? null : $route->getAllowedMethods();

        $this->routeCollection->add(
            $route->getName(),
            new Generic($pathParser, null, $methods, null, null, $defaults)
        );
    }
}
