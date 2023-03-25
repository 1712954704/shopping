<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;


use App\Http\Controllers\BaseController;
use App\Http\Service\Common\NoticeService;
use App\Http\Service\Common\PositionService;
use library\Constants\Model\NoticeConstants;
use library\Constants\StatusConstants;


class NoticeController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 系统公告相关
     *
     */
    public function notice_operate()
    {
        $notice_service = new NoticeService();
        switch ($this->method) {
            case 'GET':  // 获取系统公告列表
                // 检测参数
                $page                           = $this->get_safe_int_param('page',1);
                $limit                          = $this->get_safe_int_param('limit',100);
                $offset                         = ($page - 1) * $limit;
                $params['type']                 = $this->data_arr['type'] ?? null;   // 类型 1=通知 2=公告
                $params['title']                = $this->data_arr['title'] ?? null;   // 名称
                $params['id']                   = $this->data_arr['id'] ?? null;   // id系统公告主键id
                $user_id = $this->user_info['id'];
                $data = $notice_service->get_notice_list($params,$user_id,$limit,$offset);
                break;
            case 'POST':  // 添加系统公告
                // 检测参数
                $params['title']                = $this->check_param('title'); // 标题
                $params['content']              = $this->check_param('content');   // 内容
                $params['type']                 = $this->check_param('type');   // 类型 1=通知 2=公告
                $params['level']                = $this->data_arr['level'] ?? 1;   // 重要程度 1=一般 2=重要 3=紧急
                $params['status']               = $this->data_arr['status'] ?? NoticeConstants::COMMON_STATUS_WAIT;   // 状态 1=暂存 2=发布
                $params['start_time']           = $this->data_arr['start_time'] ?? null;   // 起始时间
                $params['end_time']             = $this->data_arr['end_time'] ?? null;   // 结束时间
                // 通知类型起始-结束时间不能为空
                if ($params['type'] == 1 && (empty($params['start_time']) || empty($params['end_time']))){
                    \Common::response_error_header(StatusConstants::ERROR_ILLEGAL_PARAMS, 'Invalid Params StartTime Or EndTime');
                }
                $params['system_type']          = $this->system_type;   // 系统类型
                $file_ids           = $this->data_arr['file_ids'] ?? [];  // 附件 数组形式接收,可有多个附件
                $user_ids            = $this->data_arr['user_ids'] ?? [];  // 通知用户 数组形式接收,可有多个用户
                $data = $notice_service->add_notice($params,$file_ids,$user_ids);
                break;
            case 'PUT':  // 更新公告
                // 检测参数
                $id                             = $this->check_param('id');
                $params['title']                = $this->check_param('title'); // 标题
                $params['content']              = $this->check_param('content');   // 内容
                $params['type']                 = $this->check_param('type');   // 类型 1=通知 2=公告
                $params['level']                = $this->data_arr['level'] ?? 1;   // 重要程度 1=一般 2=重要 3=紧急
                $params['status']               = $this->data_arr['status'] ?? NoticeConstants::COMMON_STATUS_WAIT;   // 状态 1=暂存 2=发布
                $params['start_time']           = $this->check_param('start_time');   // 起始时间
                $params['end_time']             = $this->check_param('end_time');   // 结束时间
                $params['system_type']          = $this->system_type;   // 系统类型
                $file_ids           = $this->data_arr['file_ids'] ?? [];  // 附件 数组形式接收,可有多个附件
                $user_ids            = $this->data_arr['user_ids'] ?? [];  // 通知用户 数组形式接收,可有多个用户
                $data = $notice_service->update_notice($id,$params,$file_ids,$user_ids);
                break;
            case 'DELETE':  // 删除公告
                // 检测参数
                $id                      = $this->check_param('id');
                $data = $notice_service->del_notice($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

}
