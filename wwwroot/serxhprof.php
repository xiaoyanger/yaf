<?php
class servxhprof
{
    static public function isExtension(){
        return extension_loaded('xhprof');
    }

    static public function begin()
    {
        if(!self::isExtension()) die("没有xhprof扩展!"); // 可以return掉 则不影响正常程序
        //XHPROF_FLAGS_NO_BUILTINS 使得跳过所有内置（内部）函数
        //XHPROF_FLAGS_CPU使输出的性能数据中添加 CPU 数据，
        //XHPROF_FLAGS_MEMORY
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY + XHPROF_FLAGS_NO_BUILTINS);
    }

    static public function end()
    {
        if(!self::isExtension()) die("没有xhprof扩展!"); // 可以return掉 则不影响正常程序
        $x_hprof_data = xhprof_disable();
        include_once(dirname(__FILE__)."/xhprof/utils/xhprof_lib.php");
        include_once(dirname(__FILE__)."/xhprof/utils/xhprof_runs.php");
        $xhprof_runs = new XHProfRuns_Default();
        $run_id = $xhprof_runs->save_run($x_hprof_data, "xhprof_testing");
        echo "http://test.xhprof.com/index.php?run={$run_id}&source=xhprof_testing";
    }
}