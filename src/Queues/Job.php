<?php

namespace SoSmaller\Queues;

use  Exception;

class Job
{
    private static $queuePrefix = 'queues_';

    /**
     * @desc 实例化一个job
     * @param $job
     * @return mixed
     * @throws Exception
     */
    public static function getInstance($job)
    {
        static $jobs;
        if (!isset($jobs[md5($job['class'])]) || !$jobs[md5($job['class'])]) {
            if (!class_exists($job['class'])) {
                throw new Exception(' Could not find job class ' . $job['class']);
            }
            if (!method_exists($job['class'], 'perform')) {
                throw new Exception('Job class does not contain a perform method.');
            }
            $jobs[md5($job['class'])] = new $job['class']();
        }
        return $jobs[md5($job['class'])];
    }

    /**
     * @desc 异步入队
     * @param String $class_name
     * @param String $queue_name
     * @param String $queue_params
     * @param integer $retry_count
     * @return bool
     * @throws Exception
     */
    public static function push($class_name, $queue_name, $queue_params, $retry_count = 0)
    {
        if (!is_string($class_name)) {
            throw new Exception("Queue error param class is not a object");
        }
        if ($retry_count > 3) {
            throw new Exception("Queue retry count max is 3");
        }
        $params = [
            'class' => $class_name,
            'params' => $queue_params,
            'attempts' => $retry_count,
            'id' => md5(uniqid('', true))
        ];
        return app('redis')->connection('queue')->rpush(self::$queuePrefix . $queue_name, json_encode($params));
    }

    /**
     * @desc 从队列里获取一个
     * @param string $queue_name
     * @return object job
     */
    public static function pop($queue_name = 'default')
    {
        return app('redis')->connection('queue')->lpop(self::$queuePrefix . $queue_name);
    }

    /**
     * @desc 队列状态
     * @param string $queue_name
     * @param string $status
     * @return bool
     */
    public static function status($queue_name = 'default', $status = '')
    {
        if (strlen($status)) {
            return app('redis')->connection('queue')->set(self::$queuePrefix . "status_" . $queue_name, $status, 86400);
        } else {
            return app('redis')->connection('queue')->get(self::$queuePrefix . "status_" . $queue_name);
        }
    }

}


