<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */

namespace App\Http\Service\Hr;

use App\Http\Service\ServiceBase;
use App\Models\Common\Region;
use App\Models\Common\Structure;
use library\Constants\Model\ModelConstants;
use library\Constants\Model\StructureConstants;
use library\Constants\StatusConstants;

class StructureService extends ServiceBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取组织列表
     * @param array $params [
     *      name => xxx   // 待搜索的值
     * ]
     * @param int $id
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function get_list($params, $id = null, $offset = 0, $limit = 10)
    {
        $name            = $params['name'] ?? '';
        $where           = [];
        $where['status'] = ModelConstants::COMMON_STATUS_NORMAL;
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($id) {
            $where['id'] = $id;
        }

        try {
            $need_fields                        = ['id', 'name', 'number', 'code', 'type', 'area_id', 'build_time', 'faxes', 'address', 'order', 'remark', 'short_name', 'phone', 'home_page', 'pid'];
            $result                             = Structure::where($where)->offset($offset)->limit($limit)->select($need_fields)->get();
            $this->return_data['data']['count'] = Structure::where($where)->count();
            if (!$result) {
                throw new \Exception('', StatusConstants::ERROR_DATABASE);
            }
            $this->return_data['data']['list'] = \Common::laravel_to_array($result);
            $area_ids                          = array_column($this->return_data['data']['list'], 'area_id');
            $region                            = \Common::laravel_to_array(Region::whereIn('id', $area_ids)->get());
            $region_arr                        = array_column($region, 'title', 'id');
            $region_way_arr                    = [];
            // 查询地区id单一路径
            foreach ($area_ids as &$v) {
                $region_way_arr[$v] = $this->get_region_way($v);
            }
            foreach ($this->return_data['data']['list'] as &$item) {
                $item['area_name'] = $region_arr[$item['area_id']];
                $item['area_id']   = $region_way_arr[$item['area_id']];
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * 获取地区id单一路径信息
     * @param array | int $id 主键id
     * @param int $pid
     * @return array
     */
    public function get_region_way($id, $pid = 1)
    {
        if (!$id) {
            return [];
        }
        static $way_arr = [];
        $res = Region::select(['id', 'pid'])->find($id);
        if ($res->pid != $pid) {
            $this->get_region_way($res->pid);
        }
        $way_arr[] = $res->id;
        return $way_arr;
    }


    /**
     * 更新组织状态
     * @param array $ids 主键id
     * @param int $status 默认为 -1=已删除
     * @return array
     */
    public function change_status($ids, $status = ModelConstants::COMMON_STATUS_DELETE)
    {
        try {
            if (!is_array($ids)) {
                throw new \Exception('', StatusConstants::ERROR_INVALID_PARAMS);
            }
            $data      = ['status' => $status];
            $res       = Structure::whereIn('id', $ids)->get();
            $child_arr = $normal_arr = $error_arr = [];
            // 获取状态正常的删除 status=1
            foreach ($res as $item) {
                if ($item->status == ModelConstants::COMMON_STATUS_NORMAL) {
                    $normal_arr[] = $item->id;
                } else {
                    $error_arr[] = $item->id;
                }
            }
            // 检测是否有子集
            foreach ($normal_arr as $key => $value) {
                $get_result = Structure::where(['pid' => $value])->get();
                $get_result = \Common::laravel_to_array($get_result);
                if ($get_result) {
                    $child_arr[] = $value;
                    unset($normal_arr[$key]);
                }
            }
            if ($normal_arr) {
                $result = Structure::whereIn('id', $normal_arr)->update($data);
                if (!$result) {
                    throw new \Exception('', StatusConstants::ERROR_DATABASE);
                }
            }
            if ($error_arr && $child_arr) {
                $this->return_data['data']['error_arr'] = array_merge($error_arr, $child_arr);
                $this->return_data['code']              = StatusConstants::ERROR_DATA_CONFLICT;
            } elseif ($child_arr) {
                $this->return_data['data']['error_arr'] = $child_arr;
                $this->return_data['code']              = StatusConstants::ERROR_DATA_CONFLICT_CHILD_EXIST;
            } elseif ($error_arr) {
                $this->return_data['data']['error_arr'] = $error_arr;
                $this->return_data['code']              = StatusConstants::ERROR_DATABASE_REPEAT_DELETE;
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }finally{
            return $this->return_data;
        }
    }

    /**
     * 添加组织
     * @param array $params
     * @return array
     */
    public function add_structure($params)
    {
        try {
            $result = Structure::create($params);
            if (!$result) {
                throw new \Exception('', StatusConstants::ERROR_DATABASE);
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
               $this->return_data['msg'] = $e->getMessage();
            }
        }
        return $this->return_data;
    }

    /**
     * 更新组织列信息
     * @param int $id 主键id
     * @param array $params
     * @return array
     */
    public function update_structure($id, $params)
    {
        try {
            $where['id'] = $id;
            $result      = Structure::where($where)->update($params);
            if (!$result) {
                throw new \Exception('', StatusConstants::ERROR_DATABASE);
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    /**
     * 获取地区信息
     * @param int $id 主键id
     * @return array
     */
    public function get_region($id)
    {
        try {
            $data        = [];
            $where       = [
                'pid'    => $id,
                'status' => ModelConstants::COMMON_STATUS_NORMAL,
            ];
            $need_fields = ['id', 'title', 'level', 'pid'];
            $data        = Region::where($where)->select($need_fields)->get();
            if (!$data) {
                throw new \Exception('', StatusConstants::ERROR_DATABASE);
            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        $this->return_data['data'] = \Common::laravel_to_array($data);
        return $this->return_data;
    }

    /**
     * 获取地区信息
     * @param int $id 主键id
     * @param int $group_type 组织部门类型
     * @return array
     */
    public function get_group_list($id = 0, $group_type)
    {
        try {
            $data        = [];
            $where       = [
                'group_type' => $group_type,
            ];
            $need_fields = ['id', 'name', 'short_name'];
            if (!empty($id)) {
                $where ['pid'] = $id;
            } else {
                $where ['depth'] = 1;
            }
            $data = Structure::where($where)->select($need_fields)->get();
            if (!$data) {
                throw new \Exception('', StatusConstants::ERROR_DATABASE);

            }
        } catch (\Exception $e) {
            $code = $e->getCode();
            if (in_array($code, array_keys(StatusConstants::STATUS_TO_CODE_MAPS))) {
                $this->return_data['code'] = $code;
            } else {
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        $this->return_data['data'] = \Common::laravel_to_array($data);
        return $this->return_data;
    }


    /**
     * 获取组织部门树形结构
     * @param int $id
     * @param array $params
     * @return array
     */
    public function get_tree_list($id = 0, $params)
    {
        $group_type = $params['group_type'];
        $where = [
            'pid'    => $id,
            'status' => ModelConstants::COMMON_STATUS_NORMAL,
        ];
        if ($group_type) {
            $where['group_type'] = $group_type;
        }
        $fields                            = ['id', 'name', 'type', 'pid', 'group_type'];
        $list                              = Structure::with('child')->where($where)->select($fields)->get();
        $this->return_data['data']['data'] = \Common::laravel_to_array($list);
        return $this->return_data;
    }


    /**
     * 获取部门上级组织信息
     * @param int $department_id 部门id
     * @return array
    */
    public function get_up_level_structure($department_id)
    {
        $arr = [];
        $structure = Structure::find($department_id);
        if ($structure->group_type == StructureConstants::GROUP_TYPE_STRUCTURE){
            $arr = \Common::laravel_to_array($structure);
        }else{
            $arr = $this->get_up_level_structure($structure->pid);
        }
        return $arr;
    }


}
