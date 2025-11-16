<?php

namespace IPS;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use Exception;
use IPS\storm\Profiler\Memory;
use IPS\storm\Profiler\Time;
use IPS\storm\Settings;
use Throwable;

use function class_exists;
use function debug_backtrace;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

class Db extends \IPS\_Db
{
    protected int $dbKey = 0;
    protected int $currentKey = 0;
    /**
     * @inheritdoc
     */
//    public function addColumn(string $table, array $definition): mysqli_result|bool
//    {
//        $result = parent::addColumn($table, $definition);
//        if (class_exists(\IPS\storm\Proxy::i()::class, true)) {
//            \IPS\storm\Proxy::i()->adjustModel($table);
//        }
//        return $result;
//    }

    /**
     * @inheritdoc
     */
    protected function log(string $logQuery, string $server = null): void
    {
        $this->currentKey = $this->dbKey;
        $this->dbKey++;

        parent::log($logQuery, $server);

        $log = array_pop($this->log);
        $this->log[$this->currentKey] = $log;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function preparedQuery(string $query, array $_binds, bool $read = false): string|mysqli_stmt
    {
        if (\IPS\QUERY_LOG) {
            $memory = new Memory();
            $time = new Time();
        }

        $parent = parent::preparedQuery($query, $_binds, $read);

        if (\IPS\QUERY_LOG) {
            $final = $time->end();
            $mem = $memory->end();
            $this->finalizeLog($final, $mem);
        }

        return $parent;
    }

    protected function _establishConnection(bool $read = false): mysqli
    {
        if (\IPS\QUERY_LOG) {
            $memory = new Memory();
            $time = new Time();
        }

        $return = parent::_establishConnection($read);

        if (\IPS\QUERY_LOG) {
            $final = $time->end();
            $mem = $memory->end();
            $this->finalizeLog($final, $mem);
        }

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function query(string $query, int $result_mode = MYSQLI_STORE_RESULT, bool $read = true): mysqli_result|bool
    {
        if (\IPS\QUERY_LOG) {
            $memory = new Memory();
            $time = new Time();
        }

        try {
            $parent = parent::query($query, $result_mode, $read);
        } catch (\Exception | Throwable $e) {
            throw new \IPS\Db\Exception($this->error, $this->errno);
        }

        if (\IPS\QUERY_LOG) {
            $final = $time->end();
            $mem = $memory->end();
            $this->finalizeLog($final, $mem);
        }

        return $parent;
    }

    /**
     * @param $time
     * @param $mem
     */
    protected function finalizeLog($time, $mem)
    {
        $id = $this->currentKey;
        $this->log[$id]['time'] = $time;
        $this->log[$id]['mem'] = $mem;
    }
}
