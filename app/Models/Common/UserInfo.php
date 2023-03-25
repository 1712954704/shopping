<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Models\Common;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserInfo extends Authenticatable
{
//    use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 设置当前模型使用的数据库连接名。
     *
     * @var string
     */
    protected $connection = 'mysql_common';

    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'user_info';

    /**
     * 与数据表关联的主键.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 指示模型是否主动维护时间戳。
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值的属性。
     *
     * @var array
     */
    protected $fillable = ['user_id','nation_id','native_place','entry_date','become_data','id_number','birth_date',
        'education','address','emergency_contact_name','emergency_contact_relation','emergency_contact_phone','emergency_contact_address',
        'remark'
    ];

    /**
     * 默认的查询字段
    */
    protected $default_select_fields = ['nation_id','native_place','entry_date','become_data','id_number','birth_date',
        'education','address','emergency_contact_name','emergency_contact_relation','emergency_contact_phone','emergency_contact_address',
        'remark'
    ];

    /**
     * 获取用户信息的查询
     * @date 2023/02/27
     * @param $user_id
     * @param string $field
     * @return bool|mixed
     */
    public function get_user_info($user_id,$fields = '*')
    {
        if ($fields == '*'){
            $fields = $this->default_select_fields;
        }
        $where['user_id']= $user_id;
        return $this->select($fields)->where($where)->first();
    }

}
