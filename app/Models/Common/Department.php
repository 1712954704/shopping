<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory;
    use HasFactory;
    use SoftDeletes;
    protected $connection = 'mysql_common';
    protected $guarded = [];

    protected $casts = [
        'updated_at'  => 'date:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
    ];

    protected $fillable = ['leader', 'name', 'group_type', 'pid', 'number', 'code', 'type', 'area_id', 'faxes', 'address', 'order', 'remark', 'short_name', 'phone', 'home_page', 'manager_id', 'handler_id', 'duty_id', 'status',];


    // 查询列表
    public static function index($columns,$limit,$offset,$where){

        // offset 设置从哪里开始
        // limit 设置想要查询多少条数据
        $result['data'] = Structure::select($columns)
            ->where($where)
            ->orderBy('id', 'desc')
            ->with(['user:account,id'])
            ->limit($limit)
            ->offset($offset)
            ->get();
        $result['total'] =  Structure::where($where)->count();
        return $result;
    }

    /**
     * 获取与用户相关的电话记录
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id','pid');
    }
    /**
     * 获取与用户相关的名称
     */
    public function leader()
    {
        return $this->hasOne(User::class, 'id','leader');
    }
    /**
     * 获取与用户相关的电话记录
     */
    public function pid()
    {
        return $this->hasOne(Department::class, 'id','pid');
    }

    public function child()
    {
        return $this->hasMany(self::class,'pid');
    }

    // 递归子级
    public function children()
    {
        return $this->child()->with('children')->where(GROUP_TYPE);
    }

    public function father()
    {
        return $this->hasMany(self::class,'id','pid');
    }

    // 递归父级
    public function parents()
    {
        return $this->father()->with('parents');
    }


}
