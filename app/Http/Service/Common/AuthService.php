<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\Common\UserService;
use App\Models\Common\AuthRule;
use App\Http\Service\ServiceBase;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\AuthConstants;
use library\Constants\Model\ModelConstants;
use library\Constants\StatusConstants;

class AuthService extends ServiceBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取路由规则
     * @param array $params
     * @return array
     */
    public function get_auth_rule($params)
    {
        try {
            $pid = $params['pid'];
            $system_type = $params['system_type'];
            $key_word = $params['key_word'];
            $is_all = $params['is_all'];
            $where = [
                'status' => [
                    AuthConstants::COMMON_STATUS_NORMAL,
                    AuthConstants::COMMON_STATUS_OUTAGE,
                ]
            ];
            if ($system_type){
                $where['system_type'] = $system_type;
            }
            if ($key_word){
                $where[] = ['title','like','%'.$key_word.'%'];
            }
            $where['pid'] = $pid;
            $fields = ['id','name','title','pid','type','remark','method','code','status','order','icon','system_type'];
            if ($is_all){
                unset($where['pid']);
                $result = AuthRule::where($where)->select($fields)->get();
                $result = \Common::laravel_to_array($result);
                $result = $this->get_tree_auth($result);
            }else{
                $result = AuthRule::where($where)->select($fields)->get();
            }
            $this->return_data['data'] = $result;
        }catch (\Exception $e){
            // todo 记录log
        } finally {
            return $this->return_data;
        }
    }

    /**
     * 整理路由表无限级分类
     * @param array $data
     * @param int $pid
     * @return array
     */
    public function get_tree_auth($data,$pid = 0)
    {
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['pid'] == $pid)
            {
                $arr['id'] = $v['id'];
                $arr['name'] = $v['name'];
                $arr['title'] = $v['title'];
                $arr['pid'] = $v['pid'];
                $arr['type'] = $v['type'];
                $arr['icon'] = $v['icon'];
                $arr['method'] = $v['method'];
                $arr['remark'] = $v['remark'];
                $arr['order'] = $v['order'];
                $arr['status'] = $v['status'];
                $arr['code'] = $v['code'];
                $arr['system_type'] = $v['system_type'];
                $arr['child'] = $this->get_tree_auth($data, $v['id']);
                $tree[] = $arr;
                unset($arr);
                unset($data[$k]);
            }
        }
        return $tree;
    }

    /**
     * 添加路由规则
     * @param array $params
     * @return mixed
    */
    public function add_auth_rule($params)
    {
        try {
            $user_service = new UserService();
            $pid = $params['pid'];
            // 新增节点
            $data = $this->get_node_info($params,$pid);
            DB::connection('mysql_common')->beginTransaction();
            $sql_lft = "UPDATE `common_auth_rule` SET `left` = `left`+2 WHERE way_type = ".$data['parent']['way_type']." and `left`>".$data['parent']['right'];
            $sql_rht = "UPDATE `common_auth_rule` SET `right` = `right` + 2 WHERE way_type = ".$data['parent']['way_type']." and `right`>= ".$data['parent']['right'];
            DB::connection('mysql_common')->update($sql_lft);
            DB::connection('mysql_common')->update($sql_rht);
            $res = AuthRule::insert($data['child']);
            DB::connection('mysql_common')->commit();
            if (!$res){
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
        } finally {
            $user_service->reset_user_all();
            return $this->return_data;
        }
    }

    /**
     * 更新路由规则
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function update_auth_rule($id,$data)
    {
        try {
            $where = [
                'id' => $id
            ];
            $result = AuthRule::where($where)->update($data);
            if (!$result){
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }catch (\Exception $e){
            $code = $e->getCode();
            if (in_array($code,array_keys(StatusConstants::STATUS_TO_CODE_MAPS))){
            $this->return_data['code'] = $code;
            }else{
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        } finally {
            $user_service = new UserService();
            $user_service->reset_user_all();
            return $this->return_data;
        }
    }

    /**
     * 更新路由规则
     * @param int $id
     * @return mixed
     */
    public function del_auth_rule($id)
    {
        try {
            // 获取左右值信息
            $auth_rule_info = AuthRule::find($id);
            $auth_rule_info = \Common::laravel_to_array($auth_rule_info);
            if (!$auth_rule_info){
                $this->return_data['code'] = StatusConstants::ERROR_INVALID_PARAMS;
                throw new \Exception();
            }
            $lft = $auth_rule_info['left'];
            $rgt = $auth_rule_info['right'];
            // 删除子集
            $sql = "DELETE FROM `common_auth_rule` WHERE `left`>=$lft AND `right`<=$rgt and way_type = " . $auth_rule_info['way_type'];
            // 更新左右值
            $Value=$rgt-$lft+1;
            $sql_lft = "UPDATE `common_auth_rule` SET `left`=`left`- $Value WHERE `left`>$lft and way_type = " . $auth_rule_info['way_type'];
            $sql_rht = "UPDATE `common_auth_rule` SET `right`=`right`- $Value WHERE `right`>$rgt and way_type = " . $auth_rule_info['way_type'];
            DB::connection('mysql_common')->beginTransaction();
            DB::connection('mysql_common')->delete($sql);
            DB::connection('mysql_common')->update($sql_lft);
            DB::connection('mysql_common')->update($sql_rht);
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $code = $e->getCode();
            if (in_array($code,array_keys(StatusConstants::STATUS_TO_CODE_MAPS))){
                $this->return_data['code'] = $code;
            }else{
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        } finally {
            $user_service = new UserService();
            $user_service->reset_user_all();
            return $this->return_data;
        }
    }

    /**
     * 新增子节点
     * @param array $params 父节点id
     * @param int $pid 父节点id
     * @return array $data
    */
    public function get_node_info($params,$pid)
    {
        $parent = [];
        if ($pid){
            // 查询父节点信息 获取父节点的左右值
            $parent = AuthRule::find($pid);
            // 新增节点左值 = 父节点右值 新增节点右值 = 新增节点左值 + 1
            $params['left'] = $parent->right;
            $params['right'] = $params['left'] + 1;
            $params['depth'] = $parent->depth + 1;
            $params['way_type'] = $parent->way_type; // 1=系统管理 2=考核管理
            $parent = \Common::laravel_to_array($parent);
        }else{
            $params['left'] = 1;
            $params['right'] = 2;
            $params['depth'] = 1;
//            $params['way_type'] = 1; // 1=系统管理 2=考核管理
        }
        $data = [
            'child' => $params,
            'parent' => $parent,
        ];
        return $data;
    }

    /**
     * 批量更新数据库菜单规则信息
    */
    public function batch_update_auth ()
    {
        try {
            // 获取全部信息
//            $all_data = AuthRule::where(['status' => ModelConstants::COMMON_STATUS_NORMAL,'way_type' => 2])->get();
            $all_data = AuthRule::where(['status' => ModelConstants::COMMON_STATUS_NORMAL,'way_type' => 3])->get();
            $all_data = \Common::laravel_to_array($all_data);
//            DB::connection('mysql_hr')->beginTransaction();
            foreach ($all_data as $item){
                if ($item['type'] !=5){
                    if ($item['pid']){
                        $data = $this->get_node_info($item,$item['pid']);
                        // 更新其余节点
                        $sql_lft = "UPDATE `common_auth_rule` SET `left` = `left`+2 WHERE way_type = 3 and `left`>".$data['parent']['right'];
                        $sql_rht = "UPDATE `common_auth_rule` SET `right` = `right` + 2 WHERE way_type = 3 and `right`>= ".$data['parent']['right'];
                        DB::connection('mysql_common')->update($sql_lft);
                        DB::connection('mysql_common')->update($sql_rht);
                        AuthRule::where(['id'=>$item['id']])->update($data['child']);
//                        var_dump($data);die();
                    }
                }
            }
//            DB::connection('mysql_hr')->commit();
        }catch (\Exception $e){
//            DB::connection('mysql_hr')->rollBack();
        }finally{
        }
    }

    /**
     * 更新路由规则
     * @param int $id
     * @return mixed
     */
    public function get_route_way_info($ids)
    {
        try {
            $arr = [];
            foreach ($ids as $id){
                // 查询自身信息
                $self_result = AuthRule::find($id);
                $self_result = \Common::laravel_to_array($self_result);
                //获取单一路径父级及自身结构
                $sql = "select * from common_auth_rule a where a.left > ".$self_result['left']." and a.left < ".$self_result['right']." and way_type = ".$self_result['way_type'];
                $child = DB::connection('mysql_common')->select($sql);
                $child = array_map('get_object_vars', $child);
                // 获取单一路径子集及自身结构
//            $sql = "select
//    parent.id,
//    parent.name,
//    parent.title,
//    parent.pid,
//    parent.left,
//    parent.right,
//    parent.depth,
//    parent.way_type
//from
//    hr_auth_rule as node, hr_auth_rule as parent
//where
//    node.left between parent.left and parent.right and node.id = $id and parent.way_type = 2
//    order by parent.left";

                $sql = "select
    parent.*
from
    common_auth_rule as node, common_auth_rule as parent
where
    node.left between parent.left and parent.right and node.id = $id and parent.way_type = ".$self_result['way_type']."
    order by parent.left";
                $parent = DB::connection('mysql_common')->select($sql);
                $parent = array_map('get_object_vars', $parent);
                // 合并数组
//                $arr = array_merge($parent,$child);
                $arr = array_merge($arr,array_merge($parent,$child));
            }

            $tem = [];
            foreach ($arr as $key => $item){
                if (in_array($item['id'],$tem)){
                    unset($arr[$key]);
                }else{
                    array_push($tem,$item['id']);
                }
            }
            $data = $this->getTree($arr);
            $this->return_data['data'] = $data;
        }catch (\Exception $e){
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
            $code = $e->getCode();
            if (in_array($code,array_keys(StatusConstants::STATUS_TO_CODE_MAPS))){
                $this->return_data['code'] = $code;
            }else{
                $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
            }
        }
        return $this->return_data;
    }

    public function getTree($data,$pid = 0)
    {
        $tree = [];
        foreach($data as $k => $v)
        {
            if($v['pid'] == $pid)
            {
                $arr = $v;
                $arr['child'] = $this->getTree($data, $v['id']);
                $tree[] = $arr;
                unset($arr);
                unset($data[$k]);
            }
        }
        return $tree;
    }

}
