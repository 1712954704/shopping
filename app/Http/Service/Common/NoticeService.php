<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\ServiceBase;
use App\Models\Common\Notice;
use App\Models\Common\NoticeFile;
use App\Models\Common\NoticeUser;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\ModelConstants;
use library\Constants\Model\NoticeConstants;
use library\Constants\StatusConstants;

class NoticeService extends ServiceBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取公告列表
     * @param array $params
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_notice_list($params,$user_id,$limit,$offset)
    {
        try {
            $user_service = new UserService();
            $file_service = new FileService();
            $type = $params['type'] ?? null;
            $title = $params['title'];
            $id = $params['id'];
            $time = time();
            $where = "1 and a.status in (". NoticeConstants::COMMON_STATUS_PUSH . "," . NoticeConstants::COMMON_STATUS_WAIT.")";
            if ($id){
                $where .= " and a.id = " . $id;
            }
            if ($title){
                $where .= " and a.title like %" . $title . "% ";
            }
            if ($type == NoticeConstants::COMMON_TYPE_MESSAGE){ // 通知类型 1=通知 2=公告
                $where .= " and a.type = ". NoticeConstants::COMMON_TYPE_MESSAGE ." and a.start_time <= " . $time . " and a.end_time >= " . $time;
            }else if ($type == NoticeConstants::COMMON_TYPE_AFFICHE){
                $where .= " and a.type = ". NoticeConstants::COMMON_TYPE_AFFICHE;
            }else{
                $where .= " and (a.type = " . NoticeConstants::COMMON_TYPE_AFFICHE . " or ( a.type = " . NoticeConstants::COMMON_TYPE_MESSAGE . " and b.user_id = " . $user_id . " and a.start_time <= " .$time . " and a.end_time >= " . $time . "))";
            }

            $limit = "LIMIT $limit OFFSET $offset";
            $sql = "SELECT a.id,a.title,a.content,a.level,a.type,a.system_type,a.status,a.created_at,a.start_time,a.end_time FROM common_notice a
                left join common_notice_user b on a.id = b.notice_id
                WHERE $where group by a.id ORDER BY a.level DESC,a.created_at DESC " . $limit;
            $sql_count = "SELECT count(distinct a.id) total FROM common_notice a
                left join common_notice_user b on a.id = b.notice_id
                WHERE $where ORDER BY a.level DESC,a.created_at DESC ";
            $result = DB::connection('mysql_common')->select($sql);
            $result_total = DB::connection('mysql_common')->select($sql_count);
            $result = array_map('get_object_vars', $result);
            $result_total = array_map('get_object_vars', $result_total);
            $this->return_data['data']['total'] = $result_total[0]['total'];
            $this->return_data['data']['data'] = $result;
            foreach ($this->return_data['data']['data'] as &$item){
                // 查询附件
                $notice_file_id_list = NoticeFile::where(['notice_id'=>$item['id'],'status' => ModelConstants::COMMON_STATUS_NORMAL])->select(['id','notice_id','file_id'])->get();
                $notice_file_id_list = \Common::laravel_to_array($notice_file_id_list);
                if ($notice_file_id_list){
                    $filed_ids = array_column($notice_file_id_list,'file_id');
                    $data_file = $file_service->get_file_url($filed_ids);
                    $filed_url = $data_file['data']['file_url'] ?? [];
                    $file_original_name = $data_file['data']['file_original_name'] ?? [];
                    $item['file'] = [
                        'file_ids' => $filed_ids,
                        'file_url' => $filed_url,
                        'file_original_name' => $file_original_name,
                    ];
                }else{
                    $item['file'] = [];
                }
                // 查询发送用户
                $notice_user_id_list = NoticeUser::where(['notice_id'=>$item['id'],'status' => ModelConstants::COMMON_STATUS_NORMAL])->select(['id','notice_id','user_id'])->get();
                $notice_user_id_list = \Common::laravel_to_array($notice_user_id_list);
                if ($notice_user_id_list){
//                    $item['user_ids'] = array_column($notice_file_id_list,'user_id');
                    $user_ids = array_column($notice_user_id_list,'user_id');
                    $user_infos = [];
                    // 获取用户信息
                    foreach ($user_ids as $val){
                        $user_infos[] = $user_service->get_user_info_by_id($val)['data'] ?? [];
                    }
                    $item['user_ids'] = $user_infos;
                    if ($item['user_ids']){
                        $user_ids = implode(',',array_column($item['user_ids'],'id'));
                        // 查询用户部门
                        $sql = "select a.department_id from common_user a where a.id in ($user_ids)";
                        $user_department_result = DB::connection('mysql_common')->select($sql);
                        $user_department_result = array_map('get_object_vars', $user_department_result);
                        $item['department_ids'] = array_column($user_department_result,'department_id');
                    }
                }else{
                    $item['user_ids'] = [];
                    $item['department_ids'] = [];
                }
            }
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
//            var_dump($this->return_data);die();
            return $this->return_data;
        }
    }

    /**
     * 添加系统公告
     * @param array $params
     * @param array $file_ids 附件
     * @param array $user_ids 通知用户
     * @return mixed
     */
    public function add_notice($params,$file_ids = [],$user_ids = [])
    {
        try {
            DB::connection('mysql_common')->beginTransaction();
//            $create_result = Notice::insert($params);
            $create_result = Notice::create($params);
            if ($file_ids){
                // 添加附件
                $insert_data = [];
                foreach ($file_ids as $item){
                    $insert_data[] = [
                        'notice_id' => $create_result->id,
                        'file_id' => $item,
                    ];
                }
                NoticeFile::insert($insert_data);
            }
            if ($user_ids){
                // 添加附件
                $insert_data = [];
                foreach ($user_ids as $item){
                    $insert_data[] = [
                        'notice_id' => $create_result->id,
                        'user_id' => $item,
                    ];
                }
                NoticeUser::insert($insert_data);
            }

            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }


    /**
     * 更新系统公告信息
     * @param int $id
     * @param array $params
     * @param array $file_ids
     * @param array $user_ids
     * @return mixed
     */
    public function update_notice($id,$params,$file_ids,$user_ids)
    {
        try {
            $where = [
                'id' => $id
            ];
            $up_data_status = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            $where_file_user = [
                'notice_id' => $id
            ];
            DB::connection('mysql_common')->beginTransaction();
            Notice::where($where)->update($params);
            if ($file_ids){
                // 添加附件
                $insert_data = [];
                foreach ($file_ids as $item){
                    $insert_data[] = [
                        'notice_id' => $id,
                        'file_id' => $item,
                    ];
                }
                NoticeFile::where($where_file_user)->update($up_data_status);
                NoticeFile::insert($insert_data);
            }
            if ($user_ids){
                // 添加附件
                $insert_data = [];
                foreach ($user_ids as $item){
                    $insert_data[] = [
                        'notice_id' => $id,
                        'user_id' => $item,
                    ];
                }
                NoticeUser::where($where_file_user)->update($up_data_status);
                NoticeUser::insert($insert_data);
            }
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;

    }

    /**
     * 删除公告信息
     * @param int $id
     * @return mixed
     */
    public function del_notice($id)
    {
        try {
            $where = [
                'id' => $id
            ];
            $data = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            // 更新公告表 status = -1
            Notice::where($where)->update($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

}
