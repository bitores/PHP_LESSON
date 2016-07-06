<?PHP
/**
 *  数据库中间层实现类
 */
class DB {
   
    static private $_instance = null;
    // 是否自动释放查询结果
    protected $autoFree         = false;
    // 是否显示调试信息 如果启用会在日志文件记录sql语句
    public $debug             = true;
	public $debugData             = true;
    // 是否使用永久连接
    protected $pconnect         = false;
    // 当前SQL指令
    protected $queryStr          = '';
    // 最后插入ID
    protected $lastInsID         = null;
    // 返回或者影响记录数
    protected $numRows        = 0;
    // 返回字段数
    protected $numCols          = 0;
    // 事务指令数
    protected $transTimes      = 0;
    // 错误信息
    protected $error              = '';
    // 当前连接ID
    protected $linkID            =   null;
    // 当前查询ID
    protected $queryID          = null;
    // 是否已经连接数据库
    protected $connected       = false;
    // 数据库连接参数配置
    protected $config             = '';
    // SQL 执行时间记录
    protected $beginTime;
    // 数据库表达式
    protected $comparison      = array('eq'=>'=','neq'=>'!=','gt'=>'>','egt'=>'>=','lt'=>'<','elt'=>'<=','notlike'=>'NOT LIKE','like'=>'LIKE');
    // 查询表达式
    protected $selectSql  =     'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';
    
    protected $_sql = false; //最后一条sql语句
    // protected $_table = '';
    protected $_where = '';
    protected $_limit = '';
    protected $_field = '*';
    protected $_join = '';
    protected $_having = '';
    protected $_group = '';
    protected $_order = '';
    protected $_distanct = '';
    protected $_clear = 0; //状态，0表示查询条件干净，1表示查询条件污染

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    public function __construct($config=''){
        if ( !extension_loaded('mysql') ) {
            throw_exception(L('_NOT_SUPPERT_').':mysql');
        }
        $this->config   =   $this->parseConfig($config);
    }
	public function getConfig(){
		return $this->config;
	}
	public function getLastInsID(){
		return $this->lastInsID;
	}
    /**
     +----------------------------------------------------------
     * 连接数据库方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	 
	//读取数据库配置信息
    private function parseConfig($db_config='') {
        if ( !empty($db_config) && is_string($db_config)) {
            // 如果DSN字符串则进行解析
            $db_config = $this->parseDSN($db_config);
        }elseif(is_array($db_config)) { // 数组配置
             $db_config = array(
                  'dbms'        => $db_config['db_type'],
                  'username'  => $db_config['db_user'],
                  'password'   => $db_config['db_pwd'],
                  'hostname'  => $db_config['db_host'],
                  'hostport'    => $db_config['db_port'],
                  'database'   => $db_config['db_name'],
                  'dsn'         => $db_config['db_dsn'],
                  'params'   => $db_config['db_params'],
             );
             if(isset($db_config['app_debug'])){
                $this->debugData = !!$db_config['app_debug'];
             }
             if(isset($db_config['db_debug'])){
                $this->debug = !!$db_config['db_debug'];
             }
        }elseif(empty($db_config)) {
            // 如果配置为空，读取配置文件设置
			$Conf = require("conf.php");
            if( $Conf['DB_DSN'] && 'pdo' != strtolower($Conf['DB_TYPE']) ) { // 如果设置了DB_DSN 则优先
                $db_config =  $this->parseDSN($Conf['DB_DSN']);
            }else{
                $db_config = array (
                    'dbms'        =>   $Conf['DB_TYPE'],
                    'username'  =>   $Conf['DB_USER'],
                    'password'   =>   $Conf['DB_PWD'],
                    'hostname'  =>   $Conf['DB_HOST'],
                    'hostport'    =>   $Conf['DB_PORT'],
                    'database'   =>   $Conf['DB_NAME'],
                    'dsn'          =>   $Conf['DB_DSN'],
                    'params'     =>   $Conf['DB_PARAMS'],
					'charset'    => $Conf['DB_CHARSET']
                );
                if(isset($Conf['APP_DEBUG'])){
                    $this->debugData = !!$Conf['APP_DEBUG'];
                }
                if(isset($Conf['DB_DEBUG'])){
                    $this->debug = !!$Conf['DB_DEBUG'];
                }
            }
        }
        return $db_config;
    }
	  /**
     +----------------------------------------------------------
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $dsnStr
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function parseDSN($dsnStr) {
        if( empty($dsnStr) ){return false;}
        $info = parse_url($dsnStr);
        if($info['scheme']){
            $dsn = array(
            'dbms'        => $info['scheme'],
            'username'  => isset($info['user']) ? $info['user'] : '',
            'password'   => isset($info['pass']) ? $info['pass'] : '',
            'hostname'  => isset($info['host']) ? $info['host'] : '',
            'hostport'    => isset($info['port']) ? $info['port'] : '',
            'database'   => isset($info['path']) ? substr($info['path'],1) : ''
            );
        }else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/',trim($dsnStr),$matches);
            $dsn = array (
            'dbms'        => $matches[1],
            'username'  => $matches[2],
            'password'   => $matches[3],
            'hostname'  => $matches[4],
            'hostport'    => $matches[5],
            'database'   => $matches[6]
            );
        }
        $dsn['dsn'] =  ''; // 兼容配置信息数组
        return $dsn;
     }
	 
    public function connect() {
        if(!$this->connected) {
            $config =   $this->config;
            // 处理不带端口号的socket连接情况
            $host = $config['hostname'].($config['hostport']?":{$config['hostport']}":'');
            if($this->debugData) echo __LINE__.': connect() : $host : '.$host."<p>";
            $this->linkID = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], $config['hostport'] ? intval($config['hostport']) : 3306);
			// $this->linkID = mysql_connect( $host, $config['username'], $config['password']);
           
			if($this->debugData){
				echo __LINE__." : ";
				if(!$this->linkID){
					echo "don't inited<p>";
				}else{
					echo "inited<p>";
				}
			} 

            //检查连接是否成功
            if (mysqli_connect_errno()){
                //注意mysqli_connect_error()新特性
                die('Unable to connect!'). mysqli_connect_error();
            }

			if(!$this->linkID){
				echo "数据库连接失败，请检查数据库配置(hostname,username,password)！<p>";
			}

            if ( !$this->linkID || (!empty($config['database']) && !$this->linkID->select_db($config['database'])) ) {
                //print_r(mysql_error());
				echo "数据库连接失败，请检查数据库配置(database)！<p>";
            }
			$dbVersion = $this->linkID->get_server_info();
            if ($dbVersion >= "4.1") {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                $this->linkID->query("SET NAMES '".$config['charset']."'");
            }
            //设置 sql_model
            if($dbVersion >'5.0.1'){
                $this->linkID->query("SET sql_mode=''");
            }
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
            unset($this->config);
        }
    }

    /**
     +----------------------------------------------------------
     * 释放查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function free() {
        $this->queryID->free_result();
        $this->queryID = 0;
    }

    public function switchDB($db)
    {
        $this->linkID->select_db($db);
    }

    /**
     +----------------------------------------------------------
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function query($str='') {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        $this->Q(1);
        $this->queryID = $this->linkID->query($this->queryStr);
        $this->debug();
        if ( !$this->queryID ) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = $this->queryID->num_rows;
            return $this->getAll();
        }
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function execute($str='') {
        // echo "1A"  . $this->lastInsID . '<br/>';
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        // echo "2A"  . $this->lastInsID . '<br/>';
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        //$this->W(1);
        // echo "3A"  . $this->lastInsID . '<br/>';
        $result =   $this->linkID->query($this->queryStr) ;
        $this->debug();
        // echo "4A"  . $this->lastInsID . '<br/>';
        if ( false === $result) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = $this->linkID->affected_rows;
            if($this->linkID->insert_id){
                $this->lastInsID = $this->linkID->insert_id;
                echo "<br/>lastInsID_" . $this->lastInsID . ':'.$this->linkID->insert_id;
            }
            
            
            return $this->numRows;
        }
    }

    /*
     *  doSQL   
    */

    public function doSQL($sql=''){
        $queryIps = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK'; 
        if (preg_match('/^\s*"?(' . $queryIps . ')\s+/i', $sql)) { 
            return $this->execute($sql);
        }
        else {
            //查询操作
            return $this->query($sql);
        }
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function startTrans() {
        $this->connect(true);
        if ( !$this->linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            $this->linkID->query('START TRANSACTION');
        }
        $this->transTimes++;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 用于非自动提交状态下面的查询提交
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = $this->linkID->query('COMMIT');
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->linkID->query('ROLLBACK');
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 获得所有的查询数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getAll() {
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            while($row = $this->queryID->fetch_assoc()){
                $result[]   =   $row;
            }
            // mysql_data_seek($this->queryID,0);
            $this->queryID->data_seek(0);
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 取得数据表的字段信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function getFields($tableName) {
        $result =   $this->query('SHOW COLUMNS FROM '.$tableName);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$val['Field']] = array(
                'name'    => $val['Field'],
                'type'    => $val['Type'],
                'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                'default' => $val['Default'],
                'primary' => (strtolower($val['Key']) == 'pri'),
                'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
            );
        }
        return $info;
    }

    /**
     +----------------------------------------------------------
     * 取得数据库的表信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function getTables($dbName='') {
        if(!empty($dbName)) {
           $sql    = 'SHOW TABLES FROM '.$dbName;
        }else{
           $sql    = 'SHOW TABLES ';
        }
        $result =   $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function close() {
        if (!empty($this->queryID)){
            $this->free();
        }
        if ($this->linkID && !$this->linkID->close()){
            throw_exception($this->error());
        }
        $this->linkID = 0;
    }

    /**
     +----------------------------------------------------------
     * 数据库错误信息
     * 并显示当前的SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function error() {
        $this->error = $this->linkID->error();
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * SQL指令安全过滤
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  SQL字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function escape_string($str) {
        return $this->linkID->escape_string($str);
    }

   /**
     +----------------------------------------------------------
     * 析构方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
	
	
    /**
     +----------------------------------------------------------
     * 数据库调试 记录当前SQL
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     */
    protected function debug() {
        // 记录操作结束时间
        if ( $this->debug )    {
            $runtime    =   number_format(microtime(TRUE) - $this->beginTime, 6);
            Log::record(" RunTime:".$runtime."s SQL = ".$this->queryStr,Log::SQL);
        }
    }

    /**
     +----------------------------------------------------------
     * 设置锁机制
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseLock($lock=false) {
        if(!$lock) return '';
        if('ORACLE' == $this->dbType) {
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }

    /**
     +----------------------------------------------------------
     * set分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param array $data
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseSet($data) {
        foreach ($data as $key=>$val){
			if($this->debugData)
				echo __LINE__.": parseSet() : ".$key."=>".$val."<p>";
            $value   =  $this->parseValue($val);
            if(is_scalar($value)) // 过滤非标量数据
                $set[]    = $this->addSpecialChar($key).'='.$value;
			
        }
        return ' SET '.implode(',',$set);
    }

    /**
     +----------------------------------------------------------
     * value分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $value
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseValue($value) {
        if(is_string($value)) {
            $value = '\''.$this->escape_string($value).'\'';
        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value   =  $this->escape_string($value[1]);
        }elseif(is_null($value)){
            $value   =  'null';
        }
        return $value;
    }



    public function submitTable($tbName){
        $sql = "select ".trim($this->_field)." from ".$tbName." ".trim($this->_join)." ".trim($this->_where)." ".trim($this->_having)." ".trim($this->_order)." ".trim($this->_limit);
        $this->_clear = 1;
        $this->_clear();
        return $this->doSQL($sql);
    }

    /**
     * @param mixed $option 组合条件的二维数组，例：$option['field1'] = array(1,'=>','or')
     * @return $this
     */
    public function where($option) {
        if ($this->_clear>0) $this->_clear();
        $this->_where = ' where ';
        $logic = 'and';
        if (is_string($option)) {
            $this->_where .= $option;
        }
        elseif (is_array($option)) {
            foreach($option as $k=>$v) {
                if (is_array($v)) {
                    $relative = isset($v[1]) ? $v[1] : '=';
                    $logic    = isset($v[2]) ? $v[2] : 'and';
                    $condition = ' ('.$this->_addChar($k).' '.$relative.' '.$v[0].') ';
                }
                else {
                    $logic = 'and';
                    $condition = ' ('.$this->_addChar($k).'='.$v.') ';
                }
                $this->_where .= isset($mark) ? $logic.$condition : $condition;
                $mark = 1;
            }
        }
        return $this;
    }
  
    /**
     * 设置排序
     * @param mixed $option 排序条件数组 例:array('sort'=>'desc')
     * @return $this
     */
    public function order($option) {
        if ($this->_clear>0) $this->_clear();
        if (is_string($option)) {
            $this->_order .= ' order by ' . $option;
        }
        elseif (is_array($option)) {
            foreach($option as $k=>$v){
                // $order = $this->_addChar($k).' '.$v;
                // $this->_order .= isset($mark) ? ','.$order : $order;
                // $mark = 1;
            }
        }
        return $this;
    }
  
    /**
     * 设置查询行数及页数
     * @param int $page pageSize不为空时为页数，否则为行数
     * @param int $pageSize 为空则函数设定取出行数，不为空则设定取出行数及页数
     * @return $this
     */
    public function limit($page,$pageSize=null) {
        if ($this->_clear>0) $this->_clear();
        if ($pageSize===null) {
            $this->_limit = "limit ".$page;
        }
        else {
            $pageval = intval( ($page - 1) * $pageSize);
            $this->_limit = "limit ".$pageval.",".$pageSize;
        }
        return $this;
    }
  
    /**
     * 设置查询字段
     * @param mixed $field 字段数组
     * @return $this
     */
    public function field($field){
        if ($this->_clear>0) $this->_clear();
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        // $nField = array_map(array($this,'_addChar'), $field);
        $this->_field = implode(',', $field);
        return $this;
    }

    public function join($options){
        if ($this->_clear>0) $this->_clear();
        // $this->_join = ' join ';
        if (is_string($option)) {
            $this->_join .= $option;
        }
        elseif (is_array($option)) {
            foreach($option as $k=>$v){
                $this->_join .=  ' join ' . $k . ' on ' . $v;
            }
        }
        return $this;
    }

    public function group($options){
        if ($this->_clear>0) $this->_clear();
        $this->_group = empty($options) ? '' : " group by ";
        return $this;
    }
    
    public function having($options){
        if ($this->_clear>0) $this->_clear();
        $this->_having = empty($options) ? '' : " having ";
        return $this;
    }

    public function distinct($options){
        if ($this->_clear>0) $this->_clear();
        $this->_distanct = empty($options) ? '' : " distinct ";
        return $this;
    }

    /**
     * 清理标记函数
     */
    protected function _clear() {
        $_table = '';
        $_where = '';
        $_limit = '';
        $_field = '*';
        $_having = '';
        $_group = '';
        $_order = '';
        $_distanct = '';
        $_clear = 0;
    }


    /**
     +----------------------------------------------------------
     * field分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $fields
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseField($fields) {
        if(is_array($fields)) {
            // 完善数组方式传字段名的支持
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array   =  array();
            foreach ($fields as $key=>$field){
                if(!is_numeric($key))
                    $array[] =  $this->addSpecialChar($key).' AS '.$this->addSpecialChar($field);
                else
                    $array[] =  $this->addSpecialChar($field);
            }
            $fieldsStr = implode(',', $array);
        }elseif(is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->addSpecialChar($fields);
        }else{
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }


    /**
     +----------------------------------------------------------
     * table分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $table
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseTable($tables) {
        if(is_string($tables))
            $tables  =  explode(',',$tables);
        array_walk($tables, array(&$this, 'addSpecialChar'));
        return implode(',',$tables);
    }

    /**
     +----------------------------------------------------------
     * where分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $where
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseWhere($where) {
        $whereStr = '';
        if(is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        }else{ // 使用数组条件表达式
            if(array_key_exists('_logic',$where)) {
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate    =   ' '.strtoupper($where['_logic']).' ';
                unset($where['_logic']);
            }else{
                // 默认进行 AND 运算
                $operate    =   ' AND ';
            }
            foreach ($where as $key=>$val){
                $whereStr .= "( ";
                if(0===strpos($key,'_')) {
                    // 解析特殊条件表达式
                    $whereStr   .= $this->parseThinkWhere($key,$val);
                }else{
                    $key = $this->addSpecialChar($key);
                    if(is_array($val)) {
                        if(is_string($val[0])) {
                            if(preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i',$val[0])) { // 比较运算
                                $whereStr .= $key.' '.$this->comparison[strtolower($val[0])].' '.$this->parseValue($val[1]);
                            }elseif('exp'==strtolower($val[0])){ // 使用表达式
                                $whereStr .= ' ('.$key.' '.$val[1].') ';
                            }elseif(preg_match('/IN/i',$val[0])){ // IN 运算
                                $zone   =   is_array($val[1])? implode(',',$this->parseValue($val[1])):$val[1];
                                $whereStr .= $key.' '.strtoupper($val[0]).' ('.$zone.')';
                            }elseif(preg_match('/BETWEEN/i',$val[0])){ // BETWEEN运算
                                $data = is_string($val[1])? explode(',',$val[1]):$val[1];
                                $whereStr .=  ' ('.$key.' BETWEEN '.$data[0].' AND '.$data[1].' )';
                            }else{
                                throw_exception(L('_EXPRESS_ERROR_').':'.$val[0]);
                            }
                        }else {
                            $count = count($val);
                            if(in_array(strtoupper(trim($val[$count-1])),array('AND','OR','XOR'))) {
                                $rule = strtoupper(trim($val[$count-1]));
                                $count   =  $count -1;
                            }else{
                                $rule = 'AND';
                            }
                            for($i=0;$i<$count;$i++) {
                                $data = is_array($val[$i])?$val[$i][1]:$val[$i];
                                if('exp'==strtolower($val[$i][0])) {
                                    $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                                }else{
                                    $op = is_array($val[$i])?$this->comparison[strtolower($val[$i][0])]:'=';
                                    $whereStr .= '('.$key.' '.$op.' '.$this->parseValue($data).') '.$rule.' ';
                                }
                            }
                            $whereStr = substr($whereStr,0,-4);
                        }
                    }else {
                        //对字符串类型字段采用模糊匹配
                        if(C('LIKE_MATCH_FIELDS') && preg_match('/('.C('LIKE_MATCH_FIELDS').')/i',$key)) {
                            $val = '%'.$val.'%';
                            $whereStr .= $key." LIKE ".$this->parseValue($val);
                        }else {
                            $whereStr .= $key." = ".$this->parseValue($val);
                        }
                    }
                }
                $whereStr .= ' )'.$operate;
            }
            $whereStr = substr($whereStr,0,-strlen($operate));
        }
        return empty($whereStr)?'':' WHERE '.$whereStr;
    }

    /**
     +----------------------------------------------------------
     * 特殊条件分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $key
     * @param mixed $val
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseThinkWhere($key,$val) {
        $whereStr   = '';
        switch($key) {
            case '_string':
                // 字符串模式查询条件
                $whereStr = $val;
                break;
            case '_complex':
                // 复合查询条件
                $whereStr   = substr($this->parseWhere($val),6);
                break;
            case '_query':
                // 字符串模式查询条件
                parse_str($val,$where);
                if(array_key_exists('_logic',$where)) {
                    $op   =  ' '.strtoupper($where['_logic']).' ';
                    unset($where['_logic']);
                }else{
                    $op   =  ' AND ';
                }
                $array   =  array();
                foreach ($where as $field=>$data)
                    $array[] = $this->addSpecialChar($field).' = '.$this->parseValue($data);
                $whereStr   = implode($op,$array);
                break;
        }
        return $whereStr;
    }

    /**
     +----------------------------------------------------------
     * limit分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $lmit
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseLimit($limit) {
        return !empty($limit)?   ' LIMIT '.$limit.' ':'';
    }

    /**
     +----------------------------------------------------------
     * join分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $join
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseJoin($join) {
        $joinStr = '';
        if(!empty($join)) {
            if(is_array($join)) {
                foreach ($join as $key=>$_join){
                    if(false !== stripos($_join,'JOIN'))
                        $joinStr .= ' '.$_join;
                    else
                        $joinStr .= ' LEFT JOIN ' .$_join;
                }
            }else{
                $joinStr .= ' LEFT JOIN ' .$join;
            }
        }
        return $joinStr;
    }

    /**
     +----------------------------------------------------------
     * order分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $order
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseOrder($order) {
        return !empty($order)?  ' ORDER BY '.$order:'';
    }

    /**
     +----------------------------------------------------------
     * group分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $group
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseGroup($group)
    {
        return !empty($group)? ' GROUP BY '.$group:'';
    }

    /**
     +----------------------------------------------------------
     * having分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $having
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseHaving($having)
    {
        return  !empty($having)?   ' HAVING '.$having:'';
    }

    /**
     +----------------------------------------------------------
     * distinct分析
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $distinct
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseDistinct($distinct) {
        return !empty($distinct)?   ' DISTINCT ' :'';
    }

    /**
     +----------------------------------------------------------
     * 插入记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param array $options 参数表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function insert($data,$options=array()) {
        foreach ($data as $key=>$val){
            $value   =  $this->parseValue($val);
            if(is_scalar($value)) { // 过滤非标量数据
                $values[]   =  $value;
                $fields[]     =  $this->addSpecialChar($key);
            }
        }
        $sql   =  'INSERT INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        $sql   .= $this->parseLock(isset($options['lock'])?$options['lock']:false);
        return $this->execute($sql);
    }
	/**
     +----------------------------------------------------------
     * 插入记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param array $options 参数表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function insertData($data,$options=array()) {
        foreach ($data as $key=>$val){
            $values[] = $this->parseValue($val);
			$fields[] = $key;
        }
        $sql   =  'INSERT INTO '.$options['table'].' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
		
        $sql   .= $this->parseLock(isset($options['lock'])?$options['lock']:false);
		echo $sql."<p>";
        return $this->executeData($sql);
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function executeData($str='') {
        $this->connect();
        if ( !$this->linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        //释放前次的查询结果
        if ( $this->queryID ) {    $this->free();    }
        //$this->W(1);
        $result = $this->linkID->query($this->queryStr);
		if($this->debugData){
			echo __LINE__.": sqlstr:".$this->queryStr."<p>";
			if($result){
				echo "insert success!<p>";
			}else{
				echo "insert failed!<p>";
			}
		}
        $this->debug();
        if ( false === $result) {
            if ( $this->debug )
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = $this->linkID->affected_rows;
            if($this->linkID->insert_id){
                $this->lastInsID = $this->linkID->insert_id;
                echo "lastInsID  " . $this->lastInsID;
            }
            
            return $this->numRows;
        }
    }

    /**
     +----------------------------------------------------------
     * 更新记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function update($data,$options) {

        echo "updata_" . $this->getLastInsID() . '<br/>';
		
        $sql   = 'UPDATE '
            .$this->parseTable($options['table'])
            .$this->parseSet($data)
            .$this->parseWhere(isset($options['where'])?$options['where']:'')
            .$this->parseOrder(isset($options['order'])?$options['order']:'')
            .$this->parseLimit(isset($options['limit'])?$options['limit']:'')
            .$this->parseLock(isset($options['lock'])?$options['lock']:false);
		if($this->debugData) echo __LINE__.": update() : ".$sql."<p>";
        return $this->execute($sql);
    }

    /**
     +----------------------------------------------------------
     * 删除记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function delete($options=array())
    {
        $sql   = 'DELETE FROM '
            .$this->parseTable($options['table'])
            .$this->parseWhere(isset($options['where'])?$options['where']:'')
            .$this->parseOrder(isset($options['order'])?$options['order']:'')
            .$this->parseLimit(isset($options['limit'])?$options['limit']:'')
            .$this->parseLock(isset($options['lock'])?$options['lock']:false);
			
		if($this->debugData) echo __LINE__." delete() : ".$sql."<p>";
        return $this->execute($sql);
    }

    /**
     +----------------------------------------------------------
     * 查找记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $options 表达式
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function select($options=array()) {
        if(isset($options['page'])) {
            // 根据页数计算limit
            list($page,$listRows) =  explode(',',$options['page']);
            $listRows = $listRows?$listRows:($options['limit']?$options['limit']:20);
            $offset  =  $listRows*((int)$page-1);
            $options['limit'] =  $offset.','.$listRows;
        }
        $sql   = str_replace(
            array('%TABLE%','%DISTINCT%','%FIELDS%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct'])?$options['distinct']:false),
                $this->parseField(isset($options['field'])?$options['field']:'*'),
                $this->parseJoin(isset($options['join'])?$options['join']:''),
                $this->parseWhere(isset($options['where'])?$options['where']:''),
                $this->parseGroup(isset($options['group'])?$options['group']:''),
                $this->parseHaving(isset($options['having'])?$options['having']:''),
                $this->parseOrder(isset($options['order'])?$options['order']:''),
                $this->parseLimit(isset($options['limit'])?$options['limit']:'')
            ),$this->selectSql);
        $sql   .= $this->parseLock(isset($options['lock'])?$options['lock']:false);
		if($this->debugData) echo __LINE__.": select() : ".$sql."<p>";
        return $this->query($sql);
    }

    /**
     +----------------------------------------------------------
     * 字段和表名添加`
     * 保证指令中使用关键字不出错 针对mysql
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $value
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    protected function addSpecialChar(&$value) {
        $value   =  trim($value);
        if( false !== strpos($value,' ') || false !== strpos($value,',') || false !== strpos($value,'*') ||  false !== strpos($value,'(') || false !== strpos($value,'.') || false !== strpos($value,'`')) {
            //如果包含* 或者 使用了sql方法 则不作处理
        }else{
            $value = '`'.$value.'`';
        }
        return $value;
    }

    /**
     +----------------------------------------------------------
     * 查询次数更新或者查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $times
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function Q($times='') {
        static $_times = 0;
        if(empty($times)) {
            return $_times;
        }else{
            $_times++;
            // 记录开始执行时间
            $this->beginTime = microtime(TRUE);
        }
    }

    /**
     +----------------------------------------------------------
     * 写入次数更新或者查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $times
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function W($times='') {
        static $_times = 0;
        if(empty($times)) {
            return $_times;
        }else{
            $_times++;
            // 记录开始执行时间
            $this->beginTime = microtime(TRUE);
        }
    }

    /**
     +----------------------------------------------------------
     * 获取最近一次查询的sql语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getLastSql() {
        return $this->queryStr;
    }


    /**
     +----------------------------------------------------------
     * 取得数据库类实例
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return mixed 返回数据库驱动类
     +----------------------------------------------------------
     */
    public static function getInstance($db_config='')
    {
		if ( self::$_instance==null ){
			self::$_instance = new DB($db_config);

            // echo "highlight_string(str)";
		}
		return self::$_instance;
    }
	

}


?>