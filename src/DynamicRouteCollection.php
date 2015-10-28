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
use Dash\Route\RouteInterface;
use Dash\RouteCollection\RouteCollectionInterface;
use IteratorAggregate;

class DynamicRouteCollection implements RouteCollectionInterface, IteratorAggregate
{
    /**
     * @var RouteInterface[]
     */
    protected $routes;

    /**
     * @param string         $name
     * @param RouteInterface $route
     */
    public function add($name, RouteInterface $route)
    {
        $this->routes[$name] = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!isset($this->routes[$name])) {
            throw new OutOfBoundsException(sprintf('Route with name "%s" was not found', $name));
        }

        return $this->routes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->routes as $name => $route) {
            yield $name => $route;
        }
    }
}
