<?php

namespace SoSmaller;

/**
 * Class Crontab
 * @desc php crontab
 */
class Crontab
{
    /**
     * @desc  check time is run
     * @param $rule
     * @param $time
     * @return bool
     */
    private function rule($rule, $time)
    {
        //$s = date('s', $time);//秒
        $i = date('i', $time);//分
        $h = date('H', $time);//时
        $d = date('d', $time);//日
        $m = date('m', $time);//月
        $w = date('w', $time);//周
        $run_time = explode(' ', $rule);
        //$data[] = T($run_time[0],$s,'s');
        $data[] = $this->analysis($run_time[0], $i, 'i');
        $data[] = $this->analysis($run_time[1], $h, 'h');
        $data[] = $this->analysis($run_time[2], $d, 'd');
        $data[] = $this->analysis($run_time[3], $m, 'm');
        $data[] = $this->analysis($run_time[4], $w, 'w');
        return in_array(false, $data) ? false : true;
    }

    //解析单个时间规则细节
    private function analysis($rule, $time, $timeType)
    {
        if (is_numeric($rule)) {
            return $rule == $time;
        } elseif (strstr($rule, ',')) {
            $iArr = explode(',', $rule);
            return in_array($time, $iArr) ? true : false;
        } elseif (strstr($rule, '/') && !strstr($rule, '-')) {
            list($left, $right) = explode('/', $rule);
            return in_array($left, array('*', 0)) && ($time % $right == 0);
        } elseif (strstr($rule, '/') && strstr($rule, '-')) {
            list($left, $right) = explode('/', $rule);
            if (strstr($left, '-')) {
                return self::analysisRank($left, $right, $time, $timeType);
            }
        } elseif (strstr($rule, '-')) {
            list($left, $right) = explode('-', $rule);
            return $time >= $left && $time <= $right;
        } elseif ($rule == '*') {
            return true;
        }
        return false;

    }

    /**
     * @param $rank
     * @param $num
     * @param $time
     * @param $timeType
     * @return bool
     */
    private function analysisRank($rank, $num, $time, $timeType)
    {
        $temp = [];
        $type = ['i' => 59, 'h' => 23, 'd' => 31, 'm' => 12, 'w' => 6];
        list($left, $right) = explode('-', $rank);
        if ($left < $right) {
            for ($i = $left; $i <= $right; $i = $i + $num) {
                $temp[] = $i;
            }
        }
        if ($left > $right) {
            for ($i = $left; $i <= $type[$timeType] + $right; $i = $i + $num) {
                $temp[] = $i > $type[$timeType] ? $i - $type[$timeType] : $i;
            }
        }
        return in_array($time, $temp) ? true : false;
    }

    public function schedule()
    {
        return [];
    }

    /**
     * @desc run schedule
     * @return bool
     */
    public function handle()
    {
        $time = time();
        if ($schedule = $this->schedule()) {
            foreach ($schedule as $command => $rule) {
                if ($command && $rule) {
                    if ($this->rule($rule, $time)) {
                        $command = 'cd ' . SYSTEM_PATH . ' && php artisan ' . $command . ' > /dev/null 2>&1 &';
                        pclose(popen($command, 'r'));
                    }
                }
            }
        }
        return true;
    }
}