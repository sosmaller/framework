<?php

namespace SoSmaller\Components;

use SoSmaller\Traits\Singleton;
use  Exception;

class Config
{
    use Singleton;

    private static $config;

    /**
     * @param String $key
     * @return mixed|string
     * @throws Exception
     */
    public function getConfig($key)
    {
        if ($key) {
            if (!self::$config && defined('SYSTEM_PATH')) {
                self::$config = include_once SYSTEM_PATH . 'config/config.php';
            }
            $config = self::$config;
            $keys = explode('.', $key);
            foreach ($keys as $k) {
                if (isset($config[$k])) {
                    $config = $config[$k];
                } else {
                    throw new  Exception(' The key ' . $key . ' is not in config');
                }
            }
            return $config;
        } else {
            throw new  Exception('Must give a key');
        }
        return '';
    }

}
