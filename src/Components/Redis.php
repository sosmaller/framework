<?php

namespace SoSmaller\Components;

use Exception;
use RedisException;

class Redis
{
    private $_obj;
    private $_name = 'default';
    private static $_singleton = []; // 单例

    public function connection($name = 'default')
    {
        if (isset(self::$_singleton[$name]) && self::$_singleton[$name]) {
            $redis = self::$_singleton[$name];
        } else {
            $redis = self::getRedis($name);
        }

        if (!$redis || !self::redisPing($redis)) {
            $redis = self::getRedis($name, true);
        }

        if ($redis) {
            self::$_singleton[$name] = $redis;
            $this->_name = $name;
            $this->_obj = $redis;
        } else {
            unset(self::$_singleton[$name]);
            throw new  Exception("Redis is connection failed");
        }
        return $this;
    }

    /**
     * @param string $name
     * @param bool $retry
     * @return bool|\Redis
     * @throws RedisException
     * @throws Exception
     */
    private function getRedis($name = 'default', $retry = false)
    {
        try {
            $config = config('database.redis');
            $config = isset($config[$name]) ? $config[$name] : '';
            if (!$config) {
                throw new  Exception("Redis " . $name . " is not found");
            }
            $timeout = (isset($config['timeout']) && $config['timeout']) ? $config['timeout'] : 1;
            $redis = new \Redis();
            if (isset($config['persistent']) && strlen($config['persistent'])) {
                $redis->pconnect($config['host'], $config['port'], $timeout);
            } else {
                $redis->connect($config['host'], $config['port'], $timeout);
            }
            $redis->select($config['database']);
            return $redis;
        } catch (RedisException $e) {
            if ($retry) {
                throw $e;
            }
        } catch (Exception $e) {
            if ($retry) {
                throw $e;
            }
        }
        return false;
    }

    /**
     * @desc 检查连接是否可用
     * @param $redis
     * @return bool
     */
    private function redisPing($redis)
    {
        try {
            return $redis->ping() == '+PONG';
        } catch (RedisException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * @desc 兼容redis各种命令
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        try {
            !$this->_obj && $this->connection($this->_name);
            if (!$arguments) {
                return $this->_obj->$name();
            } elseif (isset($arguments[1]) && is_array($arguments[1])) {
                return $this->_obj->$name($arguments[0], $arguments[1]);
            } else {
                return $this->_obj->$name(...$arguments);
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}