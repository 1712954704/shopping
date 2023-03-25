<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */

namespace App\Http\Controllers;

use App\Http\Service\Common\UserService;
use Illuminate\Support\Facades\Request;
use library\Constants\StatusConstants;
use library\Traits\Log;
use library\Traits\Http;

class BaseController
{
    protected $is_login = 1;  // 是否需要登录 1:是 0:否

    protected $is_default_route = 0;  // 是否默认赋予用户权限的路由 1:是 0:否

    protected $system_type = 1; // 请求系统类型  1=hr

    protected $my_config;  // 配置

    protected $method;     // 请求方式

    protected $route_at;  // 当前路由

    protected $data_arr;  // 参数信息

    protected $token;  // 用户token

    protected $user_info;  // 用户信息

    protected $model;  // 获取model路径

    /**
     * 初始化
     * @param string $token token
     * @return mixed
     */
    public function __construct($token = '')
    {
        $this->my_config = \Common::get_config();

        // 设置默认时区
        date_default_timezone_set($this->my_config['default_time_zone'] ?? 'PRC');

        //调试日志
//        if ($this->my_config['flag']['write_debug']){
//            $this->write_request_info();
//        }

        if (\Common::is_cli()) {  // 是否是cli模式
            goto END;
        }

        //跨域
//        $this->cross_domain();

        //服务器时间 毫秒
        header('X-Server-Time: ' . \Common::get_micro_time());

        //强制不缓存
        header("Cache-Control: no-cache");

        //系统类型
        if (isset($_SERVER['HTTP_SYSTEM_TYPE']) && $_SERVER['HTTP_SYSTEM_TYPE']) {
            $this->system_type = $_SERVER['HTTP_SYSTEM_TYPE'];
        }

        // 系统类型不传则返回错误
        if (!isset($this->my_config['system_type'][$this->system_type])) {
            \Common::response_error_header(400, StatusConstants::ERROR_TO_MSG_COPY[StatusConstants::ERROR_INVALID_REQUEST_METHOD]);
        }

        define('NOW_SYSTEM_TYPE', $this->my_config['system_type'][$this->system_type]); // 定义当前系统类型

        // 请求类型设置
        $this->method = $_SERVER['REQUEST_METHOD'] ?? '';

        //获取全部参数
//        $this->data_arr = \Common::getBodyParams();
        if ($this->method == 'GET') {
            $this->data_arr = $_GET;
        } else {
            if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
                $this->data_arr = $_POST;
            } else {
                $data = file_get_contents('php://input');
                if ($data) {
                    $this->data_arr = json_decode($data, true);
                }
            }
            // 容错处理，解决异常参数解码失败
            if (!is_array($this->data_arr)) {
                $this->data_arr = [];
            }
        }
        // 获取当前路由  例:api/home
        $this->route_at = strtolower(Request::path());

        // 是否需要默认赋予用户权限的路由
        if (in_array($this->route_at, $this->my_config['default_auth_routes'])) {
            $this->is_default_route = 1;
        }

        // 权限验证
        if ($this->is_login && !in_array($this->route_at, $this->my_config['no_login'])) {
            $this->check_auth($token);
        }
        $this->model = \common::getModelPath();
        END:
    }

    /**
     * 跨域设置
     */
    private function cross_domain()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Authorization');
        header('Access-Control-Allow-Methods: GET,POST,OPTIONS,PUT,DELETE');
    }

    /**
     * 权限验证
     */
    public function check_auth($token = '')
    {
        if (!$token) {
            if ((isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION']) || (isset($_SERVER['HTTP_AUTHORIZATION_2']) && $_SERVER['HTTP_AUTHORIZATION_2'])) {
                $HTTP_AUTHORIZATION = !empty($_SERVER['HTTP_AUTHORIZATION_2']) ? $_SERVER['HTTP_AUTHORIZATION_2'] : $_SERVER['HTTP_AUTHORIZATION'];
                $a                  = explode(" ", $HTTP_AUTHORIZATION);
                if (isset($a[1]) && $a[1]) {
                    $token = $a[1];
                } else {
                    \Common::response_error_header(401, 'invalid token');
                }
            } else {
                \Common::response_error_header(401, 'invalid token 2');
            }
        }
        $this->token  = $token;
        $user_service = new UserService();
        $check_result = $user_service->get_user_info_by_token($token);
        if ($check_result['code'] != 200) { // token错误
            \Common::response_error_header(500, StatusConstants::ERROR_TO_MSG_COPY[StatusConstants::ERROR_UNAUTHORIZED_TOKEN]);
        }

        // 获取用户信息缓存
        $user_result = $user_service->get_user_info_by_id($check_result['data']['id']);
        if ($user_result['code'] == 200) {
            $this->user_info = $user_result['data'];
        }
        // 刷新用户token生存时间
        if ($check_result) {
            // 每个小时刷新一次过期时间, 避免频繁刷新
            if (!empty($selected_token['refresh_token_time'])) {
                $user_service->refresh_token_expire($token, $selected_token['refresh_token_time']);
            }
        }

        if ($this->is_default_route){
            return true;
        }

        // 获取用户当前系统的权限信息并验证
        $auth_result = $user_service->get_user_auth_info_by_id($check_result['data']['id'], [$this->my_config['system_type'][$this->system_type]])[$this->my_config['system_type'][$this->system_type]];
        // 是否拥有超级管理员权限 todo 获取不到权限需要添加容错
        if (!$auth_result || ($auth_result == null) || !in_array('*', $auth_result)) {
            // 验证路由及请求方式
            if (!isset($auth_result[$this->route_at]) || !in_array($this->method, $auth_result[$this->route_at])) {
                \Common::response_error_header(500, StatusConstants::ERROR_TO_MSG_COPY[StatusConstants::ERROR_UPGRADE_AUTH_LEVEL]);
            }
        }

    }

    /**
     * @param $key
     * @param null $default 默认值
     * @param bool $check_empty 是否验证为空字符串
     * @param array $check_val_range 允许的值
     * @param int $len
     *
     * @return mixed|null
     */
    protected function check_param($key, $default = null, $check_empty = true, array $check_val_range = [], int $len = 0)
    {
        $val = $this->data_arr[$key] ?? $default;
        if ($val === null || ($check_empty && $val === '') || ($check_val_range && !in_array($val, $check_val_range)) || ($len > 0 && mb_strlen($val) > $len)) {
            \Common::response_error_header(400, 'invalid param ' . $key);
        }
        return $val;
    }


    /**
     * @param $key
     * @param int $return_type 为空时的返回类型 0=null 1='' 2=0
     * @return mixed|null
     */
    protected function check_param_empty($key,$return_type = 0)
    {
        // 判断是否设置并且是否为空
        if (isset($this->data_arr[$key]) && empty($this->data_arr[$key])){
            switch ($return_type){
                case 1:
                    return '';
                case 2:
                    return 0;
                default:
                    return null;
            }
        }else{
            return $this->data_arr[$key] ?? null;
        }
    }


    /**
     * 获取安全正整数型参数
     *
     * @param string $param
     * @param int $default
     * @param int|null $max
     *
     * @return int
     */
    protected function get_safe_int_param($param, int $default = 0, int $max = null)
    {
        $safeValue = intval($this->data_arr[$param] ?? $default);
        if ($safeValue < 0) {
            $safeValue = $default;
        }
        if ($max !== null && $safeValue > $max) {
            $safeValue = $max;
        }

        return $safeValue;
    }

    /**
     * 获取整型参数
     *
     * @param $param
     * @param int $default
     * @param int|null $max
     *
     * @return int
     */
    protected function get_int_param($param, int $default = 0, int $max = null)
    {
        $safeValue = intval($param ?? $default);
        if ($safeValue < 0) {
            $safeValue = $default;
        }
        if ($max !== null && $safeValue > $max) {
            $safeValue = $max;
        }
        return $safeValue;
    }

    /**
     * 获取安全正整数型参数 并验证是否为empty
     *
     * @param $param
     * @param int $default
     * @param int|null $max
     *
     * @return int
     */
    protected function get_safe_int_param_validate($param, int $default = 0, int $max = null)
    {
        $safeValue = $this->get_safe_int_param($param, $default, $max);
        if (!$safeValue) {
            \Common::response_error_header(400, 'invalid param ' . $param);
        }
        return $safeValue;
    }

    /**
     * 获取安全正整数型参数 并验证是否在限制范围内
     * @param $key
     * @param int $default
     * @param int $min
     * @param int $max
     * @return int
     */
    protected function check_safe_int_param($key, int $default = 0, int $min = 0, int $max = 0)
    {
        if (!isset($this->data_arr[$key])) {
            \Common::response_error_header(400, 'invalid param ' . $key);
        }
        $safeValue = !empty($default) ? intval($this->data_arr[$key] ?? $default) : intval($this->data_arr[$key]);
        if ($safeValue < $min || $safeValue > $max) {
            \Common::response_error_header(400, 'invalid param ' . $key);
        }
        return $safeValue;
    }

    /**
     * 发送响应结果
     *
     * @param array $result \Common::format_return_result的返回值
     * @param int $success_http_code 在status = SUCCESS时响应给客户端的状态码
     */
    protected function send_result(array $result, $success_http_code = null)
    {
        //如果不传msg则从常量配置中获取（如果存在的话）
        if ($result['msg'] === '') {
            $result['msg'] = StatusConstants::ERROR_TO_MSG_COPY[$result['status']] ?? '';
        }

        if ($result['status'] == StatusConstants::SUCCESS) {
            if ($success_http_code === null) {
                $success_http_code = self::HANDLED_SUCCESS_HTTP_METHOD_CODE_MAPS[$this->method] ?? 200;
            }
            $result['code'] = $success_http_code;
            \Common::response_success_header($result['code'], $result['msg'], $result['data']);
        }
        \Common::response_error_header($result['code'], $result['msg'], $result['data']);
    }

    /**
     * 发送非成功相应结果(只能运用在Controller层)
     *
     * @date 2021/6/9
     * @param int $status StatusConstants 的错误码
     * @param string $msg 报错信息不传则取默认配置
     * @param array $data 返回数据
     * @see StatusConstants
     */
    protected function send_result_error($status, $msg = '', $data = [])
    {
        // Status转Code
        $code = StatusConstants::STATUS_TO_CODE_MAPS[$status] ?? StatusConstants::ERROR_SERVICE_EXCEPTION;

        //如果不传msg则从常量配置中获取（如果存在的话）
        if ($msg === '') {
            $msg = StatusConstants::ERROR_TO_MSG_COPY[$status] ?? '';
        }

        \Common::response_error_header($code, $msg, $data);
    }

    /**
     * log记录
     */
    public function __destruct()
    {
        //记录请求日志
        if (isset($this->my_config['write_debug']) && $this->my_config['write_debug']){
            Log::NewInfo([
                '_SERVER'=>[
                    'HTTP_CONTENT_TYPE'=>$_SERVER['HTTP_CONTENT_TYPE'] ?? null,
                    'REQUEST_URI'=>$_SERVER['REQUEST_URI'],
                    'REQUEST_METHOD'=>$_SERVER['REQUEST_METHOD'],
                    'HTTP_AUTHORIZATION'=>$_SERVER['HTTP_AUTHORIZATION'] ?? null,
                    'CONTENT_TYPE'=>$_SERVER['CONTENT_TYPE'],
                    'REQUEST_TIME'=>$_SERVER['REQUEST_TIME']
                ],
                'Header'=>[
                    'Content-Type'=>Http::getContentType(),
                    'method'=>$this->method,
                    'route'=>$this->route_at,
                    'system_type'=>$this->system_type,
                ],
                'user_info'=>$this->user_info,
                'original_data'=>Http::getBodyParams(true),
                'data'=>$this->data_arr,
            ], 'request log 请求数据');
        }
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

}
