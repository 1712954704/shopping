<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Hr;

use App\Http\Controllers\BaseController;
use App\Http\Service\Hr\ApprovalService;
use library\Constants\StatusConstants;

class ApprovalController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 考评审批流转
     *
     */
    public function approval_flow()
    {
        $approval_service = new ApprovalService();
        switch ($this->method){
            case "POST":
                $assessments_id = $this->check_param('assessments_id'); // 发起人员id
                $status = $this->check_param('status') ; // 状态 1=待审批 2=通过 3=拒绝 4=转审
                $remark = $this->data_arr['remark'] ?? ''; // 开始时间 时间戳
                $shift_user_id = $this->data_arr['shift_user_id'] ?? null; // 转审者id
                $handlers_id = $this->user_info['id'];
                $data = $approval_service->approval_flow($assessments_id,$handlers_id,$status,$remark,$shift_user_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

    /**
     * 待我审批列表
     *
    */
    public function await_approval_list()
    {
        $approval_service = new ApprovalService();
        switch ($this->method){
            case "GET":
                $params['id'] = $this->data_arr['id'] ?? null; // id assessments表主键id
                $params['user_id'] = $this->data_arr['user_id'] ?? null; // 发起人员id
                $params['status'] = $this->data_arr['status'] ?? null; // 状态 1=待审批 2=通过 3=拒绝 4=转审
                $params['start_time'] = $this->data_arr['start_time'] ?? null; // 开始时间 时间戳
                $params['end_time'] = $this->data_arr['end_time'] ?? null; // 结束时间 时间戳
                $params['check_type'] = $this->data_arr['check_type'] ?? 0; // 区分我的待办/我的已办/我发起的 1=我的已办 2=我发起的 0=我的待办 默认为0
                $check_user_id = $this->user_info['id']; // 查看人员id
                $page             = $this->get_safe_int_param('page',1);
                $params['limit']   = $this->get_safe_int_param('limit',10);
                $params['offset']  = ($page - 1) * $params['limit'];
                $params['type']             = $this->data_arr['type'] ?? null;     // 关键字
                $data = $approval_service->await_approval_list($params,$check_user_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

    /**
     * 获取流转信息
     *
     */
    public function flow_log()
    {
        $approval_service = new ApprovalService();
        switch ($this->method){
            case "GET":
                $id = $this->check_param('id');  // hr_assessments表主键id
                $data = $approval_service->flow_log($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

    /**
     * 抄送
     *
     */
    public function approval_copy_operate()
    {
        $approval_service = new ApprovalService();
        switch ($this->method){
            case "GET": // 抄送列表
                $params['id'] = $this->data_arr['id'] ?? null; // id assessments表主键id
                $params['start_time'] = $this->data_arr['start_time'] ?? null; // 开始时间 时间戳
                $params['end_time'] = $this->data_arr['end_time'] ?? null; // 结束时间 时间戳
                $check_user_id = $this->user_info['id']; // 查看人员id
                $page             = $this->get_safe_int_param('page',1);
                $params['limit']   = $this->get_safe_int_param('limit',10);
                $params['offset']  = ($page - 1) * $params['limit'];
                $data = $approval_service->approval_copy_list($params,$check_user_id);
                break;
            case "POST": // 添加抄送
                $assessments_id = $this->check_param('assessments_id'); // assessments表id
                $user_id = $this->check_param('user_id'); // 用户id
                $data = $approval_service->approval_copy_add($assessments_id,$user_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

    /**
     * 抄送查阅
     *
     */
    public function approval_copy_check()
    {
        $approval_service = new ApprovalService();
        switch ($this->method){
            case "POST": // 添加抄送
                $assessments_id = $this->check_param('assessments_id'); // assessments表id
                $data = $approval_service->approval_copy_check($assessments_id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

}
