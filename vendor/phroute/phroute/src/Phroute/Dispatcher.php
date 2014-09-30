<?php namespace Phroute;

use Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Exception\HttpRouteNotFoundException;

class Dispatcher {

    private $staticRouteMap;
    private $variableRouteData;
    private $filters;
    public $matchedRoute;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteCollector $data
     */
    public function __construct(RouteCollector $data)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $data->getData();
        
        $this->filters = $data->getFilters();
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed|null
     */
    public function dispatch($httpMethod, $uri)
    {
        list($handler, $filters, $vars) = $this->dispatchRoute($httpMethod, trim($uri, '/'));

        list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

        if(($response = $this->dispatchFilters($beforeFilter)) !== null)
        {
            return $response;
        }
        
        $resolvedHandler = $this->resolveHandler($handler);
        
        $response = call_user_func_array($resolvedHandler, $vars);

        return $this->dispatchFilters($afterFilter, $response);
    }

    /**
     * Create an instance of the given filter handler.
     *
     * @param $handler
     * @return array
     */
    private function resolveHandler($handler)
    {
        if(is_array($handler) and is_string($handler[0]))
        {
            $handler[0] = new $handler[0];
        }
        
        return $handler;
    }

    /**
     * Dispatch a route filter.
     *
     * @param $filters
     * @param null $response
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null)
    {
        while($filter = array_shift($filters))
        {
            if(($filteredResponse = call_user_func($this->resolveHandler($filter), $response)) !== null)
            {
                return $filteredResponse;
            }
        }
        
        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     * @return array
     */
    private function parseFilters($filters)
    {        
        $beforeFilter = array();
        $afterFilter = array();
        
        if(isset($filters[Route::BEFORE]))
        {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if(isset($filters[Route::AFTER]))
        {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }
        
        return array($beforeFilter, $afterFilter);
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri]))
        {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }
        
        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod]))
        {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }
        
        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod)
    {
        $additional = array(Route::ANY);
        
        if($httpMethod === Route::HEAD)
        {
            $additional[] = Route::GET;
        }
        
        foreach($additional as $method)
        {
            if(isset($routes[$method]))
            {
                return $method;
            }
        }
        
        $this->matchedRoute = $routes;
        
        throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @throws Exception\HttpMethodNotAllowedException
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data) 
        {
            if (!preg_match($data['regex'], $uri, $matches))
            {
                continue;
            }

            $count = count($matches);

            while(!isset($data['routeMap'][$count++]));
            
            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod]))
            {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            } 

            foreach (array_values($routes[$httpMethod][2]) as $i => $varName)
            {
                if(!isset($matches[$i + 1]) || $matches[$i + 1] === '')
                {
                    unset($routes[$httpMethod][2][$varName]);
                }
                else
                {
                    $routes[$httpMethod][2][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

        throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
    }
}
