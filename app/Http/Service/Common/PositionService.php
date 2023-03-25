<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\ServiceBase;
use App\Models\Common\Position;
use App\Models\Common\Structure;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\ModelConstants;
use library\Constants\StatusConstants;

class PositionService extends ServiceBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取职位列表
     * @param array $params
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_position_list($params,$limit,$offset)
    {
        $key_word = $params['key_word'];
        $structure_id = $params['structure_id'];
        $where = [
            'status' => [1,2]
        ];
        if ($key_word){
            $where[] = ['name','like','%' .$key_word .'%'];
        }
        if ($structure_id){
            $where['structure_id'] = $structure_id;
        }

        $fields = ['id','name','code','structure_id','status','created_at','order'];
        $result = Position::where($where)->limit($limit)->offset($offset)->select($fields)->get();
        $this->return_data['data']['total'] = Position::where($where)->select($fields)->count();
        if (!$result){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        $this->return_data['data']['data'] = \Common::laravel_to_array($result);
        // 查询所有部门名称
        $department_ids = array_column($this->return_data['data']['data'],'structure_id');
        $department_list = Structure::whereIn('id',$department_ids)->select(['id','name'])->get();
        $department_list = array_column(\Common::laravel_to_array($department_list),'name','id');
        foreach ($this->return_data['data']['data'] as &$item){
            $item['created_at'] = strtotime($item['created_at']);
            $item['structure_name'] = $department_list[$item['structure_id']] ?? '';
        }
        return $this->return_data;
    }

    /**
     * 添加职务
     * @param array $params
     * @return mixed
     */
    public function add_position($params)
    {
        try {
            DB::connection('mysql_hr')->beginTransaction();
            $result = Position::insert($params);
            if (!$result){
                throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
            }
            DB::connection('mysql_hr')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_hr')->rollBack();
            $this->return_data['code'] = $e->getCode();
            $this->return_data['msg'] = $e->getMessage();
        }
        return $this->return_data;
    }


    /**
     * 更新职务信息
     * @param int $id
     * @param array $params
     * @return mixed
     */
    public function update_position($id,$params)
    {
        $where = [
            'id' => $id
        ];
        $result = Position::where($where)->update($params);
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
    public function del_position($id)
    {
        try {
            $where = [
                'id' => $id
            ];
            $data = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            // 更新职务表 status = -1
            Position::where($where)->update($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

}
