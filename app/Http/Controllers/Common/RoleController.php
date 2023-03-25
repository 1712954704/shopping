<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Http\Service\Common\RoleService;
use library\Constants\StatusConstants;

class RoleController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 角色权限相关
     *
     */
    public function role_operate()
    {
        $role_service = new RoleService();
        switch ($this->method) {
            case 'GET':  // 获取角色列表
                // 检测参数
                $page                   = $this->get_safe_int_param('page',1);
                $limit                  = $this->get_safe_int_param('limit',10);
                $offset                 = ($page - 1) * $limit;
                $params['name']         = $this->data_arr['name'] ?? '';   // 名称
                $params['department_id']= $this->data_arr['department_id'] ?? '';   // 部门id
                $params['key_word']    = $this->data_arr['key_word'] ?? null;     // 关键字
                $data = $role_service->get_role($params,$limit,$offset);
                break;
            case 'POST':  // 添加角色
                // 检测参数
                $params['name']             = $this->check_param('name'); // 名称
                $params['type']             = $this->check_param('type',1);  // 1=全局 2=组织
                $params['pid']              = $this->check_param('pid',0);  // 父级id
                $params['code']             = $this->check_param('code',0);  // 编码
                $params['department_id']    = $this->check_param_empty('department_id',2);   // 部门id
                $params['order']            = $this->check_param('order',0);  // 排序
                $params['status']           = $this->check_param('status',0);  // 状态 1=正常 2=停用
                $params['remark']           = $this->data_arr['remark'] ?? '';   // 备注
                if ($params['type'] == 2 && empty($params['department_id'])){
                    \Common::response_error_header(400, 'invalid param department_id' );
                }
                $data = $role_service->add_role($params);
                break;
            case 'PUT':  // 更新角色
                // 检测参数
                $id                      = $this->check_param('id');
                $params['name']           = $this->check_param('name'); // 名称
                $params['type']        = $this->check_param('type',1);  // 1=全局 2=组织
                $params['pid']            = $this->check_param('pid',0);  // 父级id
                $params['code']            = $this->check_param('code',0);  // 编码
                $params['department_id']         = $this->data_arr['department_id'] ?? '';   // 部门id
                $params['order']            = $this->check_param('order',0);  // 排序
                $params['status']            = $this->check_param('status',0);  // 状态 1=正常 2=停用
                $params['remark']         = $this->data_arr['remark'] ?? '';   // 备注
                if ($params['type'] == 2 && empty($params['department_id'])){
                    \Common::response_error_header(400, 'invalid param department_id' );
                }
                $data = $role_service->update_role($id,$params);
                break;
            case 'DELETE':  // 删除角色
                // 检测参数
                $id                      = $this->check_param('id');
                $data = $role_service->del_role($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 用户角色相关
     *
     */
    public function change_user_role()
    {
        $role_service = new RoleService();
        switch ($this->method) {
            case 'POST':  // 添加用户角色关联
                // 检测参数
                $role_id           = $this->check_param('role_id');  // 角色id
                $user_ids           = $this->check_param('user_ids');  // 角色数组
                $data = $role_service->add_user_role($role_id,$user_ids);
                break;
//            case 'PUT':  // 更新角色
//                // 检测参数
//                $user_id        = $this->data_arr['user_id'] ?? $this->user_info['id']; // 可不传默认为当前请求用户
//                $role           = $this->check_param('role');  // 角色数组
//                $data = $role_service->update_user_role($user_id,$role);
//                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }


    /**
     * 用户角色相关
     *
     */
    public function change_role_auth()
    {
        $role_service = new RoleService();
        switch ($this->method) {
            case 'POST':  // 添加角色权限关联
                // 检测参数
                $role_id           = $this->check_param('role_id');  // 角色id
                $auth_ids           = $this->check_param('auth_ids');  // 角色数组
                $data = $role_service->add_role_auth($role_id,$auth_ids);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

}
