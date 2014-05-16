<?php

class ParallelFor_Test extends \PHPUnit_Framework_TestCase
{
    public function testNumChilds()
    {
        $p = new ParallelFor();
        $p->setNumChilds(10);
    }
    
    public function testInvalidNumChilds()
    {
        $this->setExpectedException('InvalidArgumentException');
        
        $p = new ParallelFor();
        $p->setNumChilds('not integer');
    }
    
    public function testSimpleArray()
    {
        $func = function($chunks, $opt) {
            return $chunks;
        };
        
        $p = new ParallelFor();
        
        $data = [1,2,3,4,5];
        $result = $p->run($data, $func);
        sort($data);
        sort($result);
        
        $this->assertEquals($data, $result);
    }
    
    public function testAssocArray()
    {
        $func = function($chunks, $opt) {
            return $chunks;
        };
        
        $p = new ParallelFor();
        
        $data = [
            'testkey1' => 'val1',
            'testkey2' => 'val2',
            'testkey3' => 'val3',
            'testkey4' => 'val4',
            ];
        $result = $p->run($data, $func);
        
        $this->assertEquals($data, $result);
    }
    
    public function testHugeArray()
    {
        $func = function($chunks, $opt) {
            return $chunks;
        };
        
        $p = new ParallelFor();

        $data = array();
        for($i = 0; $i < 1000; $i++) {
            $data[] = mt_rand();
        }
        
        $result = $p->run($data, $func);
        sort($data);
        sort($result);
        
        $this->assertEquals($data, $result);
    }
    
    
    public function testAggregator()
    {
        $func = function($chunks, $opt) {
            return $chunks;
        };

        $aggregator = function(&$result, $data) {
            if( ! is_numeric($result)) {
                $result = 0;
            }
            $result += array_sum($data);
        };
        
        $p = new ParallelFor();
        $p->setAggregator($aggregator);
        
        $sum = 0;
        $data = array();
        for($i = 0; $i < 10; $i++) {
            $val = mt_rand();
            $data[] = $val;
            $sum += $val;
        }
        
        $result = $p->run($data, $func);
        $this->assertEquals($sum, $result);
    }

    public function testInvalidData()
    {
        $this->setExpectedException('InvalidArgumentException');
        
        $func = 'not function';
        $data = array(0 => 1);
        
        $p = new ParallelFor();
        $p->run($data, $func);
    }
}
