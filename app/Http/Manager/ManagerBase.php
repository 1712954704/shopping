<?php

/**
 * Manager 可复用逻辑层: 通用业务处理层
 * 它有如下特征:
 * 1. 对第三方平台封装的层，预处理返回结果及转化异常信息;
 * 2. 对Service层通用能力的下沉，如缓存方案、中间件通用处理;
 * 3. 与DAO层交互，对多个DAO的组合复用。
 * 4. 缓存都写在Manager层，如用户缓存，就在Manager层加一个CacheUserManager.php
 *    所有用户相关的缓存，就写在这里。其他控制器或Service要调用，要用这个Manager处理。
 *    都封装好key的写入和读取方法
 *
 * 可以是单个服务的，比如我们的cache,mq等等，当然也可以是复合的，当你需要调用多个Service的时候，这个可以合为一个Manager，
 * 比如逻辑上的连表查询等。如果是httpManager或rpcManager需要在这一层做一些数据转换
 *
 * 注意:
 * 1、所有的参数都通过方法传入，不允许通过构造方法共享(微服务的时候由于类只初始化一次，所以类的状态会被后续请求改变)
 * 2、只有Service返回结果要通过\Common::format_return_result()方法构造固定返回结构，Manager可以不需要(要支持远程调用的，就使用\Common::format_return_result())。Manager可以理解成多个service整合逻辑结果。
 * 3、所有的服务不能有状态，以免影响后续请求(微服务常驻内存时，不会重置类状态)
 *
 * eg:
 * Controller、Service、Manager中获取容器调用方法
 */

namespace App\Http\Manager;

use App\Http\Service\ServiceBase;

class ManagerBase extends ServiceBase
{

    /**
     * 请求百度api方法
     *
     * @param string $url
     * @param array $data
     * @param integer $time_out
     * @return array
     */
    public function request_post($url,$data  = array(), $time_out = 10)
    {
        $curl = curl_init(); //初始化curl
        curl_setopt($curl, CURLOPT_URL, $url); //抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0); //设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1); //post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $aHeader[] = 'Content-type: application/json'; //header
        curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($curl, CURLOPT_TIMEOUT, $time_out); //以秒计算的超时时间
        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($result) {
            curl_close($curl);
            return array(
                'code' => $info["http_code"],
                'data' => json_decode($result, true),
                // 'info' => $info
            );
        } else {
            $error_info = curl_error($curl);
            curl_close($curl);
            return array(
                'code' => $info["http_code"],
                'data' => $error_info,
                // 'info' => $info
            );
        }
    }

    /**
     * curl请求
     * @param $url
     * @param string $type
     * @param string $post_data json 数据
     * @param string $content_type
     * @return array
     */
    public function curl_request($url, $type = 'GET', $post_data = '',$content_type='')
    {

        $curl    = curl_init();
        $aHeader = Array();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

        if ($type == 'POST') {

            $content_type = empty($content_type)? "application/json":$content_type;
            $aHeader[] = 'Content-type: '.$content_type ;
            curl_setopt($curl, CURLOPT_POST, 1);

            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }
        if (!empty($aHeader)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeader);
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result    = curl_exec($curl);
        $info      = curl_getinfo($curl);
        $error_no  = curl_errno($curl);
        $error_str = curl_error($curl);

        curl_close($curl);

        $result       = str_replace("\"", '"', $result);
        $result_array = json_decode($result, true);

        if ($info["http_code"] == 200) {
            $data = array(
                "code" => $info["http_code"],
                "data" => $result_array
            );
        } else {
            $data = array(
                "code" => $info["http_code"],
                "data" => Array(
                    'time'      => date('Y-m-d H:i:s', time()),
                    'type'      => $type,
                    'url'       => $url,
                    'post_data' => $post_data,
                    'code'      => $info["http_code"],
                    'body'      => $result_array,
                    'error_no'  => $error_no,
                    'error_str' => $error_str
                )
            );
        }

        return $data;
    }

    //根据ip获取地区
    public function get_region_by_ip($ip){
        $url = 'https://api.ip138.com/ip/?ip='.$ip.'&datatype=jsonp';
        $header = array(\Common::get_config('ip138_token'),'Content-type: application/json');
        return $this->request($url,'GET',[],$header);
    }

    public function request($url,$method,$data ,$headers = array()){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3); //以秒计算的超时时间

        if ($method == "POST"){
            curl_setopt($curl, CURLOPT_POST, 1);
        } elseif ($method == "PUT" || $method == "DELETE"){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }

        //header
        if( !empty($headers) ){
            foreach ($headers as $item){
                $aHeader[] = $item;
            }
        }else{
            $aHeader[] = 'Content-type: application/json';
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $aHeader);

        //data
        if ($data){
            $data = $data && is_array($data) ? json_encode($data) : (string)$data;
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($result){
            return array(
                'code' => $info["http_code"],
                'data' => json_decode($result, true)
            );
        } else{
            return array(
                'code' => $info["http_code"],
                'data' => curl_error($curl)
            );
        }
    }



}
