<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;


use App\Http\Controllers\BaseController;
use App\Http\Service\Common\PositionService;
use library\Constants\StatusConstants;


class PositionController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 职务相关
     *
     */
    public function position_operate()
    {
        $role_service = new PositionService();
        switch ($this->method) {
            case 'GET':  // 获取职务列表
                // 检测参数
                $page             = $this->get_safe_int_param('page',1);
                $limit            = $this->get_safe_int_param('limit',10);
                $offset = ($page - 1) * $limit;
                $params['name']         = $this->data_arr['name'] ?? null;   // 名称
                $params['structure_id']         = $this->data_arr['department_id'] ?? null;   // 组织/部门id
                $params['key_word']    = $this->data_arr['key_word'] ?? null;     // 关键字
                $data = $role_service->get_position_list($params,$limit,$offset);
                break;
            case 'POST':  // 添加职务
                // 检测参数
                $params['name']             = $this->check_param('name'); // 名称
                $params['structure_id']     = $this->check_param('department_id');   // 组织/部门id
                $params['code']             = $this->data_arr['code'] ?? '';   // 编码
                $params['order']            = $this->data_arr['order'] ?? 0;  // 排序
                $data = $role_service->add_position($params);
                break;
            case 'PUT':  // 更新角色
                // 检测参数
                $id                      = $this->check_param('id');
                $params['name']             = $this->check_param('name'); // 名称
                $params['structure_id']     = $this->check_param('department_id');   // 组织/部门id
                $params['code']             = $this->data_arr['code'] ?? '';   // 编码
                $params['order']            = $this->data_arr['order'] ?? 0;  // 排序
                $data = $role_service->update_position($id,$params);
                break;
            case 'DELETE':  // 删除角色
                // 检测参数
                $id                      = $this->check_param('id');
                $data = $role_service->del_position($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

}
