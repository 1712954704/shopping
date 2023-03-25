<?php
/**
 * User: Jack
 * Date: 2023/03/22
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Hr;

use App\Http\Service\Common\FileService;
use App\Http\Service\Common\UserService;
use App\Models\Hr\ApprovalProcess;
use App\Http\Service\ServiceBase;
use App\Models\Hr\AssessmentsCopy;
use App\Models\Hr\QuarterSummary;
use App\Models\Hr\QuarterSummaryFile;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\ApprovalConstants;
use library\Constants\Model\ModelConstants;
use library\Constants\StatusConstants;

class QuarterSummaryService extends ServiceBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 抄送列表
     * @param array $params
     * @return array
     *
     */
    public function quarter_summary_list($params)
    {
        try {
            $file_service = new FileService();
            $user_id = $params['user_id'];
            $department_id = $params['department_id'];
            $where = [
                'user_id' => $user_id
            ];
            if ($department_id){
                $where = [
                    'department_id' => $department_id
                ];
            }
            $where['status'] = ModelConstants::COMMON_STATUS_NORMAL;
            $result_data =QuarterSummary::where($where)->get();
            $result_data = \Common::laravel_to_array($result_data);
            foreach ($result_data as &$item){
                // 查询附件
                $file_id_list = QuarterSummaryFile::where(['quarter_summary_id'=>$item['id'],'status' => ModelConstants::COMMON_STATUS_NORMAL])->select(['id','quarter_summary_id','file_id'])->get();
                $file_id_list = \Common::laravel_to_array($file_id_list);
                if ($file_id_list){
                    $filed_ids = array_column($file_id_list,'file_id');
                    $data_file = $file_service->get_file_url($filed_ids);
                    $filed_url = $data_file['data']['file_url'] ?? [];
                    $file_original_name = $data_file['data']['file_original_name'] ?? [];
                    $item['file'] = [
                        'filed_ids' => $filed_ids,
                        'file_url' => $filed_url,
                        'file_original_name' => $file_original_name,
                    ];
                }else{
                    $item['filed_ids'] = [];
                }
            }
            $this->return_data['data'] = $result_data;
        }catch (\Exception $e){
            // 记录log
//            var_dump($e->getLine());
//            var_dump($e->getMessage());die();
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 添加纪要
     * @param array $params
     * @param array $file_ids
     * @return array
     *
     */
    public function quarter_summary_add($params,$file_ids)
    {
        try {
            $create_result = QuarterSummary::create($params);
            if ($file_ids){
                // 添加附件
                $insert_data = [];
                foreach ($file_ids as $item){
                    $insert_data[] = [
                        'quarter_summary_id' => $create_result->id,
                        'file_id' => $item,
                    ];
                }
                QuarterSummaryFile::insert($insert_data);
            }
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
            return $this->return_data;
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 添加纪要
     * @param int $params
     * @param int $file_ids
     * @param int $id
     * @return array
     *
     */
    public function quarter_summary_update($params,$file_ids,$id)
    {
        try {
            $up_data_status = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            $where_file_user = [
                'quarter_summary_id' => $id
            ];
            $where = [
                'id' => $id
            ];
            DB::connection('mysql_common')->beginTransaction();
            QuarterSummary::where($where)->update($params);
            if ($file_ids){
                // 添加附件
                $insert_data = [];
                foreach ($file_ids as $item){
                    $insert_data[] = [
                        'quarter_summary_id' => $id,
                        'file_id' => $item,
                    ];
                }
                QuarterSummaryFile::where($where_file_user)->update($up_data_status);
                QuarterSummaryFile::insert($insert_data);
            }
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            return $this->return_data;
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 删除纪要
     * @param array $ids
     * @return array
     *
     */
    public function quarter_summary_del($ids)
    {
        try {
            $data_update = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            QuarterSummary::whereIn('id',$ids)->update($data_update);
            QuarterSummaryFile::whereIn('quarter_summary_id',$ids)->update($data_update);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            return $this->return_data;
        } finally {
            return $this->return_data;
        }
    }

}
