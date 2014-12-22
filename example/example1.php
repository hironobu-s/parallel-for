<?php

$base = dirname(dirname(__FILE__));
require_once $base . DIRECTORY_SEPARATOR . 'autoload.php';

// This function simulate that It takes 100 ms for each item of the array.
$executor = function($data, $opt) {
    $result = array();
    foreach($data as $value) {
        $result[] = $value;
        
        // Wait 100ms.
        // It simulate long processing time task.
        $wait = 100000;
        usleep($wait);
    }
    return $result;
};


// prepare source data.
$data = array();
for($i = 0; $i < 50; $i++) {
    $data[] = $i;
}

// Run in a single process.
echo "runnning. please wait...\n";
$begin = microtime(true);
$executor($data, array());
echo "single: " . (microtime(true) - $begin) . " sec\n";


// Run in multi process.
// Probabry, it is short time than a single process.
echo "runnning. please wait...\n";
$begin = microtime(true);

$p = new ParallelFor();
$p->setNumChilds(4);
$result = $p->run($data, $executor);

echo "parallel: " . (microtime(true) - $begin) . " sec\n";
