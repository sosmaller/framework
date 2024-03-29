<?php

namespace SoSmaller\Queues;

use Exception;

class Worker
{
    /**
     * @desc start worker
     * @param array $params
     */
    public static function run($params)
    {
        $queue = (isset($params['queue']) && $params['queue']) ? $params['queue'] : 'default';
        //$count = (isset($params['count']) && $params['count']) ? $params['count'] : 1;
        $logger = (isset($params['logger']) && $params['logger']) ? $params['logger'] : 1;
        $interval = (isset($params['interval']) && $params['interval']) ? $params['interval'] : 1000;
        $run = (isset($params['run']) && strlen($params['run'])) ? $params['run'] : 1;
        if (!$run) {
            Job::status($queue, 0);
            self::log($queue . ' is exit...', $logger);
            return;
        }

        Job::status($queue, 1);
        self::log($queue . ' is run...', $logger);
        while (true) {
            if (!Job::status($queue)) break;

            if (!$job = Job::pop($queue)) {
                //self::log($queue . ' is sleep...', $logger);
                usleep($interval * 1000);
                continue;
            }

            if (!$job = json_decode($job, true)) continue;

            try {
                self::work($job);
            } catch (Exception $e) {
                if ($job['attempts'] > 0) {
                    $job['attempts'] = $job['attempts'] - 1;
                    Job::push($job['class'], $queue, $job['params'], $job['attempts']);
                    break;
                } else {
                    self::log($queue . ' is failed... ' . $e->getMessage(), $logger);
                    Job::push($job['class'], 'failed_' . $queue, $job['params'], 1);
                    break;
                }
            }
        }
    }

    /**
     * @desc worker 执行
     * @param Object $job 信息
     * @throws Exception
     */
    public static function work($job)
    {
        try {
            $instance = Job::getInstance($job);
            $instance->params = $job['params'];
            //if (method_exists($instance, 'before')) {
                //$instance->before();
            //}
            $instance->perform();
            //if (method_exists($instance, 'after')) {
                //$instance->after();
            //}
        } catch (Exception $e) {
            throw $e; //worker抛出异常
        }
    }

    /**
     * @desc worker 日志记录
     * @param $message
     * @param int $logger
     */
    private static function log($message, $logger = 1)
    {
        $date = date('Ymd');
        $logFile = SYSTEM_PATH . 'storage/logs/worker-' . $date . '.log';
        if ($logger) {
            file_put_contents($logFile, date('Ymd H:i:s') . ' --- ' . $message . PHP_EOL, FILE_APPEND);
        }
    }

}


