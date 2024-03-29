<?php

namespace SoSmaller\Components;

use PDOException;
use Exception;
use PDO;

/**
 * DB
 */
class Db
{
    const DB_READ = 'read';
    const DB_WRITE = 'write';

    private $_pdo; //对象pdo
    private $_db = 'default';
    private $_type = 'read'; // 类型
    private static $_last_insert_id = ''; //最后插入id
    private static $_last_sql = ''; //最后一条sql
    private static $_singleton = []; //单例模式 pdo

    /**
     * 获取pdo
     * @param String $db pdo
     * @param String $type 类型 write/read
     * @return mixed
     * @throws Exception
     */
    public function connection($db = 'default', $type = 'read')
    {
        if (isset(self::$_singleton[$type][$db]) && self::$_singleton[$type][$db]) {
            $pdo = self::$_singleton[$type][$db];
        } else {
            $pdo = self::getPdo($db, $type);
        }
        if (!$pdo || !self::pdoPing($pdo)) {
            if ($type == self::DB_READ) {
                $type = self::DB_WRITE;
            }
            $pdo = self::getPdo($db, $type, true);
        }
        if ($pdo) {
            self::$_singleton[$type][$db] = $pdo;
            $this->_type = $type;
            $this->_db = $db;
            $this->_pdo = $pdo;
        } else {
            unset(self::$_singleton[$type][$db]);
            throw new Exception('Mysql ' . $db . ' is connection failed');
        }
        return $this;
    }

    /**
     * 获取实例
     * @param string $db
     * @param String $type
     * @param bool $retry
     * @return mixed
     * @throws Exception
     */
    private function getPdo($db = 'default', $type = 'read', $retry = false)
    {
        try {
            $config = config('database.mysql');
            $config = $config[$db][$type];
            $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'];
            $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true];
            if (isset($config['timeout']) && $config['timeout']) {
                $opt[PDO::ATTR_TIMEOUT] = $config['timeout'];
            }
            if (isset($config['persistent']) && strlen($config['persistent'])) {
                $opt[PDO::ATTR_PERSISTENT] = true;
            }
            $pdo = new PDO($dsn, $config['username'], $config['password'], $opt);
            $charset = (isset($config['charset']) && $config['charset']) ? $config['charset'] : 'utf8';
            $timezone = (isset($config['timezone']) && $config['timezone']) ? "set time_zone='{$config['timezone']}';" : '';
            $pdo->exec("set names {$charset};{$timezone}");
            return $pdo;
        } catch (PDOException $e) {
            if ($retry) {
                throw $e;
            }
        } catch (Exception $e) {
            if ($retry) {
                throw $e;
            }
        }
        return false;
    }

    /**
     * @param $sql
     * @param array $prepare
     * @param string $query_model row or all
     * @return array
     * @throws Exception
     */
    public function query($sql, $prepare = [], $query_model = 'row')
    {
        $result = [];
        $data = self::execute($sql, $prepare);
        if ($data) {
            if ($query_model == 'row') {
                $result = $data->fetch(PDO::FETCH_ASSOC);
            } else {
                $result = $data->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        return $result;
    }

    /**
     * @param $sql
     * @param array $prepare
     * @param boolean $getId
     * @return int
     * @throws Exception
     */
    public function exec($sql, $prepare = [], $getId = false)
    {
        try {
            $data = self::execute($sql, $prepare, $getId);
            return $data ? ($getId ? self::$_last_insert_id : $data->rowCount()) : 0;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $sql
     * @param array $prepare
     * @param boolean $getId
     * @return mixed
     * @throws Exception
     */
    private function execute($sql, $prepare = [], $getId = false)
    {
        try {
            !$this->_pdo && $this->connection($this->_db, $this->_type);
            $result = $this->_pdo->prepare($sql);
            $result->execute($prepare);
            self::$_last_sql = $sql;
            if ($result && $getId) {
                self::$_last_insert_id = $this->_pdo->lastInsertId();
            }
            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @desc 检查连接是否可用
     * @param Object $pdo 数据库连接
     * @return Boolean
     */
    private function pdoPing($pdo)
    {
        try {
            return $pdo ? $pdo->getAttribute(PDO::ATTR_SERVER_INFO) : false;
        } catch (PDOException $e) {
            return false;
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getLastSql()
    {
        return self::$_last_sql;
    }

    /**
     * @return mixed
     */
    public function getLastInsertId()
    {
        return self::$_last_insert_id;
    }

}