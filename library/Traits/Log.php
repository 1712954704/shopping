<?php
namespace library\Traits;

interface ILogHandler2
{
    public function write($msg);

}

class CLogFileHandler2 implements ILogHandler2
{
    private $handle = null;

    public function __construct($file = '')
    {
        $this->handle = fopen($file, 'a');
    }

    public function write($msg)
    {
        fwrite($this->handle, $msg, 4096);
    }

//    public function __destruct()
//    {
//        fclose($this->handle);
//    }
}

/**
 * 日志类
 */
class Log
{
    private $handler = null;
    private $level = 15;

    private static $instance = null;

//    private function __construct()
//    {
//    }
//
//    private function __clone()
//    {
//    }

    public static function Init($handler = null, $level = 15)
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
            self::$instance->__setHandle($handler);
            self::$instance->__setLevel($level);
        }
        return self::$instance;
    }


    private function __setHandle($handler)
    {
        $this->handler = $handler;
    }

    private function __setLevel($level)
    {
        $this->level = $level;
    }

    public static function NewDebug($body,$fun=null,$msg='')
    {
        self::$instance->new_write(1,$body,$fun,$msg);
    }

//    public static function ERROR($msg,$fun)
//    {
//        $debugInfo = debug_backtrace();
//        $stack = "[";
//        foreach ($debugInfo as $key => $val) {
//            if (array_key_exists("file", $val)) {
//                $stack .= ",file:" . $val["file"];
//            }
//            if (array_key_exists("line", $val)) {
//                $stack .= ",line:" . $val["line"];
//            }
//            if (array_key_exists("function", $val)) {
//                $stack .= ",function:" . $val["function"];
//            }
//        }
//        $stack .= "]";
//        self::$instance->write(8, $stack . $msg,$fun);
//    }

    public static function NewError($body,$fun=null,$e=null)
    {
        self::$instance->new_write(8,$body,$fun,$e);
    }

    public static function NewInfo($body,$fun=null,$e=null)
    {
        self::$instance->new_write(2,$body,$fun,$e);
    }

    public static function MqlError($body,$fun=null,$e=null)
    {
        self::$instance->new_write(3,$body,$fun,$e);
    }

    private function getLevelStr($level)
    {
        switch ($level) {
            case 1:
                return 'DEBUG';
                break;
            case 2:
                return 'INFO';
                break;
            case 3:
                return 'MYSQL_ERROR';
                break;
            case 8:
                return 'ERROR';
                break;
            default:
                break;
        }
    }

    protected function write($level, $msg,$fun)
    {
        if (($level & $this->level) == $level) {
            $msg = '[' . date('Y-m-d H:i:s') . '][' . $this->getLevelStr($level) . '] ['.$fun.']' .PHP_EOL. $msg .PHP_EOL;
            $this->handler->write($msg);
        }
    }

    /**
     *
     * 2022/12/1
     * @param $level
     * @param $body
     * @param $fun
     * @return void
     */
    protected function new_write($level, $body,$fun,$e=null)
    {
        if(!empty($e)){
            $exception = [
                'code'=>$e->getCode(),
                'message'=>$e->getMessage(),
                'file'=>$e->getFile(),
                'line'=>$e->getLine(),
            ];
        }

        $server= $body['_SERVER']?? null;
        $header= $body['Header']?? null;
        unset($body['_SERVER']);
        unset($body['Header']);

//        $return_content = \Common::cut_middle_str(json_encode($body,JSON_UNESCAPED_UNICODE|JSON_HEX_APOS), GET_CONFIG['response_body_keep_length'] ?? 1000);
        $return_content = \Common::cut_middle_str(json_encode($body,JSON_UNESCAPED_UNICODE|JSON_HEX_APOS), 1000);

        $data = [
            'fun'=>$fun,
            'time'=>time(),
            'data'=>date('Y-m-d H:i:s'),
            'ip'=>\Common::get_ip2(),
            'level'=>$this->getLevelStr($level),
            'server'=>$server,
            'header'=>$header,
            'exception'=>$exception ?? null,
            'body'=>$return_content,
//            'body_json' => json_encode($body),
//            'unique_id'=>$_SERVER['UNIQUE_ID']?? 0
        ];
        $this->handler->write(json_encode($data,JSON_UNESCAPED_UNICODE).PHP_EOL.PHP_EOL);
    }

    public function object_to_array($obj)
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($_arr as $key => $val) {
            $val = (is_object($val)) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }
}

if(PHP_SAPI === 'cli'){
    //初始化cli模式日志
    $path = ROOT_PATH."temp/log";
    $tmpPath = $path."/cli_" . date('Y-m-d') . '.log';
}else{
    //初始化日志
    $path = ROOT_PATH."temp/log";
    $tmpPath = $path."/log_" . date('Y-m-d') . '.log';
}

if(!file_exists($path)){
    throw new \Exception("temp/log 文件夹不存在!");
}

$logHandler = new CLogFileHandler2($tmpPath);
$log = Log::Init($logHandler, 15);

