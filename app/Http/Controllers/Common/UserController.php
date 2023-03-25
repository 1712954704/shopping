<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */

namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Http\Service\Common\UserService;
use library\Constants\Model\UserConstants;
use library\Constants\StatusConstants;

class UserController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 登录
     */
    public function login()
    {
        // 验证 ip地址
        $user_service = new UserService();
        switch ($this->method) {
            case 'POST': // 添加路由配置
                // 检测参数
                $account = $this->check_param('account');  // 账号
                $pwd     = $this->check_param('pwd');  // 密码
                $data    = $user_service->login($account, $pwd, $this->system_type);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 获取用户信息包含权限个及个人配置等(登录后获取)
     */
    public function user_info()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'GET': // 获取用户信息
                $user_id = $this->data_arr['user_id'] ?? 0;
                if ($user_id){
                    $data['data']['info']   = $user_service->get_user_info_by_id($user_id)['data'] ?? [];
                }else{
                    $data['data']['info']   = $this->user_info;
                    $user_id = $this->user_info['id'];
                }
                $routes                 = $user_service->user_route_info($user_id, $this->system_type);
                $data['code']           = $routes['code'];
                $data['msg']            = $routes['msg'];
                $data['data']['routes'] = $routes['data'];
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 清除用户锁定
     */
    public function clear_user_lock()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'POST': // 添加路由配置
                // 检测参数
                $account = $this->check_param('account');  // 账号
                $data    = $user_service->clear_user_lock($account);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 注册
     */
    public function user_register_or_edit()
    {
        // 检测参数
        $params['account']       = $this->check_param('account');  // 账号
        $params['name']          = $this->check_param('name');  // 姓名
        $params['gender']        = $this->check_param('gender');  // 性别 1=男 2=女
        $params['job_number']    = $this->data_arr['job_number'] ?? '';  // 员工工号
        $params['email']         = $this->data_arr['email'] ?? '';  // 邮箱
        $params['structure_id']  = $this->data_arr['structure_id'] ?? 0;  // 所属组织
        $params['department_id'] = $this->data_arr['department_id'] ?? 0;  // 所属部门
//        $params['manager_id'] = $this->check_param('manager_id');  // 直属主管
        $params['manager_id']     = $this->data_arr['department_id'] ?? 0;  // 直属主管
        $params['position_id']    = $this->data_arr['position_id'] ?? 0;  // 所属岗位
        $params['role_id']        = $this->data_arr['role_id'] ?? [];  // 所属角色 数组形式 可以有多个角色
        $params['job_type']       = $this->data_arr['job_type'] ?? 0;  // 用户类型 1=在职 2=离职
        $params['status']         = $this->data_arr['status'] ?? 0;  // 状态 1=在职(正常) 2=锁定 3=禁用 -1=删除
        $params['phone']          = $this->check_param('phone');  // 手机
        $params['landline_phone'] = $this->data_arr['landline_phone'] ?? '';  // 办公室座机
        $params['avatar']         = $this->check_param_empty('avatar', 1);  // 头像
        $params['nation_id']      = $this->check_param_empty('nation_id', 1);  // 民族
        $params['native_place']   = $this->check_param_empty('native_place', 1);  // 籍贯
        $params['entry_date']     = $this->check_param_empty('entry_date');  // 正式入职时间
        $params['become_data']    = $this->check_param_empty('become_data');  // 转正时间
        $params['id_number']      = $this->check_param_empty('id_number', 1);  // 身份证号
        $params['birth_date']     = $this->check_param_empty('birth_date');  // 出生日期
//        $params['education'] = $this->data_arr['education'] ?? null;  // 学历 1=小学 2=初中 3=高中 4=中专 5=大专 6=本科 7=研究生 8=博士及以上
        $params['education']                  = $this->check_param_empty('education');  // 学历 1=小学 2=初中 3=高中 4=中专 5=大专 6=本科 7=研究生 8=博士及以上
        $params['address']                    = $this->check_param_empty('address', 1);  // 现住址
        $params['emergency_contact_name']     = $this->check_param_empty('emergency_contact_name', 1);  // 紧急联系人姓名
        $params['emergency_contact_relation'] = $this->check_param_empty('emergency_contact_relation', 1);  // 紧急联系人关系
        $params['emergency_contact_phone']    = $this->check_param_empty('emergency_contact_phone', 1);  // 紧急联系人电话
        $params['emergency_contact_address']  = $this->check_param_empty('emergency_contact_address', 1);  // 紧急联系人现住址
        $params['remark']                     = $this->check_param_empty('remark', 1);  // 备注
        $params['user_id']                    = $this->data_arr['user_id'] ?? null;  // 用户id
        $user_service                         = new UserService();
        switch ($this->method) {
            case 'POST': // 新增用户
                $data = $user_service->register_or_edit($params, 1);
                break;
            case 'PUT': // 编辑用户信息
                $data = $user_service->register_or_edit($params, 2);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 删除用户
     */
    public function user_del()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'DELETE': // 添加路由配置
                // 检测参数
                $user_id = $this->check_param('user_id');
                $data    = $user_service->user_del($user_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 获取用户列表
     */
    public function user_list()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'GET': // 获取用户列表
                $page  = $this->get_safe_int_param('page', 1);
                $limit = $this->get_safe_int_param('limit', 10);
                if (isset($this->data_arr['status']) && in_array($this->data_arr['status'], [UserConstants::COMMON_STATUS_NORMAL, UserConstants::COMMON_STATUS_LOCK, UserConstants::COMMON_STATUS_DISABLE])) {
                    $params['status'] = $this->data_arr['status'];
                } else {
                    $params['status'] = [UserConstants::COMMON_STATUS_NORMAL, UserConstants::COMMON_STATUS_LOCK, UserConstants::COMMON_STATUS_DISABLE];
                }
                $id                      = $this->data_arr['id'] ?? null;     // 主键id
                $params['job_type']      = $this->data_arr['job_type'] ?? null;     // 员工类型
                $params['department_id'] = $this->data_arr['department_id'] ?? null;     // 部门id
                $params['key_word']    = $this->data_arr['key_word'] ?? null;     // 关键字
                $offset = ($page - 1) * $limit;
                $data   = $user_service->get_list($params, $id, $offset, $limit);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 重置用户缓存信息(内部测试使用)
     */
    public function user_reset()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'POST': // 添加路由配置
                // 检测参数
                $ids  = $this->data_arr['ids'] ?? [];  // 用户id
                if ($ids){
                    $data = $user_service->user_reset($ids, $this->system_type);
                }else{
                    // 重置全部
                    $data = $user_service->reset_user_all();
                }
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 用户邮箱找回密码
     * @return mixed
     */
    public function reset_password()
    {
        $interface = request('interface');
        $data = request('data');
        // 用户找回密码
        $Service = new UserService();
        $result  = $Service->reset_password($interface,$data);
        return \Common::format_return_result($result['code'], $result['msg'], $result['data']);
    }

    /**
     * 根据旧密码重置用户密码
     */
    public function user_reset_pwd()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'POST': // 添加路由配置
                // 检测参数
                $user_id              = $this->user_info['id'];  // 用户id
                $params['old_pwd']    = $this->check_param('old_pwd');  // 旧密码
                $params['pwd']        = $this->check_param('pwd');  // 新密码
                $params['repeat_pwd'] = $this->check_param('repeat_pwd');  // 新密码_第二次输入
                $data                 = $user_service->user_reset_pwd($params, $user_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 重置用户密码为默认密码
     */
    public function user_reset_default_pwd()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'POST': // 添加路由配置
                // 检测参数
                $user_id              = $this->check_param('user_id');  // 用户id
                $data                 = $user_service->user_reset_default_pwd($user_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

    /**
     * 获取民族列表
     */
    public function get_nation_list()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'GET': // 添加路由配置
                // 检测参数
                $data = $user_service->get_nation_list();
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }


    /**
     * 获取新员工介绍
     */
    public function get_new_staff_list()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'GET': // 添加路由配置
                // 检测参数
                $page  = $this->get_safe_int_param('page', 1);
                $limit = $this->get_safe_int_param('limit', 10);
                $offset = ($page - 1) * $limit;
                $data = $user_service->get_new_staff_list($limit,$offset);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }


    /**
     * 获取员工分布
     */
    public function get_staff_found_info()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'GET': // 获取员工分布
                $data    = $user_service->get_staff_found_info();
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }


    /**
     * 获取更新钉钉数据入系统
     */
    public function update_structure_data()
    {
        $user_service = new UserService();
        switch ($this->method) {
            case 'POST': // 更新组织-部门信息
                $data    = $user_service->update_structure_data();
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL, 'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }


}
