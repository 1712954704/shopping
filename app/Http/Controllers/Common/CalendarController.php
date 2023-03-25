<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;

use App\Http\Controllers\BaseController;
use App\Http\Service\Common\CalendarService;
use library\Constants\StatusConstants;


class CalendarController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 日程安排相关
     *
     */
    public function calendar_operate()
    {
        $calendar_service = new CalendarService();
        switch ($this->method) {
            case 'GET':  // 获取日程列表
                // 检测参数
                $page             = $this->get_safe_int_param('page',1);
                $limit            = $this->get_safe_int_param('limit',100);
                $offset = ($page - 1) * $limit;
                $params['title']         = $this->data_arr['title'] ?? null;   // 名称
                $params['user_id']             = $this->user_info['id'];   // 系统类型
//                $params['structure_id']         = $this->data_arr['department_id'] ?? null;   // 组织/部门id
                $data = $calendar_service->get_calendar_list($params,$limit,$offset);
                break;
            case 'POST':  // 添加日程安排
                // 检测参数
                $params['title']             = $this->check_param('title'); // 标题
                $params['content']     = $this->check_param('content');   // 内容
                $params['address_remark']     = $this->check_param('address_remark');   // 地址说明
                $params['calendar_time']     = $this->check_param('calendar_time');   // 日历时间 时间戳
                $params['start_time']             = $this->data_arr['start_time'] ?? null;   // 起始时间
                $params['end_time']             = $this->data_arr['end_time'] ?? null;   // 结束时间
                $params['colour']             = $this->data_arr['colour'] ?? '';   // 颜色
                $params['system_type']             = $this->system_type;   // 系统类型
                $params['user_id']             = $this->user_info['id'];   // 系统类型
                $data = $calendar_service->add_calendar($params);
                break;
            case 'PUT':  // 更新日程
                // 检测参数
                $id                          = $this->check_param('id');
                $params['title']             = $this->check_param('title'); // 标题
                $params['content']           = $this->check_param('content');   // 内容
                $params['address_remark']    = $this->check_param('address_remark');   // 地址说明
                $params['calendar_time']     = $this->check_param('calendar_time');   // 日历时间 时间戳
                $params['start_time']        = $this->data_arr['start_time'] ?? null;   // 起始时间
                $params['end_time']          = $this->data_arr['end_time'] ?? null;   // 结束时间
                $params['colour']            = $this->data_arr['colour'] ?? '';   // 颜色
                $data = $calendar_service->update_calendar($id,$params);
                break;
            case 'DELETE':  // 日程安排
                // 检测参数
                $id                      = $this->check_param('id');
                $data = $calendar_service->del_calendar($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

}
