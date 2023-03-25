<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class QuarterSummary extends Authenticatable
{
//    use HasFactory;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 设置当前模型使用的数据库连接名。
     *
     * @var string
     */
    protected $connection = 'mysql_hr';

    /**
     * 与模型关联的数据表.
     *
     * @var string
     */
    protected $table = 'quarter_summary';

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
    protected $fillable = ['target','actuality','progress','quicken_factor','change_point','need_support','other','user_id','department_id','status'];

}
