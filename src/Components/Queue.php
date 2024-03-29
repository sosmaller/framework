<?php

namespace Sosmall\Components;


use Exception;

/**
 * Class Queue
 * @package app\utils\components
 * @desc insert into queue
 */
class Queue
{
    /**
     * @used app('queue')->onQueue(\app\jobs\TestJob::class,['name'=>'yangchengsheng']);
     * @param string $queue_name
     * @param $class_name
     * @param $queue_params
     * @param $retry_count
     * @return string
     * @throws Exception
     */
    public function onQueue($class_name, $queue_params, $queue_name = 'default', $retry_count = 0)
    {
        try {
            if (env('QUEUE_DRIVER') == 'sync') {
                return \Sosmall\Queues\Worker::work(['class' => $class_name, 'params' => $queue_params]);
            } else {
                $queue_id = \Sosmall\Queues\Job::push($class_name, $queue_name, $queue_params, $retry_count);
                return $queue_id;
            }
        } catch (Exception $e) {
            throw  new  Exception("Queue error " . $e->getMessage());
        }
        return false;
    }

    /**
     * queue – 队列名称 默认是default， eg:queue=mail。
     *
     * count – 设定 worker 数量，默认是1 ，eg：count=5 。
     *
     * logger – 设定 log，默认是0， eg: logger=1。
     *
     * interval – 队列为空时休息的毫秒数，默认是1秒， eg：interval=1000 。
     *
     * run -  执行和停止. 默认是true，停止是false, eg:run=0
     *
     * 所以，你的指令最后可能会变这样：
     *
     * php artisan queue queue=default count=1 logger=1 interval=1 run=1
     * php artisan queue queue=default run=0
     * @throws Exception
     */
    public function handle()
    {
        $params = [];
        $argv = $_SERVER['argv'];
        foreach ($argv as $key => $val) {
            if (strpos($val, '=')) {
                $param = explode('=', $val);
                if (isset($param[0]) && isset($param[1])) {
                    $params[$param[0]] = $param[1];
                }
            }
        }
        if (!$params) {
            throw new  Exception("params can't be empty");
            return false;
        }
        return \Sosmall\Queues\Worker::run($params);
    }
}