<?php
//设置错误处理
set_error_handler('error_handler');
//设置异常处理
set_exception_handler('exception_handler');
//注册shutdown 函数
register_shutdown_function('showdown_handler');


/**
 * @param $code
 * @param $err
 * @param $file
 * @param $line
 */
function error_handler($code, $err, $file, $line)
{
    throw new Exception($err, $code);
}

function exception_handler($e)
{
    throw new Exception($e->getMessage(), $e->getCode());
}

function showdown_handler()
{
    if ($error = error_get_last()) {
        exception(new Exception($error['message'],$error['type']));
        //throw new Exception($error['message'], $error['type']);
    }
}

/**
 * @param string $key
 * @param string $defalut
 * @return string
 */
function env($key, $defalut = '')
{
    static $env;
    if (!$env) {
        $env = parse_ini_file(SYSTEM_PATH . '.env.conf');
        if (isset($env['APP_ENV']) && $env['APP_ENV']) {
            $env_file = SYSTEM_PATH . '.env.' . $env['APP_ENV'];
            file_exists($env_file) && $env = (parse_ini_file($env_file) + $env);
        }
    }
    return isset($env[$key]) ? $env[$key] : $defalut;
}

/**
 * @param $key
 */
function app($key)
{
    $class = 'SoSmaller\\Components\\' . ucwords($key);
    return new $class;
}

/**
 * @param $key
 * @return mixed|void
 */
function config($key)
{
    return \SoSmaller\Components\Config::instance()->getConfig($key);
}

/**
 * @param $exception
 * @return mixed
 */
function exception(Exception $e)
{
    $class = '\\App\\Exception\\ExceptionHandler';
    if (class_exists($class)) {
        return (new $class)->report($e);
    } else {
        exit($e->getMessage().PHP_EOL.$e->getTraceAsString());
    }
}

/**
 * filter an param from the request.
 * @param mixed $param
 * @return mixed
 */
function safe_filter($param)
{
    if (!$param) return $param;

    if (is_array($param)) {
        foreach ($param as &$v) {
            if (is_array($v)) {
                $v = safe_filter($v);
            } else {
                $v = str_replace(PHP_EOL, '', trim($v));// remove CR(0a) and LF(0b) and TAB(9) NULL 空格
                $v = htmlspecialchars(strip_tags($v), ENT_QUOTES);//去除 HTML 和 PHP 标记并转换为 HTML 实体
            }
        }
    } else {
        $param = str_replace(PHP_EOL, '', trim($param));// remove CR(0a) and LF(0b) and TAB(9) NULL 空格
        $param = htmlspecialchars(strip_tags($param), ENT_QUOTES);//去除 HTML 和 PHP 标记并转换为 HTML 实体
    }
    return $param;
}
