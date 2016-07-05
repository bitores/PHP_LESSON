<?php
/**
 * Logger 日记抽象类
 *
 * - 对系统的各种情况进行纪录，具体存储媒介由实现类定义
 * - 日志分类型，不分优先级，多种类型可按并组合
 *
 *
 *      //保存全部类型的日记
 *      $logger = new Logger_Mock(Logger::LOG_LEVEL_DEBUG | Logger::LOG_LEVEL_INFO | Logger::LOG_LEVEL_ERROR);
 *
 *      //开发调试使用，且带更多信息
 *      $logger->debug('this is bebug test', array('name' => 'mock', 'ver' => '1.0.0'));
 *
 *      //业务场景使用
 *      $logger->info('this is info test', 'and more detail here ...');
 *
 *      //一些不该发生的事情
 *      $logger->error('this is error test');
 */

 abstract class Logger {

    /**
     * @var int $logLevel 多个日记级别
     */
    protected $logLevel = 0;

    /**
     * @var int LOG_LEVEL_DEBUG 调试级别
     */
    const LOG_LEVEL_DEBUG = 1;
    
    /**
     * @var int LOG_LEVEL_INFO 产品级别
     */
    const LOG_LEVEL_INFO = 2;
    
    /**
     * @var int LOG_LEVEL_ERROR 错误级别
     */
    const LOG_LEVEL_ERROR = 4;

    public function __construct($level) {
        $this->logLevel = $level;
    }

    /**
     * 日记纪录
     *
     * 可根据不同需要，将日记写入不同的媒介
     *
     * @param string $type 日记类型，如：info/debug/error, etc
     * @param string $msg 日记关键描述
     * @param string/array $data 场景上下文信息
     * @return NULL
     */
    abstract public function log($type, $msg, $data);

    /**
     * 应用产品级日记
     * @param string $msg 日记关键描述
     * @param string/array $data 场景上下文信息
     * @return NULL
     */
    public function info($msg, $data = NULL) {
        if (!$this->isAllowToLog(self::LOG_LEVEL_INFO)) {
            return;
        }

        $this->log('info', $msg, $data);
    }

    /**
     * 开发调试级日记
     * @param string $msg 日记关键描述
     * @param string/array $data 场景上下文信息
     * @return NULL
     */
    public function debug($msg, $data = NULL) {
        if (!$this->isAllowToLog(self::LOG_LEVEL_DEBUG)) {
            return;
        }

        $this->log('debug', $msg, $data);
    }

    /**
     * 系统错误级日记
     * @param string $msg 日记关键描述
     * @param string/array $data 场景上下文信息
     * @return NULL
     */
    public function error($msg, $data = NULL) {
        if (!$this->isAllowToLog(self::LOG_LEVEL_ERROR)) {
            return;
        }

        $this->log('error', $msg, $data);
    }

    /**
     * 是否允许写入日记，或运算
     * @param int $logLevel
     * @return boolean
     */
    protected function isAllowToLog($logLevel) {
        return (($this->logLevel & $logLevel) != 0) ? TRUE : FALSE;
    }
}
/**
 * Logger_Explorer 直接输出日记到控制台的日记类 
 * 
 * - 测试环境下使用
 * 
 */

class Logger_Explorer extends Logger {

	public function log($type, $msg, $data) {
        $msgArr = array();
        $msgArr[] = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
        $msgArr[] = strtoupper($type);
        $msgArr[] = str_replace(PHP_EOL, '\n', $msg);
        if ($data !== NULL) {
            $msgArr[] = is_array($data) ? json_encode($data) : $data;
        }

        $content = implode('|', $msgArr) . PHP_EOL;

        echo "\n", $content, "\n";
	}
}




/**
 * Logger_File 文件日记纪录类
 *
 * - 将日记写入文件，文件目录可以自定义
 * 
 * <br>使用示例：<br>
 *      //目录为./Runtime，且保存全部类型的日记
 *      $logger = new Logger_File('./Runtime', Logger::LOG_LEVEL_DEBUG | Logger::LOG_LEVEL_INFO | Logger::LOG_LEVEL_ERROR);
 *
 *      //日记会保存在在./Runtime/debug_log/目录下
 *      $logger->debug('this is bebug test');
 *
 */

class Logger_File {

    protected $logFolder;
    protected $dateFormat;

    protected $logFile;

    public function __construct($logFolder, $level, $dateFormat = 'Y-m-d H:i:s') {
        $this->logFolder = $logFolder;
        $this->dateFormat = $dateFormat;

        parent::__construct($level);

        $this->init();
    }

    protected function init() {
        $folder = $this->logFolder 
            . DIRECTORY_SEPARATOR . 'log' 
            . DIRECTORY_SEPARATOR . date('Ym', $_SERVER['REQUEST_TIME']);
        
        if (!file_exists($folder)) {
            mkdir($folder . '/', 0777, TRUE);
        }

        $this->logFile = $folder 
            . DIRECTORY_SEPARATOR . date('Ymd', $_SERVER['REQUEST_TIME']) . '.log';

        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0777);
        }
    }

    public function log($type, $msg, $data) {
        $msgArr = array();
        $msgArr[] = date($this->dateFormat, $_SERVER['REQUEST_TIME']);
        $msgArr[] = strtoupper($type);
        $msgArr[] = str_replace(PHP_EOL, '\n', $msg);
        if ($data !== NULL) {
            $msgArr[] = is_array($data) ? json_encode($data) : $data;
        }

        $content = implode('|', $msgArr) . PHP_EOL;

        file_put_contents($this->logFile, $content, FILE_APPEND);
    }
}