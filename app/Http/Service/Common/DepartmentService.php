<?php


namespace App\Http\Service\Common;

use App\Http\Service\Hr\StructureService;
use App\Http\Service\ServiceBase;
use App\Models\Common\Region;
use App\Models\Common\Structure;
use Common;
use Illuminate\Support\Facades\DB;

class DepartmentService extends ServiceBase
{

    static private $tree = [];

    /**
     * DepartmentService constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function index($interface, $pid, $id, $group_type, $limit, $offset, $data)
    {


        $group_type_child = [];
        if ($group_type) {
            $group_type_child['group_type'] = $group_type;
            $where['group_type']            = $group_type;
        }

        define('GROUP_TYPE', $group_type_child); // 定义当前系统类型


        $where['pid'] = $pid;

        if ($id) {
            $where['id'] = $id;
        }


        if ($interface == 1) {


            // var_dump($this->arr_list($arr));
            //
            // exit();

            // $keyword = "部" . "%";
            //
            // $where[] = ['name', 'like', $keyword];


            $result['data'] = Structure::where($where)
                ->with(['leader_with'])
                ->with(['users_with_dept'])
                ->with(['users_with'])
                ->with(['children'])
                ->orderBy('order', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();

            // 子部门和员工合并在一个数组里children


        } else {
            $result['data'] = Structure::where($where)
                ->with(['children'])
                // ->where('name', 'like', '百%')
                ->with(['leader_with'])
                ->orderBy('order', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->get();
        }


        $result['total'] = Structure::where($where)->count();


        $response['code']  = count($result) > 0 ? '200' : '404';
        $response['msg']   = count($result) > 0 ? 'success' : '数据不存在';
        $response['data']  = $result;
        $result            = Common::laravel_to_array($response['data']['data']);
        $area_ids          = array_column($result, 'area_id');
        $region            = Common::laravel_to_array(Region::whereIn('id', $area_ids)->get());
        $region_arr        = array_column($region, 'title', 'id');
        $region_way_arr    = [];
        $structure_service = new StructureService();
        // 查询地区id单一路径
        foreach ($area_ids as &$v) {
            $region_way_arr[$v] = $structure_service->get_region_way($v);
        }
        foreach ($response['data']['data'] as &$item) {
            if ($item['area_id'] && isset($region_arr[$item['area_id']])) {
//                $item['area_name'] = $region_arr[$item['area_id']];
//                $item['area_id']   = $region_way_arr[$item['area_id']];
                $item['area_id'] = $region_arr[$item['area_id']];
            }
        }

        $this->return_data['data']['data'] = $result;
        return $this->return_data;
    }

    public function arr_list($arr, $newArr = [])
    {


        foreach ($arr as $v) {
            // $newArr  = array_merge($newArr,$v);
            // // $newArr['a12006'] = "126";
            // if (is_array($v)) {
            //     $has_users_with      = array_key_exists('users_with', $v);
            //     $has_users_with_user = array_key_exists('users_with_user', $v);
            //
            //     // var_dump($has_users_with);
            //     // var_dump($v['users_with']);
            //
            //     if ($has_users_with and $has_users_with_user) {
            //         // var_dump($v['users_with']);
            //         // var_dump($v['users_with_user']);
            //         $newArr['a110'] = $v['users_with'] + $v['users_with_users'];
            //         $newArr['a120'] = "126";
            //
            //
            //     }
            //
            //     $this->arr_list($v);
            // }


            var_dump($v);
            $arr = [];
            if (is_array($v)) {
                if ((isset($v['users_with']) && isset($v['users_with_users'])) && (is_array($v['users_with']) && is_array($v['users_with_users']))) {
                    $arr = [$v['users_with'], $v['users_with_users']];
                    // $arr = $v['users_with'] + $v['users_with_users'];
                }
            }
            $newArr = $arr;

            $this->arr_list($v, $newArr);
            // var_dump($arr);die();


        }


        return $newArr;

    }

    /**
     * 新增
     * @param array $data
     * @return mixed
     */
    public function store($data)
    {
        //增加
        $result                    = Structure::create($data['data']);
        $this->return_data['data'] = $result;
        return $this->return_data;
    }

    /**
     * 编辑
     * @param array $data
     * @return mixed
     */
    public function renewal($data)
    {

        $result = DB::connection('mysql_common')->table('structure')
            ->where('id', $data['data']['id'])
            ->update($data['data']);


        $this->return_data['data'] = $result;
        return $this->return_data;
    }

    public function statistics($data)
    {

        $result['data'] = Structure::where([])
            ->with(['children'])
            ->with(['leader:account,id'])
            ->orderBy('order', 'desc')
            ->limit(5)
            ->offset(0)
            ->get();

        $this->return_data['data'] = $result;
        return $this->return_data;
    }


    /**
     * 获取组织-部门结构
     * @param array $params
     * @return array
    */
    public function get_structure_list($params)
    {
        try {
            $name = $params['name'] ?? '';
            $where = "1";
            if ($name){
                $where .= " and a.name like '%" . $name ."%'";
            }
            $sql = "select a.id,a.name,a.pid from common_structure a where ". $where;
            $result = DB::connection('mysql_common')->select($sql);
            $result = array_map('get_object_vars', $result);

            $tree_new = [];
            foreach ($result as $key => $item){
                if ($item['pid']){
                    $parent_data = $this->get_parent_data($item['pid']);
                }else{
                    $parent_data = [];
                }
                array_push($parent_data,['id' => $item['id'],'name' => $item['name'],'pid' => $item['pid']]);
                $tree_new = array_merge($tree_new,$parent_data);
            }

            $tem = [];
            foreach ($tree_new as $key => $val){
                if (in_array($val['id'],$tem)){
                    unset($tree_new[$key]);
                }else{
                    array_push($tem,$val['id']);
                }
            }
            $data = $this->getTree($tree_new,0);
            $this->return_data['data']['data'] = $data;
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());
        } finally {
            return $this->return_data;
        }
    }


    /**
     * 获取父级及自身数据
     * @param int $id
     * @return array
    */
    public function get_parent_data($id)
    {
//        static $tree = [];
        $tree = [];
        $result = Structure::select(['id','name','pid'])->find($id);
        $result = Common::laravel_to_array($result);
        $tree[] = $result;
        if ($result['pid']){
            $arr = $this->get_parent_data($result['pid'])[0];
            $tree[] = $arr;
            unset($arr);
        }
        return $tree;
    }

    /**
     * 获取树形结构
    */
    public function getTree($data,$pid = 0)
    {
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['pid'] == $pid)
            {
                $arr = $v;
                $arr['label'] = $v['name'];
                $arr['children'] = $this->getTree($data, $v['id']);
                $tree[] = $arr;
                unset($arr);
                unset($data[$k]);
            }
        }
        return $tree;
    }


}
