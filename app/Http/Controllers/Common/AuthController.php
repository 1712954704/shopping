<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Http\Service\Common\AuthService;
use library\Constants\StatusConstants;

class AuthController extends BaseController
{

    public function __construct()
    {
        $this->is_login = 0;
        parent::__construct();
    }


    /**
     * 路由相关
     *
     */
    public function rule_operate()
    {
        $auth_service = new AuthService();
        switch ($this->method) {
            case 'GET':  // 获取路由配置
                // 检测参数
                $params['pid']                  = $this->data_arr['pid'] ?? 0;
                $params['is_all']               = $this->data_arr['is_all'] ?? 0;
                $params['system_type']          = $this->check_param('system_type');
                $params['key_word']             = $this->data_arr['key_word'] ?? null;     // 关键字
                $data = $auth_service->get_auth_rule($params);
                break;
            case 'POST': // 添加路由配置
                // 检测参数
                $params['name']                 = $this->check_param('name');
                $params['type']                 = $this->check_param('type');  // 路由类型 1=无子集和按钮权限的菜单栏 2=有子集的菜单栏 3=有按钮权限的菜单栏 4=按钮
                $params['title']                = $this->data_arr['title'] ?? '';
                $params['remark']               = $this->data_arr['remark'] ?? '';
                $params['pid']                  = $this->data_arr['pid'] ?? 0;
                $params['method']               = $this->data_arr['method'] ?? '';
                $params['code']                 = $this->data_arr['code'] ?? '';
                $params['order']                = $this->check_param_empty('order',2);
                $params['icon']                 = $this->data_arr['icon'] ?? '';
                $data = $auth_service->add_auth_rule($params);
                break;
            case 'PUT':  // 更新路由配置
                // 检测参数
                $id                             = $this->check_param('id'); // 主键id
                $params['name']                 = $this->data_arr['name'] ?? '';
                $params['type']                 = $this->data_arr['type'] ?? '';  // 路由类型 1=无子集和按钮权限的菜单栏 2=有子集的菜单栏 3=有按钮权限的菜单栏 4=按钮
                $params['title']                = $this->data_arr['title'] ?? '';
                $params['remark']               = $this->data_arr['remark'] ?? '';
                $params['pid']                  = $this->data_arr['pid'] ?? 0;
                $params['method']               = $this->data_arr['method'] ?? '';
                $params['code']                 = $this->data_arr['code'] ?? '';
                $params['order']                = $this->data_arr['order'] ?? '';
                $params['icon']                 = $this->data_arr['icon'] ?? '';
                $data = $auth_service->update_auth_rule($id,$params);
                break;
            case 'DELETE':  // 更新路由配置
                // 检测参数
                $id                             = $this->check_param('id'); // 主键id
                $data = $auth_service->del_auth_rule($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }



    /**
     * 获取路由详情(获取整条路径信息)
     *
     */
    public function get_route_way_info()
    {
        $auth_service = new AuthService();
        switch ($this->method) {
            case 'GET':  // 获取路由配置
                // 检测参数
                $id = $this->check_param('id'); // id
                $ids = explode (',',$id);
                $ids = array_unique($ids);
                $data = $auth_service->get_route_way_info($ids);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result(StatusConstants::SUCCESS,'',$data);
    }

    /**
     * 路由相关
     *
     */
    public function get_system_info()
    {
        switch ($this->method) {
            case 'GET':  // 获取路由配置
                // 检测参数
                $data = [
                    [
                        'name' => 'ehr',
                        'system_type' => 1,
                    ],
                ];
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result(StatusConstants::SUCCESS,'',$data);
    }


}
