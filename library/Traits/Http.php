<?php
/**
 * @name HTTP类
 *
 */
namespace library\Traits;

class Http{
	static private $instance=null;

    static protected $_dataBody = null;

	//返回单例
	static public function getInstance(){
		if(!defined('USE_SINGLE') || USE_SINGLE==false){
			return new self();
		}

		if (NULL === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @name JS转向
	 * @param string $url URL地址
	 * @param boolean $parent 父窗口
	 */
	public function jsGotoUrl($url, $parent=false){
		echo '<script>'.($parent?'parent.':'').'location.href="'.$url.'";</script>';
		exit;
	}

	/**
	 * @name goto404 转到404
	 */
	public function goto404(){
		echo 404;exit;
	}

	/**
	 * @name exit 停止运行
	 * @param string $msg 提示信息
	 */
	public function exitRun($msg=''){
		echo $msg;
		exit();
	}

	/**
	 * @name POST参数
	 * @param string $name POST名称
	 * @return mix
	 */
	public function getPost($name=null){

        $result = self::getBodyParams();

//		if($name==null){
//			$result = isset($_POST)?$_POST:array();
//		}
//		else{
//			if(isset($_POST[$name])){
//				$result = $_POST[$name];
//			}
//			else{
//				$result = '';
//			}
//		}
		return isset($result[$name]) ? $result[$name] : '';
	}

	/**
	 * @name GET参数
	 * @param string $name GET名称
	 * @return mix
	 */
	public function getGet($name=null){
//		if($name==null){
//			$result =  isset($_GET)?$_GET:array();
//		}
//		else{
//			if(isset($_GET[$name])){
//				$result = $_GET[$name];
//			}
//			else{
//				$result = '';
//			}
//		}
        $result = self::getBodyParams();
		return isset($result[$name]) ? $result[$name] : '';
	}

	public function getRequest($name){
		$result = $this->getGet($name);
		if(!$result){
			$result = $this->getPost($name);
		}

		return $result;
	}

    /**
     * 获取请求类型
     * @time 2019-06-03 10:55
     * @return |null
     */
    public static function getContentType(){
        if (isset($_SERVER['CONTENT_TYPE'])) {
            return $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {

            return $_SERVER['HTTP_CONTENT_TYPE'];
        }

        return null;
    }

    /**
     * 获取参数
     * 2022/12/31
     * @param $is_original_data
     * @return array|null
     */
    public static function getBodyParams($is_original_data =false)
    {
        //兼容已经拿过值了就直接返回
        if(!empty(self::$_dataBody) && $is_original_data ==false){
            return self::$_dataBody;
        }

        $rawContentType = self::getContentType();
        if (($pos = strpos($rawContentType, ';')) !== false) {
            $contentType = strtolower(substr($rawContentType, 0, $pos));
        } else {
            $contentType = strtolower($rawContentType);
        }
        $_bodyParams = [];
        $_bodyParams = array_merge($_bodyParams,$_GET); //路由是在地址里发过来的

        $method =  isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        if('application/json' === $contentType){
            $_body = json_decode(file_get_contents('php://input'),true) ?: [];
            $_bodyParams = array_merge($_bodyParams,$_body);
        }else if($method === 'POST'){
//            $_body = $_POST ? $_POST : $GLOBALS['HTTP_RAW_POST_DATA'];
            $_body = $_POST ? $_POST : [];
            $_bodyParams = array_merge($_bodyParams,$_body?:[]);
        }else if($method === 'GET'){
            $_bodyParams = array_merge($_bodyParams,$_GET?:[]);
        }else if($method === 'REQUEST'){
            $_bodyParams = array_merge($_bodyParams,$_REQUEST?:[]);
        }else{
            mb_parse_str(file_get_contents('php://input'), $_bodyParams);
        }

        $original_data = $_bodyParams; //原始数据

        if($is_original_data == true){
            return $original_data;
        }

        //过滤特殊字符
        self::_remove_chars($_bodyParams);

        self::$_dataBody = $_bodyParams;

        return $_bodyParams;
    }

    /**
     * 过滤非法字符
     * 2022/11/29
     * @param $data
     * @return void
     */
    public static function _remove_chars(&$data)
    {
        /**
         * \@\. 邮箱
         * \_方法
         * \- 文件id
         * \/\?\&\= 路由地址
         */
        //todo
        $reg = '/[^0-9a-zA-Z\@\.\-\_\/\?\&\=\x{4e00}-\x{9fa5}]/u'; //允许的字符
//        $reg = '/[^\da-zA-Z\@\.\-\_\/\?\&\x{4e00}-\x{9fa5}]/u/'; //允许的字符

        foreach ($data as $k=>&$y){

            if(is_array($y)){
                //数组解析
                self::_remove_chars($y);
            }else {
                switch ($y) {
                    case (strtotime($y) !== false): //日期格式
                        break;
                    default: //其他格式
                        $y = preg_replace($reg, '', $y);
                }
            }
        }
    }

	/**
	 * @name CURL POST
	 * @param string $url
	 * @param string $params
	 * @return mixed
	 */
	public function curlPost($url,$params){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$output = curl_exec($ch);

		curl_close($ch);
		return $output;
	}

}
