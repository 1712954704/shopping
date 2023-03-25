<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\Common\UserService;
use App\Http\Service\ServiceBase;
use App\Models\Common\Structure;
use App\Models\Common\Role;
use App\Models\Common\RoleAuthRule;
use App\Models\Common\UserRole;
use Illuminate\Support\Facades\DB;
use library\Constants\Model\AuthConstants;
use library\Constants\StatusConstants;

class RoleService extends ServiceBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取角色列表
     * @param array $params
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_role($params,$limit,$offset)
    {
        $name = $params['name'] ?? null;
        $key_word = $params['key_word'] ?? null;
        $department_id = $params['department_id'] ?? null;
        $where = [
            'status' => [1,2]
        ];
        if ($name){
            $where[] = ['name','like','%' .$name .'%'];
        }
        if ($department_id){
            $where['department_id'] = $department_id;
        }
        if ($key_word){
            $where[] = ['name','like','%'.$key_word.'%'];
        }

        $fields = ['id','name','pid','type','code','department_id','status','created_at','order','remark'];
        $result = Role::where($where)->limit($limit)->offset($offset)->select($fields)->get();
        $this->return_data['data']['total'] = Role::where($where)->select($fields)->count();
        if (!$result){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        $this->return_data['data']['data'] = \Common::laravel_to_array($result);
        // 查询所有部门名称
        $department_ids = array_column($this->return_data['data']['data'],'department_id');
        $department_list = Structure::whereIn('id',$department_ids)->select(['id','name'])->get();
        $department_list = array_column(\Common::laravel_to_array($department_list),'name','id');
        // 处理数据
        foreach ($this->return_data['data']['data'] as &$item){
            // 查询角色下的所有用户id
            $role_ids = array_unique(array_column($this->return_data['data']['data'],'id'));
            $user_list = UserRole::whereIn('role_id',$role_ids)->select(['role_id','user_id'])->get();
            $user_ids = array_column(\Common::laravel_to_array($user_list),'user_id');
            $item['role_user_ids'] = $user_ids;
            $item['created_at'] = strtotime($item['created_at']);
            $item['department_name'] = $department_list[$item['department_id']] ?? '';
        }
        return $this->return_data;
    }

    /**
     * 添加角色
     * @param array $params
     * @return mixed
     */
    public function add_role($params)
    {
        try {
            DB::connection('mysql_common')->beginTransaction();
            $result = Role::insert($params);
            if (!$result){
                throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
            }
//            if ($auth){
//                // 添加角色组权限
//                $insert_data = [];
//                foreach ($auth as $item){
//                    $insert_data[] = [
//                        'role_id' => $result->id,
//                        'auth_rule_id' => $item,
//                    ];
//                }
//                $res = RoleAuthRule::insert($insert_data);
//                if (!$res){
//                    throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
//                }
//            }
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            var_dump($e->getLine());
            var_dump($e->getMessage());die();
            $this->return_data['code'] = $e->getCode();
            $this->return_data['msg'] = $e->getMessage();
        }
        return $this->return_data;
    }

    /**
     * 更新角色信息
     * @param int $id
     * @param array $params
     * @return mixed
     */
    public function update_role($id,$params)
    {
        $where = [
            'id' => $id
        ];
        $result = Role::where($where)->update($params);
        if (!$result){
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }

    /**
     * 删除角色信息
     * @param int $id
     * @return mixed
     */
    public function del_role($id)
    {
        try {
            $where_role = [
                'id' => $id
            ];
            $where_role_auth_rule = [
                'role_id' => $id
            ];
            $data = [
                'status' => AuthConstants::COMMON_STATUS_DELETE
            ];
            DB::connection('mysql_common')->beginTransaction();
            // 更新角色表
            Role::where($where_role)->update($data);
            // 更新角色规则关联 status = -1
            RoleAuthRule::where($where_role_auth_rule)->update($data);
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = StatusConstants::ERROR_DATABASE;
        }
        return $this->return_data;
    }


    /**
     * 添加用户角色关联
     * @param array $user_ids
     * @param int $role_id
     * @return mixed
     */
    public function add_user_role($role_id,$user_ids)
    {
        if (is_array($user_ids) && !$user_ids){
            $this->return_data['code'] = StatusConstants::ERROR_ILLEGAL_PARAMS;
            return $this->return_data;
        }

        try {
            DB::connection('mysql_common')->beginTransaction();
            if ($user_ids){
                // 添加角色组权限
                $insert_data = [];
                foreach ($user_ids as $item){
                    $insert_data[] = [
                        'user_id' => $item,
                        'role_id' => $role_id,
                    ];
                }
                $res = UserRole::insert($insert_data);
                if (!$res){
                    throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
                }
            }
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = $e->getCode();
            $this->return_data['msg'] = $e->getMessage();
        } finally {
            $user_service = new UserService();
            $user_service->user_reset($user_ids);
            return $this->return_data;
        }
    }

    /**
     * 添加用户角色关联
     * @param int $role_id
     * @param array $auth_ids
     * @return mixed
     */
    public function add_role_auth($role_id,$auth_ids)
    {
        if (is_array($auth_ids) && !$auth_ids){
            $this->return_data['code'] = StatusConstants::ERROR_ILLEGAL_PARAMS;
            return $this->return_data;
        }

        try {
            DB::connection('mysql_common')->beginTransaction();
            if ($auth_ids){
                // 添加角色组权限
                $insert_data = [];
                foreach ($auth_ids as $item){
                    $insert_data[] = [
                        'auth_rule_id' => $item,
                        'role_id' => $role_id,
                    ];
                }
                $res = RoleAuthRule::insert($insert_data);
                if (!$res){
                    throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
                }
            }
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = $e->getCode();
            $this->return_data['msg'] = $e->getMessage();
        } finally {
            // 重置用户缓存
            $user_service = new UserService();
            $user_service->user_reset($auth_ids);
            return $this->return_data;
        }
    }

    /**
     * 添加用户角色关联
     * @param int $user_id
     * @param array $role
     * @return mixed
     */
    public function update_user_role($user_id,array $role)
    {
        if (is_array($role) && !$role){
            $this->return_data['code'] = StatusConstants::ERROR_ILLEGAL_PARAMS;
            return $this->return_data;
        }
        $where = ['user_id' => $user_id];
        try {
            DB::connection('mysql_common')->beginTransaction();
            // 先删除再添加
            $result = UserRole::where($where)->delete();
            if (!$result){
                throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
            }
            if ($role){
                // 添加角色组权限
                $insert_data = [];
                foreach ($role as $item){
                    $insert_data[] = [
                        'user_id' => $user_id,
                        'role_id' => $item,
                    ];
                }
                $res = UserRole::insert($insert_data);
                if (!$res){
                    throw new \Exception('DATABASE ERROR',StatusConstants::ERROR_DATABASE);
                }
            }
            DB::connection('mysql_common')->commit();
        }catch (\Exception $e){
            DB::connection('mysql_common')->rollBack();
            $this->return_data['code'] = $e->getCode();
            $this->return_data['msg'] = $e->getMessage();
        }
        return $this->return_data;
    }

}
