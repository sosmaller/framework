<?php

namespace SoSmaller\Traits;

trait Singleton
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return mixed
     */
    public static function instance(...$args)
    {
        if (!isset(static::$instance)) {
            static::$instance = new static(...$args);
        }
        return static::$instance;
    }
}
