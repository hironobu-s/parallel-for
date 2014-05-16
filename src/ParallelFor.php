<?php

/**
 * The ParallelFor class provide a way to iterate an array using multi process.
 *
 * @author gc37
 * @copyright under MIT Licence
 */
class ParallelFor
{
    private $num_child = 4;
    private $aggregator = null;

    /**
     * constructor
     */
    public function __construct()
    {
        // pcntl_fork をサポートしていない場合は使用できない
        // PHP SAPI has to be compiled with pcntl module.
        if( ! function_exists('pcntl_fork')) {
            throw new RuntimeException('This SAPI does not support pcntl functions.');
        }
        
        // デフォルトで array_merge を使用した aggregator を使用する
        // Aggregator function using array_merge() is default.
        $func = function(&$result, $data) {
            if( ! is_array($result)) {
                $result = array();
            }
            $result = array_merge($result, $data);
        };
        
        $this->setAggregator($func);
    }


    /**
     * Set the number of childs.
     *
     * @params int $num
     */
    public function setNumChilds($num)
    {
        if( ! is_numeric($num)) {
            throw new InvalidArgumentException('Argument #1($num) must be integer.');
        }
        
        $this->num_child = $num;
    }

    /**
     * Set aggregator funtion
     *
     * @param closure $fund
     */
    public function setAggregator($func)
    {
        if( ! $func instanceof Closure) {
            throw new InvalidArgumentException('Argument #2($callback) must be closure.');
        }
        $this->aggregator = $func;
    }

    /**
     * Run
     *
     * @param array $data
     * @param closure $executor  executor function.
     * @param array $opt         options for the executor function.
     */
    public function run(array $data, $executor, $opt = array()) {
        if( ! $executor instanceof Closure) {
            throw new InvalidArgumentException('Argument #2($executor) must be closure.');
        }
        
        // 結果を書き出す一時ファイルのプレフィクス
        // Prefix of the shared temporary files.
        $uniqid = uniqid(get_class($this), true);
        $shared_file_prefix = '.parallel_for_' . $uniqid;
  
        // 一つの子プロセスが処理する要素数を決定
        // The number of items per one child process
        $num_data = count($data);
        $count_per_child = ceil($num_data / $this->num_child);
        
        // 実行中の子プロセス数
        // The number of process working on.
        $num_works = 0;
        
        // 実行
        // Run
        for($i = 0; $i < $this->num_child; $i++) {

            // Forks current process.
            $pid = pcntl_fork();
            if($pid) {
                $num_works++;
                continue;
            }
            
            // この子プロセスが処理する要素を $data からコピー
            // Copy item from $list to  $child_list.
            // $child_list will process the current child.
            $offset = $i * $count_per_child;
            if($offset + $count_per_child >= $num_data) {
                $limit = $num_data - $offset;
            } else {
                $limit = $count_per_child;
            }
            
            $child_data = array_slice($data, $offset, $limit);
            
            // コールバック関数(executor)を実行
            // Run the executor.
            $child_result = $executor($child_data, $opt);
      
            // 結果を一時ファイルに書き込み
            // Write the result to shared temporary file.
            $shared_file = $shared_file_prefix . getmypid();
            file_put_contents($shared_file, serialize($child_result));
    
            // 子プロセス終了
            // The current process is terminated.
            exit;
        }
  
        // 子プロセスの終了を待ちつつ処理結果をマージ
        // Merge the result while waiting for child process to be terminaled.
        $result = null;
        while($num_works > 0) {
            $stat = null;
            $pid = pcntl_wait($stat);
    
            $shared_file = $shared_file_prefix . $pid;
            $data = unserialize(file_get_contents($shared_file));
            
            // 結果を集約する
            // Aggregate the result.
            $method = $this->aggregator;
            $method($result, $data);
            
            // 一時ファイルを削除
            // remove the shared file.
            unlink($shared_file);
            
            $num_works--;
        }
        
        return $result;
    }
}

