<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\ServiceBase;
use App\Models\Common\Calendar;
use App\Models\Common\DictionaryData;
use App\Models\Common\DictionaryType;
use App\Models\Common\Position;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\ModelConstants;
use library\Constants\StatusConstants;
use function PHPUnit\Framework\assertDirectoryDoesNotExist;

class DictionaryService extends ServiceBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取字典类别列表
     * @param array $params
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_dictionary_list($params,$limit,$offset)
    {
        try {
            $title = $params['title'] ?? null;
            $user_id = $params['user_id'] ?? null;
            $system_type = $params['system_type'] ?? null;
            $where = [
                'status' => ModelConstants::COMMON_STATUS_NORMAL
            ];
            if ($title){
                $where[] = ['full_name','like','%' .$title .'%'];
            }
            if ($user_id){
                $where['user_id'] = $user_id;
            }
            if ($system_type){
                $where['system_type'] = $system_type;
            }
            $fields = ['id','parent_id','full_name','encode','status','system_type','user_id','created_at'];
            $result = DictionaryType::where($where)->limit($limit)->offset($offset)->select($fields)->get();
            $this->return_data['data']['total'] = DictionaryType::where($where)->select($fields)->count();
//            $this->return_data['data']['data'] = \Common::laravel_to_array($result);
            $result = \Common::laravel_to_array($result);
            $this->return_data['data']['data'] = $this->getTree($result);
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 添加数据字典类别
     * @param array $params
     * @return mixed
     */
    public function add_dictionary($params)
    {
        try {
            DB::connection('mysql_common')->beginTransaction();
            DictionaryType::insert($params);
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }


    /**
     * 更新字典类别信息
     * @param int $id
     * @param array $params
     * @return mixed
     */
    public function update_dictionary($id,$params)
    {
        $where = [
            'id' => $id
        ];
        $params = $this->del_array_null($params);
        $result = DictionaryType::where($where)->update($params);
        if (!$result){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

    /**
     * 删除字典类别
     * @param int $id
     * @return mixed
     */
    public function del_dictionary($id)
    {
        try {
            $where = [
                'id' => $id
            ];
            $data = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            // 更新职务表 status = -1
            DictionaryType::where($where)->update($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }


    /**
     * 获取字典类别值列表
     * @param array $params
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_dictionary_value_list($params,$limit,$offset)
    {
        try {
            $title = $params['title'] ?? null;
            $user_id = $params['user_id'] ?? null;
            $type_id = $params['type_id'] ?? null;
            $system_type = $params['system_type'] ?? null;
            $where = [
                'status' => ModelConstants::COMMON_STATUS_NORMAL
            ];
            if ($title){
                $where[] = ['full_name','like','%' .$title .'%'];
            }
            if ($user_id){
                $where['user_id'] = $user_id;
            }
            if ($system_type){
                $where['system_type'] = $system_type;
            }
            if ($type_id){  // 类别主键
                $where['dictionary_type_id'] = $type_id;
            }
            $fields = ['id','parent_id','full_name','encode','status','system_type','user_id','created_at'];
            $result = DictionaryData::where($where)->limit($limit)->offset($offset)->select($fields)->get();
            $this->return_data['data']['total'] = DictionaryData::where($where)->select($fields)->count();
            $result = \Common::laravel_to_array($result);
            $this->return_data['data']['data'] = $this->getTree($result);
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
            return $this->return_data;
        }
    }


    /**
     * 添加数据字典类别
     * @param array $params
     * @return mixed
     */
    public function add_dictionary_value($params)
    {
        try {
            DB::connection('mysql_common')->beginTransaction();
            DictionaryData::insert($params);
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
     * 更新字典类别信息
     * @param int $id
     * @param array $params
     * @return mixed
     */
    public function update_dictionary_value($id,$params)
    {
        $where = [
            'id' => $id
        ];
        $params = $this->del_array_null($params);
        $result = DictionaryData::where($where)->update($params);
        if (!$result){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

    /**
     * 删除字典类别
     * @param int $id
     * @return mixed
     */
    public function del_dictionary_value($id)
    {
        try {
            $where = [
                'id' => $id
            ];
            $data = [
                'status' => ModelConstants::COMMON_STATUS_DELETE
            ];
            // 更新职务表 status = -1
            DictionaryData::where($where)->update($data);
        }catch (\Exception $e){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }


    /**
     * 获取树形结构
     */
    public function getTree($data,$pid = 0)
    {
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['parent_id'] == $pid)
            {
                $arr = $v;
                $arr['children'] = $this->getTree($data, $v['id']);
                $tree[] = $arr;
                unset($arr);
                unset($data[$k]);
            }
        }
        return $tree;
    }

}
