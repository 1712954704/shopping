<?php

namespace App\Models\Hr;

use App\Http\Service\Common\FileService;
use App\Models\Common\Department;
use App\Models\Common\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory;
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'mysql_hr';
    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'date:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
    ];

    protected $fillable = ['*'];

    // 查询列表
    public static function index($limit, $offset, $where, $id, $data = [])
    {

        // offset 设置从哪里开始
        // limit 设置想要查询多少条数据
        $model        = \common::getModelPath();
        $whereBetween = [];

        // $has_id = array_key_exists('id', $value);

        $has_start_time = 0;
        $has_end_time   = 0;

        $has_arr = is_array($data);
        if ($has_arr) {
            $has_node = array_key_exists('node', $data);
            if ($has_node) {
                $node    = $data['node'];
                $where[] = ['node', $node];
            }

            $email_status = array_key_exists('email_status', $data);
            if ($email_status) {
                $email_status = $data['email_status'];
                $where[]      = ['email_status', $email_status];
            }

            $has_start_time = array_key_exists('start_time', $data);
            $has_end_time   = array_key_exists('end_time', $data);


        }
        if ($id) {
            // 查询单个考评详情
            $result = $model::where('id', '=', $where['id'])
                ->select('*')
                ->with(['user:account,id,structure_id,department_id'])
                ->with(['assessment_detail'])
                ->first();


            $document = $result->document;

            if ($document) {
                $file = new FileService();

                $file_data          = $file->get_file_url($document);
                $filepath           = $file_data['data']['file_url'] ?? '';
                $file_original_name = $file_data['data']['file_original_name'] ?? '';
                if ($filepath) {
                    $result->document = $filepath;
                }
                $result->file_original_name = $file_original_name;
            }


        } elseif ($has_start_time and $has_end_time) {


            $start_time = $data['start_time'];
            $end_time   = $data['end_time'];
            $between    = [$start_time, $end_time];


            // 查询所有用户考评记录
            $result['total'] = $model::where($where)->count();

            $result['data'] = $model::where($where)
                ->whereBetween("start_time", $between)
                ->orderBy('id', 'desc')
                ->with(['user:account,id,structure_id,department_id'])
                ->with(['user.structure_info:id,name'])
                ->limit($limit)
                ->offset($offset)
                ->get();
        } else {

            // 查询所有用户考评记录
            $result['total'] = $model::where($where)->count();

            $result['data'] = $model::where($where)
                ->orderBy('id', 'desc')
                ->with(['user:account,id,structure_id,department_id'])
                ->with(['user.structure_info:id,name'])
                ->limit($limit)
                ->offset($offset)
                ->get();


        }


        return $result;
    }

    /**
     * 获取考评的所有绩效
     */
    public function assessment_detail()
    {
        return $this->hasMany(AssessmentsDetail::class, 'assessment_id');
    }


    // 远程一对多
    public function deployments()
    {

        // 第一个参数是我们最终想要访问的模型的名称
        // 第二个参数是中间模型的名称
        // 第三个参数是中间表的外键名
        // 第四个参数是最终想要访问的模型的外键名
        // 第五个参数是当前模型的本地键名
        // 第六个参数是中间模型的本地键名
        return $this->hasManyThrough(
            Deployment::class,
            Environment::class,
            'project_id', // environments 表的外键名
            'environment_id', // deployments 表的外键名
            'id', // projects 表的本地键名
            'id' // environments 表的本地键名
        );
    }

    public function show($id)
    {

        $model  = \common::getModelPath();
        $result = $model::where('id', '=', $id)
            ->select('*')
            ->with(['user:account,id,structure_id'])
            ->with(['assessment_detail'])
            ->first();


        return $result;

    }

    /**
     * 获取与用户相关的电话记录
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * 获取与用户相关的名称
     */
    public function leader()
    {
        return $this->hasOne(User::class, 'id', 'leader');
    }

    /**
     * 获取与用户相关的电话记录
     */
    public function pid()
    {
        return $this->hasOne(Department::class, 'id', 'pid');
    }

    public function children()
    {
        return $this->child()->with('children:id,name,structure_id,pid,encode,order,created_at,updated_at');
    }

    // 递归子级

    public function child()
    {
        return $this->hasMany(self::class, 'pid');
    }

    public function parents()
    {
        return $this->father()->with('parents');
    }

    // 递归父级

    public function father()
    {
        return $this->hasMany(self::class, 'id', 'pid');
    }

}
