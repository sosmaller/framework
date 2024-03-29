<?php

namespace SoSmaller;

use Exception;

class Application
{
    public function __construct()
    {
        date_default_timezone_set(env('APP_TIMEZONE', 'PRC'));
    }

    /**
     *  http run
     */
    public function run()
    {
        try {
            $this->router();
        } catch (Exception $e) {
            exception($e);
        }
    }

    /**
     * cli run
     */
    public function make()
    {
        try {
            $this->router(true);
        } catch (Exception $e) {
            exception($e);
        }
    }

    /**
     * @desc router map
     * @param bool $cli
     * @return mixed
     * @throws Exception
     */
    private function router($cli = false)
    {
        if ($cli) {
            //cli url parset
            $params = $_SERVER["argv"];
            $command = isset($params[1]) ? $params[1] : "";
            $method = "handle";
            if (!$command) {
                $command = 'schedule';
                $method = 'alert';
            }
            if ($command == 'schedule') {
                $class = '\\App\\Console\\Kernel';
            } elseif ($command == 'queue') {
                $class = '\\SoSmaller\\Components\\Queue';
            } else {
                $class = '\\App\\Console\\Commands\\' . ucwords($command);
            }
        } else {
            //request url parset
            $request_uri = safe_filter($_SERVER['REQUEST_URI']);
            $request_uri = str_replace('//', '/', $request_uri);
            $request_uri = explode('?', trim($request_uri));
            $request_uri = explode('/', trim($request_uri[0], '/'));
            $control = $request_uri[0] ? $request_uri[0] : 'index';
            $method = isset($request_uri[1]) ? $request_uri[1] : '';
            $route_url = $method ? $control . '/' . $method : $control;
            if (!$route_info = $this->access($route_url)) {
                throw new Exception("You have no access");
            }
            $class = $route_info['class'];
            $method = $route_info['method'];
            $params = ['controller' => $class, 'method' => $method];
        }

        if (class_exists($class)) {
            if (method_exists($class, $method)) {
                $class = new $class($params);
                return $class->$method($params);
            } else {
                throw new Exception('Method ' . $method . ' is not exists');
            }
        } else {
            throw new Exception('Class ' . $class . ' is not exists');
        }
    }

    /**
     * access control
     * @param $route
     * @return bool|mixed
     * @throws Exception
     */
    private function access($route)
    {
        $http_method = safe_filter(strtolower($_SERVER['REQUEST_METHOD']));
        if (!in_array($http_method, ['post', 'get'])) throw new Exception("Request method is not allow");

        $router_map = config("router")[$http_method];

        foreach ($router_map as $map) {
            $_route_from = isset($map[0]) ? $map[0] : '';
            $_route_to = isset($map[1]) ? $map[1] : '';
            if ($_route_from == $route && $_route_to) {
                $_route_to = explode('@', $_route_to);
                $controller = str_replace('/', '\\', $_route_to[0]);
                $method = isset($_route_to[1]) ? $_route_to[1] : 'index';
                $class = '\\App\\Controllers\\' . $controller;
                return compact('class', 'method');
            }
        }
        return false;
    }

}
