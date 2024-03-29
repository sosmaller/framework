<?php

namespace SoSmaller\Components;

use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;

class Mongodb
{

    private $_mongo; //对象
    private $_name = 'default'; // 数据库对象名称
    private $_database = ''; //连的数据库
    private static $_singleton = []; //单例模式

    /**
     * @param string $name
     * @return $this
     * @throws Exception
     */
    public function connection($name = 'default')
    {
        $config_mongo = config('database.mongo');
        $config = $config_mongo[$name];
        try {
            if (isset(self::$_singleton[$name]) && self::$_singleton[$name]) {
                $mongo = self::$_singleton[$name];
            } else {
                $mongo = $this->getMongo($config);
            }
        } catch (Exception $e) {
            throw $e;
        }
        if ($mongo) {
            $this->_name = $name;
            $this->_database = $config['database'];
            $this->_mongo = $mongo;
            self::$_singleton[$name] = $mongo;
        } else {
            throw new Exception('MongoDB ' . $name . ' is connection failed');
        }
        return $this;
    }

    /**
     * @param $config
     * @param bool $retry
     * @return bool|Manager
     * @throws Exception
     */
    private function getMongo($config, $retry = false)
    {
        $timeout = (int)$config['timeout'];
        try {
            $options = ['connectTimeoutMs' => $timeout, 'socketTimeoutMs' => $timeout, 'maxWaitTimeMs' => $timeout];
            return new  Manager($config['dsn'], $options);
        } catch (Exception $e) {
            if ($retry) {
                throw $e;
            }
        }
        return false;
    }

    /**
     * 查询数据
     * @param string $collection 表名
     * @param array $filter 查询条件
     * @param array $options 参数配置
     * @return mixed
     * @throws Exception
     */
    public function select($collection, $filter = [], $options = [])
    {
        $result = [];
        try {
            $result = $this->query($collection, $filter, $options);
            if ($result == 'fail') {
                $this->connection($this->_name);
                $result = $this->query($collection, $filter, $options, true);
            }
        } catch (Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 查询数据
     * @param string $collection 表名
     * @param array $filter 查询条件
     * @param array $options 参数配置
     * @return mixed
     * @throws Exception
     */
    private function query($collection, $filter = [], $options = [], $retry = false)
    {
        $list = [];
        try {
            $query = new Query($filter, $options);
            $cursor = $this->_mongo->executeQuery($this->_database . '.' . $collection, $query);
            $result = $cursor->toArray();
            if ($result) {
                foreach ($result as $value) {
                    $list[] = (array)$value;
                }
            }
        } catch (Exception $e) {
            if ($retry) {
                throw $e;
            } else {
                return 'fail';
            }
        }
        return $list;
    }


    /**
     * 插入数据
     * @param string $collection 表名
     * @param array $data 插入数据
     * @param bool $getId 是否返回id
     * @return mixed
     * @throws Exception
     */
    public function insert($collection, $data, $getId = false)
    {
        $ids = [];

        $bulk = new BulkWrite();
        if (is_array(current($data))) {
            foreach ($data as $item) {
                $_id = new ObjectId();
                $item['_id'] = $_id;
                $bulk->insert($item);
                $_id = (array)$_id;
                $ids[] = $_id['oid'];
            }
        } else {
            $_id = new ObjectId();
            $data['_id'] = $_id;
            $bulk->insert($data);
            $_id = (array)$_id;
            $ids = $_id['oid'];
        }

        if ($this->exec($collection, $bulk)) {
            return $getId ? $ids : true;
        }
        return false;
    }

    /**
     * 更新数据
     * @param string $collection 表名
     * @param array $filter 查询条件
     * @param array $data 更新数据
     * @return bool
     * @throws Exception
     */
    public function update($collection, $filter, $data)
    {
        $bulk = new BulkWrite();
        $bulk->update($filter, $data, ['multi' => true]);
        return $this->exec($collection, $bulk);
    }

    /**
     * 删除数据
     * @param string $collection 表名
     * @param array $filter 查询条件
     * @return bool
     * @throws Exception
     */
    public function delete($collection, $filter)
    {
        $bulk = new BulkWrite();
        $bulk->delete($filter);
        return $this->exec($collection, $bulk);
    }

    /**
     * @param string $collection
     * @param array $filter
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    public function count($collection, $filter = [], $options = [])
    {
        try {
            $query = new Query($filter, $options);
            $command = new Command(["count" => $collection, "query" => $query]);
            $count = $this->_mongo->executeCommand($this->_database, $command)->toArray()[0]->n;
        } catch (Exception $e) {
            throw  $e;
        }
        return $count;
    }

    /**
     * @param $collection
     * @param $bulk
     * @return bool
     * @throws Exception
     */
    private function exec($collection, $bulk)
    {
        try {
            !$this->_mongo && $this->connection($this->_name);
            $this->_mongo->executeBulkWrite($this->_database . '.' . $collection, $bulk);
        } catch (BulkWriteException $e) {
            throw $e;
        } catch (Exception $e) {
            throw $e;
        }
        return true;
    }

    /**
     * 聚合搜索
     * @param $command
     * @return mixed
     * @throws Exception
     */
    public function aggregate($command)
    {
        try {
            $command = new Command($command);
            $data = $this->_mongo->executeCommand($this->_database, $command)->toArray();
        } catch (Exception $e) {
            throw  $e;
        }
        return $data;
    }

}