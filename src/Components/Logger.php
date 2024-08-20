<?php

namespace SoSmaller\Components;

use SoSmaller\Traits\Singleton;

/**
 * 请前往 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * 查看完整的接口规范.
 */
class Logger
{
    use Singleton;

    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * 系统无法使用。
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function emergency($message, array $context = array())
    {
        return self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * 必须立即采取行动。
     *
     * 例如: 整个网站宕机了，数据库挂了，等等。 这应该
     * 发送短信通知警告你.
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function alert($message, array $context = array())
    {
        return self::log(self::ALERT, $message, $context);
    }

    /**
     * 临界条件。
     *
     * 例如: 应用组件不可用，意外的异常。
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function critical($message, array $context = array())
    {
        return self::log(self::CRITICAL, $message, $context);
    }

    /**
     * 运行时错误不需要马上处理，
     * 但通常应该被记录和监控。
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function error($message, array $context = array())
    {
        return self::log(self::ERROR, $message, $context);
    }

    /**
     * 例外事件不是错误。
     *
     * 例如: 使用过时的API，API使用不当，不合理的东西不一定是错误。
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function warning($message, array $context = array())
    {
        return self::log(self::WARNING, $message, $context);
    }

    /**
     * 正常但重要的事件.
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function notice($message, array $context = array())
    {
        return self::log(self::NOTICE, $message, $context);
    }

    /**
     * 有趣的事件.
     *
     * 例如: 用户登录，SQL日志。
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        return self::log(self::INFO, $message, $context);
    }

    /**
     * 详细的调试信息。
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, array $context = array())
    {
        return self::log(self::DEBUG, $message, $context);
    }

    /**
     * 可任意级别记录日志。
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function log($level, $message, array $context = array())
    {
        $logFile = SYSTEM_PATH . 'storage/logs/error.log';
        $handle = fopen($logFile, "a");
        fwrite($handle, date('Y-m-d H:i:s') . ' [ ' . $level . ' ] ' . $message . PHP_EOL);
        fclose($handle);
        return true;
    }
}
