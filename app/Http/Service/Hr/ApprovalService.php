<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Hr;

use App\Http\Manager\Hr\ApprovalManager;
use App\Http\Service\Common\UserService;
use App\Models\Common\Structure;
use App\Models\Hr\ApprovalProcess;
use App\Models\Hr\ApprovalUser;
use App\Models\Hr\Assessment;
use App\Http\Service\ServiceBase;
use App\Models\Hr\AssessmentsCopy;
use App\Models\Common\Role;
use App\Models\Common\UserRole;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\ApprovalConstants;
use library\Constants\Model\ApprovalUserConstants;
use library\Constants\Model\AssessmentConstants;
use library\Constants\Model\AssessmentsCopyConstants;
use library\Constants\StatusConstants;
use library\Traits\Log;

class ApprovalService extends ServiceBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 审批流转(考评)
     * @param int $assessments_id hr_assessments表主键id 考评信息表
     * @param int $handlers_id 操作者id
     * @param int $status 2=通过 3=拒绝 4=转审 默认为1通过
     * @param string $remark 备注说明
     * @param int $shift_user_id 转审人员id $status为4时必须传值
     * @return mixed
    */
    public function approval_flow($assessments_id,$handlers_id,$status = 2,$remark='',$shift_user_id = null)
    {
        $approval_manager = new ApprovalManager();
        try {
            // 解决并发请求问题 加锁
            $locked = $approval_manager->lock_approval($assessments_id);
            if (!$locked) {
                throw new \Exception('',StatusConstants::ERROR_FORBIDDEN_CONCURRENCY_LIMIT);
            }
            $user_service = new UserService();
            // 获取考评信息
            $check_result = Assessment::find($assessments_id);
            $check_result = \Common::laravel_to_array($check_result);
            if (empty($check_result)){
                throw new \Exception('not found assessments_id',StatusConstants::ERROR_INVALID_PARAMS);
            }
            $user_id = $check_result['user_id']; // 提交者id
            $node = $approval_id =  0; // 审批者id
            // 获取提交者的信息
            $user_info = $user_service->get_user_info_by_id($user_id);
            $department_id = $user_info['data']['department_id']; // 部门id
            // 获取提交者部门信息
            $structure = Structure::find($department_id);
            $manager_id = $structure->manager_id; // 部门主管id
            $handler_id = $structure->handler_id; // 部门经理id
            // 获取部门上级组织信息
            $structure_service = new StructureService();
            $up_level_structure = $structure_service->get_up_level_structure($structure->pid);
            $duty_id = $up_level_structure['duty_id'];       // 公司负责人id
            $approval_data = [
                'user_id' => $user_id,
                'assessments_id' => $assessments_id,
                'department_id' => $department_id,
            ];
            $approval_user_data['user_id'] = $user_id;
            // 查询流转步骤
            $where_map = [
                'assessments_id' => $assessments_id
            ];
            $flow_result = ApprovalProcess::where($where_map)->orderBy('created_at','desc')->limit(1)->get();
            $flow_result = \Common::laravel_to_array($flow_result);
            // 开启事务
            // 流程节点 流程节点 -1=自身修改审核 1=发起由主管/经理审核 2=主管审核 3=经理审核 4=公司负责人审核 5=自评 6=主管审核 7=经理审核 8=公司负责人审核  9=hr审核 10=end
            // 没有流转步骤则初始化创建一个
            if (empty($flow_result)){
                // 判断是用户是否为部门主管/经理 由谁审核
                if ($user_id != $manager_id && $user_id != $handler_id){
                    $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_MANAGER;
                    $approval_id = $manager_id;
                }else if($user_id == $manager_id){ // 部门经理为审批者
                    $approval_id = $handler_id;
                    $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_HANDLER;
                }else if ($user_id == $handler_id){ // 公司负责人为审批者
                    $approval_id = $duty_id;
                    $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_DUTY;
                }
                if (empty($approval_id)){
//                    throw new \Exception('not found approval_id',StatusConstants::ERROR_INVALID_PARAMS_STRUCTURE);
                    throw new \Exception('负责人暂无,请联系管理员设置',StatusConstants::ERROR_INVALID_PARAMS_STRUCTURE);
                }
                // 获取收件人信息
                $to_info = $user_service->get_user_info_by_id($approval_id);
                if (!empty($to_info['data']['email'])){
                    // 添加考评流程 没有邮箱则返回提醒
                    $approval_data['approval_user_type'] = $approval_user_type;
                    $approval_data['node'] = ApprovalConstants::NODE_SHIFT;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data = [  // 审批人员关联表
                        'approval_id' => $create_user_result->id,
                        'user_id' => $approval_id,
                    ];
                    ApprovalUser::insert($approval_user_data);
                    // 发送邮件
                    $assessments = new AssessmentService();
                    $data_email = [
                        'content' => [
                            'lead_name' => $to_info['data']['name'], // 领导名称
                            'phone' => $user_info['data']['phone'], // 手机号
                            'full_name' => ltrim($user_info['data']['structure_name'] . '/' . $user_info['data']['department_name'],'/'), // 组织-部门名称
                            'time' => date("Y-m-d H:i:s"), // 时间
                            'name' => $user_info['data']['name'], // 员工名称
                        ],
                        'name' => $user_info['data']['name'], // 员工名称
                        'user_id' => $user_info['data']['id'], // 员工id
                        'to' =>  $to_info['data']['email'], // 员工邮箱
                        'subject' => $user_info['data']['name'] . '的月度绩效沟通会议邀请', // 标题
                    ];
                    Log::NewInfo([
                        'data_email' => $data_email,
                        'assessments_id' => $assessments_id,
                        'handlers_id' => $handlers_id,
                        'status' => $status,
                        'remark' => $remark,
                        'shift_user_id' => $shift_user_id,
                    ],__FUNCTION__ . ' send_email_log_start');
                    $res = $assessments->send_email($data_email);
                    Log::NewInfo([
                        'data_email' => $data_email,
                        'assessments_id' => $assessments_id,
                        'handlers_id' => $handlers_id,
                        'status' => $status,
                        'remark' => $remark,
                        'shift_user_id' => $shift_user_id,
                        'res' => $res,
                    ],__FUNCTION__ . ' send_email_log_ok');
                }else{
                    throw new \Exception($to_info['data']['name'] . ' 邮箱不存在,请联系管理员添加',StatusConstants::ERROR_INVALID_USER_EMAIL);
                }
                throw new \Exception('',StatusConstants::SUCCESS);
            }
            // 获取最新流程的记录
            $approval_process_id = $flow_result[0]['id'];
            // 获取流程信息
            $approval_process_result = ApprovalProcess::find($approval_process_id);
            $approval_process_result = \Common::laravel_to_array($approval_process_result);
            $approval_data['pid'] = $approval_process_id; // 设置父级节点

            // 获取当前可操作者id
            $allow_where  = [
                'approval_id' => $approval_process_id
            ];
            $allow_result = ApprovalUser::where($allow_where)->get();
            $allow_result = \Common::laravel_to_array($allow_result);
            $allow_ids = array_column($allow_result,'user_id');
            // 判断当前操作者是否可操作
            if (!in_array($handlers_id,$allow_ids)){
                $this->return_data['code'] = StatusConstants::ERROR_INVALID_PARAMS_HANDLERS;
                throw new \Exception('',StatusConstants::ERROR_INVALID_PARAMS_HANDLERS);
            }

            $status_data['status'] = $status;
            $time = time();
            switch ($status){
                case 2: // 通过
                    ApprovalProcess::where(['id'=>$approval_process_id])->update(['status' => ApprovalConstants::STATUS_PASS,'check_time' => $time]);
                    ApprovalUser::where(['approval_id'=>$approval_process_id,'user_id'=>$handlers_id])->update(['status' => ApprovalUserConstants::STATUS_CHECK_FINISH,'remark' => $remark]);
                    goto FLOW;
                    break;
                case 3: // 拒绝
                    ApprovalProcess::where(['id'=>$approval_process_id])->update(['status' => ApprovalConstants::STATUS_REJECT,'check_time' => $time]);
                    ApprovalUser::where(['approval_id'=>$approval_process_id,'user_id'=>$handlers_id])->update(['status' => ApprovalUserConstants::STATUS_CHECK_FINISH,'remark' => $remark]);
                    goto REJECT;
                case 4: // 转审
                    ApprovalProcess::where(['id'=>$approval_process_id])->update(['status' => ApprovalConstants::STATUS_PASS,'check_time' => $time]);
                    ApprovalUser::where(['approval_id'=>$approval_process_id,'user_id'=>$handlers_id])->update(['status' => ApprovalUserConstants::STATUS_CHECK_FINISH,'remark' => $remark]);
                    goto SHIFT;
                    break;
            }

            FLOW:
            // 根据当前节点流程步骤流转
            switch ($approval_process_result['node']){
                case ApprovalConstants::NODE_SELF_AGAIN: // 转为自身审核
                    // 判断是用户是否为部门主管/经理 由谁审核
                    if ($user_id != $manager_id && $user_id != $handler_id){
                        $approval_id = $manager_id;
                        $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_MANAGER;
                    }else if($user_id == $manager_id){ // 部门经理为审批者
                        $approval_id = $handler_id;
                        $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_HANDLER;
                    }else if ($user_id == $handler_id){ // 公司负责人为审批者
                        $approval_id = $duty_id;
                        $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_DUTY;
                    }
                    $approval_data['approval_user_type'] = $approval_user_type;
                    // 根据审核者走不同的流程节点
                    switch ($approval_user_type){
                        case ApprovalConstants::APPROVAL_USER_TYPE_MANAGER:
                            $node = ApprovalConstants::NODE_MANAGER;
                            break;
                        case ApprovalConstants::APPROVAL_USER_TYPE_HANDLER:
                            $node = ApprovalConstants::NODE_HANDLER;
                            break;
                        case ApprovalConstants::APPROVAL_USER_TYPE_DUTY:
                            $node = ApprovalConstants::NODE_DUTY;
                            break;
                    }
                    $approval_data['node'] = $node;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data['user_id'] = $approval_id;
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    break;
                case ApprovalConstants::NODE_SHIFT: //发起审批由主管/经理/公司负责人审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_SELF;
                    $approval_data['node'] = ApprovalConstants::NODE_SELF_AGAIN;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    break;
                case ApprovalConstants::NODE_MANAGER: // 主管审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_HANDLER;
//                    $approval_data['node'] = ApprovalConstants::NODE_HANDLER;
                    $approval_data['node'] = ApprovalConstants::NODE_SELF;
                    $create_user_result = ApprovalProcess::create($approval_data);
//                    $approval_user_data['user_id'] = $handler_id;
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    // 抄送给经理
                    $this->approval_copy_add($assessments_id,$handler_id);
                    break;
                case ApprovalConstants::NODE_HANDLER: // 经理审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_SELF;
                    $approval_data['node'] = ApprovalConstants::NODE_SELF;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    break;
                case ApprovalConstants::NODE_DUTY: // 公司负责人审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_SELF;
                    $approval_data['node'] = ApprovalConstants::NODE_SELF;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data['user_id'] = $duty_id;
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    break;
                case ApprovalConstants::NODE_SELF: // 自评 开始评分
                    // 判断是用户是否为部门主管/经理 由谁审核
                    if ($user_id != $manager_id && $user_id != $handler_id){
                        $approval_id = $manager_id;
                        $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_MANAGER;
                    }else if($user_id == $manager_id){ // 部门经理为审批者
                        $approval_id = $handler_id;
                        $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_HANDLER;
                    }else if ($user_id == $handler_id){ // 公司负责人为审批者
                        $approval_id = $duty_id;
                        $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_DUTY;
                    }
                    $approval_data['approval_user_type'] = $approval_user_type;
                    // 根据审核者走不同的流程节点
                    switch ($approval_user_type){
                        case ApprovalConstants::APPROVAL_USER_TYPE_MANAGER:
                            $node = ApprovalConstants::NODE_MANAGER_AGAIN;
                            break;
                        case ApprovalConstants::APPROVAL_USER_TYPE_HANDLER:
                            $node = ApprovalConstants::NODE_HANDLER_AGAIN;
                            break;
                        case ApprovalConstants::APPROVAL_USER_TYPE_DUTY:
                            $node = ApprovalConstants::NODE_DUTY_AGAIN;
                            break;
                    }
                    $approval_data['approval_user_type'] = $approval_user_type;
                    $approval_data['node'] = $node;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data['user_id'] = $approval_id;
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    break;
                case ApprovalConstants::NODE_MANAGER_AGAIN: // 再次主管审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_HANDLER;
                    $approval_data['node'] = ApprovalConstants::NODE_HANDLER_AGAIN;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    $approval_user_data['user_id'] = $handler_id;
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    ApprovalUser::insert($approval_user_data);
                    break;
                case ApprovalConstants::NODE_HANDLER_AGAIN: // 再次经理审核
                case ApprovalConstants::NODE_DUTY_AGAIN: // 再次公司负责人审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_HR;
                    $approval_data['node'] = ApprovalConstants::NODE_HR;
                    $create_user_result = ApprovalProcess::create($approval_data);
                    // 由hr角色审核 查询hr人员
                    $where_hr = [
                        "name" => "HR"
                    ];
                    $hr_result = Role::where($where_hr)->first();
                    $hr_result = \Common::laravel_to_array($hr_result);
                    if (!$hr_result){
                        $this->return_data['code'] = StatusConstants::ERROR_INVALID_ROLE_HR;
                        throw new \Exception();
                    }
                    // 获取hr角色成员
                    $where_hr_user = [
                        "role_id"   => $hr_result['id']
                    ];
                    $hr_user_result = UserRole::where($where_hr_user)->get();
                    $hr_user_result = \Common::laravel_to_array($hr_user_result);
                    if (!$hr_user_result){
                        $this->return_data['code'] = StatusConstants::ERROR_INVALID_ROLE_HR;
                        throw new \Exception();
                    }
                    $approval_user_data['approval_id'] = $create_user_result->id;
                    foreach ($hr_user_result as $item){
                        $approval_user_data['user_id'] = $item['user_id'];
                        ApprovalUser::insert($approval_user_data);
                    }
                    break;
                case ApprovalConstants::NODE_HR: // 再次公司负责人审核
                    $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_NO_BODY;
                    $approval_data['node'] = ApprovalConstants::NODE_END;
                    $approval_data['status'] = ApprovalConstants::STATUS_END;
                    ApprovalProcess::create($approval_data);
                    break;
            }
            throw new \Exception('',StatusConstants::SUCCESS);
            REJECT:
            // 拒绝审批则返回上一级审批 查询上一级
            if ($approval_process_result['pid']){
                $parent_result = ApprovalProcess::find($approval_process_result['pid']);
                $approval_data['approval_user_type'] = $parent_result->approval_user_type;
                $approval_data['node'] = $parent_result->node;
//                $approval_data['pid'] = $parent_result->id;
                $approval_data['pid'] = $approval_process_result['id'];
                $approval_data['status'] = ApprovalConstants::STATUS_CHECK_REFUTE;
                $create_user_result = ApprovalProcess::create($approval_data);
//                $approval_user_result = ApprovalUser::where(['approval_id' => $approval_process_result['pid'],'status' => 1])->get();
                $approval_user_result = ApprovalUser::where(['approval_id' => $approval_process_result['pid']])->get();
                $approval_user_data['approval_id'] = $create_user_result->id;
                foreach ($approval_user_result as $item){
                    $approval_user_data['user_id'] = $item->user_id;
                    ApprovalUser::insert($approval_user_data);
                }
                return $this->return_data;
            }else{ // 由自身审核
                $approval_data['approval_user_type'] = ApprovalConstants::APPROVAL_USER_TYPE_SELF;
                $approval_data['node'] = ApprovalConstants::NODE_SELF_AGAIN;
                $approval_data['pid'] = $approval_process_result['id'];
                $create_user_result = ApprovalProcess::create($approval_data);
                $approval_user_data['approval_id'] = $create_user_result->id;
                ApprovalUser::insert($approval_user_data);
                return $this->return_data;
            }
            SHIFT: // 转审
            $approval_data['pid'] = $approval_process_result['id'];
            $approval_data['node'] = $approval_process_result['node'];
            $approval_data['approval_user_type'] = $approval_process_result['approval_user_type'];
            $approval_data['status'] = ApprovalConstants::STATUS_SHIFT;
            $create_user_result = ApprovalProcess::create($approval_data);
            $approval_user_data['user_id'] = $shift_user_id;
            $approval_user_data['approval_id'] = $create_user_result->id;
            ApprovalUser::insert($approval_user_data);
        }catch (\Exception $e){
//            var_dump($e->getLine());
//            var_dump($e->getMessage());die();
            $this->return_data['code'] =  $e->getCode();
            $this->return_data['msg'] = $e->getMessage();
            Log::NewInfo([
                'assessments_id' => $assessments_id,
                'handlers_id' => $handlers_id,
                'status' => $status,
                'remark' => $remark,
                'shift_user_id' => $shift_user_id,
                'exception' => [
                    'line' =>$e->getLine(),
                    'msg' =>$e->getMessage(),
                    'file' =>$e->getFile(),
                ],
            ],__FUNCTION__ . ' send_email_log_error');
        } finally {
            if ($this->return_data['code'] == StatusConstants::SUCCESS){
                // 正常返回则同步考评表流程节点及更新状态
                $data_assessment['node'] = $approval_data['node'];
                if ($approval_data['node'] == ApprovalConstants::NODE_SHIFT){
                    $data_assessment['status'] = AssessmentConstants::STATUS_CHECK_IN;
                }
                if ($approval_data['node'] == ApprovalConstants::NODE_END){
                    $data_assessment['status'] = AssessmentConstants::STATUS_CHECK_END;
                }
                $where_assessment = [
                    'id' => $assessments_id
                ];
                Assessment::where($where_assessment)->update($data_assessment);
            }
            // 解锁
            $locked && $approval_manager->unlock_approval($assessments_id);
            return $this->return_data;
        }

    }


    /**
     * 获取提交者应该由谁审核的信息
     * @param int $user_id
     * @param int $manager_id
     * @param int $handler_id
     * @param int $duty_id
     * @return array
    */
    public function get_submitter_to_check_info($user_id,$manager_id,$handler_id,$duty_id)
    {
        // 判断是用户是否为部门主管/经理 由谁审核
        if ($user_id != $manager_id && $user_id != $handler_id){
            $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_MANAGER;
            $approval_id = $manager_id;
        }else if($user_id == $manager_id){ // 部门经理为审批者
            $approval_id = $handler_id;
            $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_HANDLER;
        }else if ($user_id == $handler_id){ // 公司负责人为审批者
            $approval_id = $duty_id;
            $approval_user_type = ApprovalConstants::APPROVAL_USER_TYPE_DUTY;
        }
        $data = [
            'approval_user_type' => $approval_user_type,
            'approval_id' => $approval_id
        ];
        return $data;
    }


    /**
     * 获取流转信息
     * @param int $assessments_id hr_assessments表主键id
     * @return array
     */
    public function flow_log($assessments_id)
    {
        try {
            // 查询提交记录
            $assessments_info = Assessment::find($assessments_id);
            // 查询审批记录
//            $sql = "select a.node,a.status,a.user_id commit_user_id,b.user_id approval_user_id,a.created_at from hr_approval_process a
            $sql = "select a.node,a.status,b.user_id approval_user_id,b.remark,a.created_at,a.check_time from hr_approval_process a
            left join hr_approval_user b on a.id = b.approval_id
            where a.assessments_id = $assessments_id order by b.created_at desc";
            $result = DB::connection('mysql_hr')->select($sql);
            $result_data = array_map('get_object_vars', $result);

            $user_service = new UserService();
            // 获取提交者用户缓存
            $commit_info = $user_service->get_user_info_by_id($assessments_info->user_id);

            // 获取考评信息
            $assessments_info = Assessment::find($assessments_id);
            $commit = [
                'created_at' => strtotime($assessments_info->created_at), // 考评流程发起时间
                'commit_user_id' => $commit_info['data']['id'],
                'commit_user_name' => $commit_info['data']['name'],
            ];
            $node = [];
            foreach ($result_data as $val){
                $user_info = $user_service->get_user_info_by_id($val['approval_user_id']);
                if (isset($node[$val['node']])){
                    $node[$val['node']]['approval_user_ids'][] = $val['approval_user_id'];
                    $node[$val['node']]['approval_user_name'] .= ','.($user_info['data']['name'] ?? '');
                    $node[$val['node']]['remark'][$val['approval_user_id']] = $val['remark'];
                }else{
                    $node[$val['node']]['created_at'] = strtotime($val['created_at']);
                    $node[$val['node']]['check_time'] = $val['check_time'];
                    $node[$val['node']]['status'] = $val['status'];
                    $node[$val['node']]['status_name'] = ApprovalConstants::STATUS_MAP[$val['status']];
                    $node[$val['node']]['remark'][$val['approval_user_id']] = $val['remark'];
                    $node[$val['node']]['approval_user_ids'][] = $val['approval_user_id'];
                    $node[$val['node']]['approval_user_name'] = $user_info['data']['name'] ?? '';
                    $node[$val['node']]['node_name'] = ApprovalConstants::NODE_MAP[$val['node']];
                }
            }
            $this->return_data['data']['commit'] = $commit;
//            $this->return_data['data']['node'] = $node;
            $this->return_data['data']['node'] = array_values($node);
        }catch (\Exception $e){
            // 记录log
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 待我审批列表
     * @param array $params
     * @param int $check_user_id  // 审核/查看人员id
     * @return array
     */
    public function await_approval_list($params,$check_user_id)
    {
        try {
            $id = $params['id']; // id assessments表主键id
            $user_id = $params['user_id']; // 发起人员id
            $type = $params['type']; // 所属流程
            $status = $params['status']; // 状态 1=待审批 2=通过 3=拒绝 4=转审
            $start_time = $params['start_time']; // 开始时间 时间戳
            $end_time = $params['end_time']; // 结束时间 时间戳
            $limit = $params['limit']; // 结束时间 时间戳
            $offset = $params['offset']; // 结束时间 时间戳
            $sql_limit = " limit $offset,$limit";
            // 默认type=1为考评流程
            $where = '1';
            // 区分我的待办/我的已办/我发起的 1=我的已办 2=我发起的 0=我的待办 默认为0
            $check_type = $params['check_type'] ?? 0;
            if ($check_type == 1){ // 我的已办
                $where .= ' and a.type = 1 and c.user_id='.$check_user_id;
                if ($user_id){
                    $where .= ' AND b.user_id='.$user_id;
                }
                $where .= ' AND c.status = '.ApprovalUserConstants::STATUS_CHECK_FINISH;
            }else if ($check_type == 2){ // 我发起的
                $where .= ' and b.user_id = '.$check_user_id;
            }else{ // 我的待办
                $where .= ' and a.type = 1 and c.user_id='.$check_user_id;
//            $where = ' a.type = 1 and c.user_id=29';
                if ($user_id){
                    $where .= ' AND b.user_id='.$user_id;
                }
                $where .= ' AND c.status = '.ApprovalUserConstants::COMMON_STATUS_NORMAL;
            }

            if ($status){
                $where .= ' AND b.status in ('.$status.') ';
            }
            if ($start_time){
                $where .= " AND a.created_at >= '".date('Y-m-d H:i:s',$start_time)."'";
            }
            if ($end_time){
                $where .= " AND a.created_at <= '".date('Y-m-d H:i:s',$end_time)."'";
            }
            if ($id){
                $where .= ' AND a.id = ' . $id;
            }
            if ($type){
                $where .= ' AND b.type = ' . $type;
            }

            $sql = "SELECT a.id,a.type,a.user_id,a.created_at FROM hr_assessments a
                LEFT JOIN hr_approval_process b ON a.id = b.assessments_id
                LEFT JOIN hr_approval_user c ON b.id = c.approval_id
                WHERE $where GROUP BY a.id order by a.id desc ".$sql_limit;
            $sql_user_ids = "SELECT a.user_id FROM hr_assessments a
                LEFT JOIN hr_approval_process b ON a.id = b.assessments_id
                LEFT JOIN hr_approval_user c ON b.id = c.approval_id
                WHERE $where GROUP BY a.user_id ";
            $sql_count = "SELECT count(distinct(a.id)) count FROM hr_assessments a
                LEFT JOIN hr_approval_process b ON a.id = b.assessments_id
                LEFT JOIN hr_approval_user c ON b.id = c.approval_id
                WHERE $where ";
            $result = DB::connection('mysql_hr')->select($sql);
            $result_count = DB::connection('mysql_hr')->select($sql_count);
            $result_user_ids = DB::connection('mysql_hr')->select($sql_user_ids);
            $result_data = array_map('get_object_vars', $result);
            $result_count = array_map('get_object_vars', $result_count);
            $result_user_ids = array_map('get_object_vars', $result_user_ids);
            $assessments_ids = array_column($result_data,'id');
            $tem_arr = [];
            foreach ($assessments_ids as $key => $item){
                // 查询最新的审核状态
                $sql = "SELECT a.id,a.status,a.node FROM hr_approval_process a WHERE a.assessments_id = $item order by a.created_at desc limit 1";
                $result_tem = DB::connection('mysql_hr')->select($sql);
                $tem_data = array_map('get_object_vars', $result_tem);
                $tem_arr[$item]['id'] = $tem_data[0]['id'];
                $tem_arr[$item]['node'] = $tem_data[0]['node'];
                $tem_arr[$item]['status'] = $tem_data[0]['status'];
            }
            $user_service = new UserService();
            // 获取提交者用户缓存
            foreach ($result_data as &$val){
                // 获取用户信息
                $user_info = $user_service->get_user_info_by_id($val['user_id']);
                $val['user_name'] = $user_info['data']['name'];  // 发起者名称
                $val['title'] = $user_info['data']['name'] . '的考评流程';  // 标题
                $val['way_type'] = '考评流程'; // 所属流程
                if (isset($tem_arr[$val['id']])){
                    $val['status'] = $tem_arr[$val['id']]['status'];
                    $val_arr = $val;
                    $val['node_name'] = ApprovalConstants::NODE_MAP[$tem_arr[$val['id']]['node']];
                    $val['node'] = $tem_arr[$val['id']]['node'];
                    $val['is_check'] = 0;
                    // 查询最新的审核人员
                    $sql = "SELECT * FROM hr_approval_user a WHERE a.approval_id = ".$tem_arr[$val['id']]['id'];
                    $result_tem = DB::connection('mysql_hr')->select($sql);
                    $tem_check = array_column(array_map('get_object_vars', $result_tem),'user_id');
                    if (in_array($check_user_id,$tem_check)){
                        $val['is_check'] = 1;
                    }
                }
            }
            // 获取发起人信息
            $result_user_ids = array_column($result_user_ids,'user_id');
            $source_info = [];
            foreach ($result_user_ids  as $v){
                $data_user_info = $user_service->get_user_info_by_id($v);
                $source_info[]  = [
                    'user_id' => $v,
                    'user_name' => $data_user_info['data']['name'],
                ];
            }
            $this->return_data['data']['data'] = $result_data;
            $this->return_data['data']['total'] = $result_count[0]['count'] ?? 0;
            $this->return_data['data']['source_info'] = $source_info;
        }catch (\Exception $e){
            // 记录log
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 抄送列表
     * @param array $params
     * @param int $check_user_id  // 查看人员id
     * @return array
     *
    */
    public function approval_copy_list($params,$check_user_id)
    {
        try {
            $id = $params['id']; // id assessments表主键id
            $start_time = $params['start_time']; // 开始时间 时间戳
            $end_time = $params['end_time']; // 结束时间 时间戳
            $limit = $params['limit']; // 结束时间 时间戳
            $offset = $params['offset']; // 结束时间 时间戳
            $sql_limit = " limit $offset,$limit";
            $where = ' b.user_id = '.$check_user_id;
            if ($start_time){
                $where .= ' AND a.created_at >='.date('Y-md H:i:s',$start_time);
            }
            if ($end_time){
                $where .= ' AND a.created_at >='.date('Y-md H:i:s',$end_time);
            }
            if ($id){
                $where .= ' AND a.id = ' . $id;
            }
            $sql = "select a.id,a.type,a.user_id,a.created_at,b.created_at copy_time from hr_assessments a left join hr_assessments_copy b on a.id = b.assessments_id where $where group by a.id ".$sql_limit;
            $sql_count = "select count(distinct(a.id)) total from hr_assessments a left join hr_assessments_copy b on a.id = b.assessments_id where $where ";
            $sql_user_ids = "select a.user_id from hr_assessments a left join hr_assessments_copy b on a.id = b.assessments_id where $where ";
            $result = DB::connection('mysql_hr')->select($sql);
            $result_total = DB::connection('mysql_hr')->select($sql_count);
            $result_user_ids = DB::connection('mysql_hr')->select($sql_user_ids);
            $result_data = array_map('get_object_vars', $result);
            $result_total = array_map('get_object_vars', $result_total);
            $result_user_ids = array_map('get_object_vars', $result_user_ids);
            $assessments_ids = array_column($result_data,'id');
            $tem_arr = [];
            foreach ($assessments_ids as $key => $item){
                // 查询最新的审核状态
                $sql = "SELECT a.id,a.status,a.node FROM hr_approval_process a WHERE a.assessments_id = $item order by a.created_at desc limit 1";
                $result_tem = DB::connection('mysql_hr')->select($sql);
                $tem_data = array_map('get_object_vars', $result_tem);
                if ($tem_data){
                    $tem_arr[$item]['id'] = $tem_data[0]['id'];
                    $tem_arr[$item]['node'] = $tem_data[0]['node'];
                    $tem_arr[$item]['status'] = $tem_data[0]['status'];
                }
            }
            $user_service = new UserService();
            // 获取提交者用户缓存
            foreach ($result_data as &$val){
                // 获取用户信息
                $user_info = $user_service->get_user_info_by_id($val['user_id']);
                $val['user_name'] = $user_info['data']['name'];  // 发起者名称
                $val['title'] = $user_info['data']['name'] . '的考评流程';  // 标题
                $val['way_type'] = '考评流程'; // 所属流程
                if (isset($tem_arr[$val['id']])){
                    $val['status'] = $tem_arr[$val['id']]['status'];
                    $val_arr = $val;
                    $val['node_name'] = ApprovalConstants::NODE_MAP[$tem_arr[$val['id']]['node']];
                    $val['node'] = $tem_arr[$val['id']]['node'];
                    $val['is_check'] = 0;
                    // 查询最新的审核人员
                    $sql = "SELECT * FROM hr_approval_user a WHERE a.approval_id = ".$tem_arr[$val['id']]['id'];
                    $result_tem = DB::connection('mysql_hr')->select($sql);
                    $tem_check = array_column(array_map('get_object_vars', $result_tem),'user_id');
                    if (in_array($check_user_id,$tem_check)){
                        $val['is_check'] = 1;
                    }
                }
            }

            // 获取发起人信息
            $result_user_ids = array_column($result_user_ids,'user_id');
            $source_info = [];
            foreach ($result_user_ids  as $v){
                $data_user_info = $user_service->get_user_info_by_id($v);
                $source_info[]  = [
                    'user_id' => $v,
                    'user_name' => $data_user_info['data']['name'],
                ];
            }

            $this->return_data['data']['data'] = $result_data;
            $this->return_data['data']['total'] = $result_total[0]['total'];
            $this->return_data['data']['source_info'] = $source_info;
        }catch (\Exception $e){
            // 记录log
//            var_dump($e->getLine());
//            var_dump($e->getMessage());die();
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 添加抄送
     * @param int $assessments_id
     * @param int $user_id
     * @return array
     *
     */
    public function approval_copy_add($assessments_id,$user_id)
    {
        try {
            // 查询抄送的考评是否开启审核
//            $check_info = ApprovalProcess::find($assessments_id);
            $where = [
                'assessments_id' => $assessments_id
            ];
            $check_info = ApprovalProcess::where($where)->get();
            $check_info = \Common::laravel_to_array($check_info);
            if (!$check_info){
                $this->return_data['code'] = StatusConstants::ERROR_ACCESS_ROBOT_SERVICE_API_EXCEPTION;
                throw new \Exception();
            }
            $data = [
                'assessments_id' => $assessments_id,
                'user_id' => $user_id,
            ];
            AssessmentsCopy::insert($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            return $this->return_data;
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 抄送查阅
     * @param int $assessments_id
     * @param int $user_id
     * @return array
     *
     */
    public function approval_copy_check($assessments_id,$user_id)
    {
        try {
            $data = [
                'status' => AssessmentsCopyConstants::STATUS_CHECK,
            ];
            $where = [
                'assessments_id' => $assessments_id,
                'user_id' => $user_id
            ];
            AssessmentsCopy::where($where)->update($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            return $this->return_data;
        } finally {
            return $this->return_data;
        }
    }

}
