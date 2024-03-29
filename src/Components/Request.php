<?php

namespace SoSmaller\Components;


class Request
{

    private static $parameters = [];

    public function __construct()
    {
        self::$parameters = $_REQUEST;
        //兼容json
        if (isset($_SERVER['CONTENT_TYPE']) && false !== stripos($_SERVER['CONTENT_TYPE'], 'application/json')) {
            $input = @json_decode(file_get_contents('php://input'), true);
            if ($input && is_array($input)) {
                foreach ($input as $key => $val) {
                    self::$parameters[$key] = $val;
                }
            }
        }
    }

    /**
     * Returns the parameters.
     *
     * @return array An array of parameters
     */
    public function all()
    {
        return self::$parameters;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     */
    public function keys()
    {
        return array_keys(self::$parameters);
    }

    /**
     * Returns true if the parameter is defined.
     *
     * @param string $key The key
     *
     * @return bool true if the parameter exists, false otherwise
     */
    public function has($key)
    {
        return array_key_exists($key, self::$parameters);
    }

    /**
     * Sets a parameter by name.
     *
     * @param string $key The key
     * @param mixed $value The value
     */
    public function set($key, $value)
    {
        self::$parameters[$key] = $value;
    }

    /**
     * Returns a parameter by name.
     *
     * @param string $key The key
     * @param mixed $default The default value if the parameter key does not exist
     * @param bool $safe_filter is use safe filter
     * @return mixed
     */
    public function get($key, $default = '', $safe_filter = true)
    {
        if (array_key_exists($key, self::$parameters)) {
            return $safe_filter ? safe_filter(self::$parameters[$key]) : self::$parameters[$key];
        }
        return $default;
    }


}