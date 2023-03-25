<?php
/**
 * User: Jack
 * Date: 2023/03/22
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Hr;

use App\Http\Controllers\BaseController;
use App\Http\Service\Hr\ApprovalService;
use App\Http\Service\Hr\QuarterSummaryService;
use library\Constants\StatusConstants;

class QuarterSummaryController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 季度会议纪要相关
     *
     */
    public function quarter_summary_operate()
    {
        $quarter_summary_service = new QuarterSummaryService();
        switch ($this->method){
            case "GET":  // 添加纪要
                $params['user_id'] = $this->user_info['id']; // 纪要建立者id
                $params['department_id'] = $this->data_arr['department_id'] ?? 0; // 纪要建立者id
                $data = $quarter_summary_service->quarter_summary_list($params);
                break;
            case "POST":  // 添加纪要
                $params['target'] = $this->data_arr['target'] ?? ''; // 目标
                $params['actuality'] = $this->data_arr['actuality'] ?? ''; // 现状
                $params['progress'] = $this->data_arr['progress'] ?? ''; // 进展
                $params['quicken_factor'] = $this->data_arr['quicken_factor'] ?? ''; // 加快进展因素
                $params['change_point'] = $this->data_arr['change_point'] ?? ''; // 机会点
                $params['need_support'] = $this->data_arr['need_support'] ?? ''; // 需要的支持
                $params['other'] = $this->data_arr['other'] ?? ''; // 其他
                $params['number'] = $this->data_arr['number'] ?? 1; // 其他
                $params['user_id'] = $this->user_info['id']; // 纪要建立者id
                $params['department_id'] = $this->user_info['department_id'] ?? 0; // 纪要建立者id
                $file_ids           = $this->data_arr['file_ids'] ?? [];  // 附件 数组形式接收,可有多个附件
                $data = $quarter_summary_service->quarter_summary_add($params,$file_ids);
                break;
            case "PUT":  // 更新纪要
                $id = $this->check_param('id'); // 纪要id
                $params['target'] = $this->data_arr['target'] ?? ''; // 目标
                $params['actuality'] = $this->data_arr['actuality'] ?? ''; // 现状
                $params['progress'] = $this->data_arr['progress'] ?? ''; // 进展
                $params['quicken_factor'] = $this->data_arr['quicken_factor'] ?? ''; // 加快进展因素
                $params['change_point'] = $this->data_arr['change_point'] ?? ''; // 机会点
                $params['need_support'] = $this->data_arr['need_support'] ?? ''; // 需要的支持
                $params['other'] = $this->data_arr['other'] ?? ''; // 其他
                $params['number'] = $this->data_arr['number'] ?? 1; // 其他
                $file_ids           = $this->data_arr['file_ids'] ?? [];  // 附件 数组形式接收,可有多个附件
                $data = $quarter_summary_service->quarter_summary_update($params,$file_ids,$id);
                break;
            case "DELETE":  // 添加纪要
                $ids = $this->check_param('ids'); // 纪要id 数组
                $data = $quarter_summary_service->quarter_summary_del($ids);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'],$data['msg'],$data['data']);
    }

}
