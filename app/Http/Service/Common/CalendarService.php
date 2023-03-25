<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\ServiceBase;
use App\Models\Common\Calendar;
use App\Models\Common\Notice;
use App\Models\Common\NoticeFile;
use App\Models\Common\NoticeUser;
use App\Models\Common\Position;
use App\Models\Common\Structure;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\ModelConstants;
use library\Constants\Model\NoticeConstants;
use library\Constants\StatusConstants;

class CalendarService extends ServiceBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取公告列表
     * @param array $params
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_calendar_list($params,$limit,$offset)
    {
        try {
            $title = $params['title'] ?? null;
            $user_id = $params['user_id'] ?? null;
            $where = [
                'status' => ModelConstants::COMMON_STATUS_NORMAL
            ];
            if ($title){
                $where[] = ['title','like','%' .$title .'%'];
            }
            if ($user_id){
                $where['user_id'] = $user_id;
            }
            $fields = ['id','user_id','title','content','address_remark','colour','calendar_time','start_time','end_time','system_type','status','created_at',];
            $result = Calendar::where($where)->limit($limit)->offset($offset)->select($fields)->get();
            $this->return_data['data']['total'] = Calendar::where($where)->select($fields)->count();
            $this->return_data['data']['data'] = \Common::laravel_to_array($result);
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
//            var_dump($this->return_data);die();
            return $this->return_data;
        }
    }

    /**
     * 添加日程安排
     * @param array $params
     * @return mixed
     */
    public function add_calendar($params)
    {
        try {
            DB::connection('mysql_common')->beginTransaction();
            $create_result = Calendar::insert($params);
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }


    /**
     * 更新职务信息
     * @param int $id
     * @param array $params
     * @return mixed
     */
    public function update_calendar($id,$params)
    {
        $where = [
            'id' => $id
        ];
        $result = Calendar::where($where)->update($params);
        if (!$result){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

    /**
     * 删除职务信息
     * @param int $id
     * @return mixed
     */
    public function del_calendar($id)
    {
        try {
            $where = [
                'id' => $id
            ];
            $data = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            // 更新日程表表 status = -1
            Calendar::where($where)->update($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

}
