<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */

namespace App\Http\Service\Hr;

use App\Http\Service\ServiceBase;
use App\Models\Common\EmailLog;
use App\Models\Common\User;
use App\Models\Hr\Assessment;
use App\Models\Hr\AssessmentsDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use library\Constants\Model\AssessmentConstants;

// use Illuminate\Support\Facades\Mail;

class AssessmentService extends ServiceBase
{
    public function __construct()
    {
        parent::__construct();


    }

    /**
     * 查询列表
     * @param int $user_id
     * @param string $test_number
     * @return mixed
     */
    public function index($interface, $user_id, $id, $limit, $offset, $data)
    {
        if ($interface == 1) {


            $where[] = ['node', '<>', null];
            // 模型层处理数据库数据
            // $result = $this->model::index($limit, $offset, $where, $id);
            // $result = DB::connection('mysql_hr')->table('assessments')
            //     ->select('id','node')
            //     ->selectRaw('count(*) as sum')
            //     ->orderBy('node', 'asc')
            //     ->where($where)
            //     // ->selectRaw('count(*) as sum, min(pid) as someMin, max(pid) as someMax')
            //     ->groupBy('node')
            //     ->get();

            $result                         = [];
            $result['staging']              = $this->model::where('node', 100)->count();
            $result['in_assessment']        = $this->model::where('node', '<', 10)->count();
            $result['assessment_completed'] = $this->model::where('node', '>', 9)->count();


            $this->return_data['data'] = $result;
            return $this->return_data;

        }
        if ($interface == 2) {

            // 统计每个等级的完成度接口

            $where[] = ['grade', '<>', null];
            $result  = DB::connection('mysql_hr')->table('assessments')
                ->select('id', 'grade')
                ->selectRaw('count(*) as sum')
                ->orderBy('grade', 'asc')
                ->where($where)
                ->groupBy('grade')
                ->get();


            $this->return_data['data'] = $result;
            return $this->return_data;

        }

        // 667586630
        if ($interface == 5) {
            $dept_children = $this->get_dept_children(668193149, 0,);


            $this->return_data['data'] = $dept_children;
            return $this->return_data;
        }

        // 667586630
        if ($interface == 7) {

            $dept_children = $this->get_all_dept_children(0);

            // var_dump($this->get_dept_children(0));


            $this->return_data['data'] = $dept_children;
            return $this->return_data;
        }
        if ($interface == 3) {


            // 判断一个部门的父ID的类型是属于组织1

            // 获取所有组织的ID
            $where       = ['group_type' => 1];
            $assessments = DB::connection('mysql_common')->table('structure')
                ->select('id', "name", "group_type")
                ->where($where)
                ->get();

            foreach ($assessments as $value) {
                $assessments_ids[] = $value->id;
            }
            // // 获取顶级的部门ID
            $where = ['group_type' => 2];

            $top_department = DB::connection('mysql_common')->table('structure')
                ->select('id', 'pid', "name", "group_type")
                ->whereIn('pid', $assessments_ids)
                ->where($where)
                ->get();

            // 文件存储到public目录
            // file_put_contents("t.txt",json_encode($top_department,256));
            // var_dump(json_encode($top_department,256));


            // 获取每个部门的员工
            $dept_children = "";
            foreach ($top_department as $value) {


                $dept_children = $this->get_dept_children($value->id, 0,);



                // 查询部门的直属员工，包括子部门
                $department_users = DB::connection('mysql_common')->table('user')
                    ->select('id', "name")
                    // ->where("department_id", $value->id)
                    ->whereIn('department_id', $dept_children)
                    ->get();


                // var_dump($department_users);
                // exit();

                $department_users_arr = [];
                foreach ($department_users as $value_user) {
                    $department_users_arr[] = $value_user->id;
                }

                // $get_all[$value->id] = [
                //     "department_users_arr"   => $department_users_arr
                // ];


                if ($department_users_arr) {


                    // var_dump($department_users_arr);
                    // 获取这些员工的分组分数
                    $department_users_grade = DB::connection('mysql_hr')->table('assessments')
                        ->select('grade', 'encode', 'user_id')
                        // ->whereIn('user_id',[11,7,14])
                        ->whereIn('user_id', $department_users_arr)
                        ->selectRaw('count(*) as sum')
                        ->orderBy('grade', 'asc')
                        ->where('grade', '<>', null)
                        ->groupBy('grade')
                        ->get();
                    // var_dump($department_users_grade->toArray());die();


                    $department_users_arr_simple = [];
                    foreach ($department_users_grade as $value_user_grade) {
                        $department_users_arr_simple[] = $value_user_grade;
                    }
                    if ($department_users_arr_simple) {
                        $get_all[$value->id] = [
                            "department_id"       => $value->id,
                            "department_user"     => json_encode($department_users_arr),
                            "department_children" => json_encode($dept_children),
                            "department_name"     => $value->name

                        ];

                        $get_all[$value->id]['total'] = $department_users_arr_simple;
                    }


                };


            }

            $this->return_data['data'] = $get_all;
            return $this->return_data;

        }

        if ($interface == 6) {
            $check_user_info           = User::where('id', $user_id)->first();
            $this->return_data['data'] = $check_user_info;
            return $this->return_data;
        }


        if ($id) {
            $where['id'] = $id;
        } elseif ($user_id) {
            $where['user_id'] = $user_id;
        } else {
            $where = [];

            // 条件大于/小于的两种写法示例
            // $where[] = ['user_id', '>', 1];
            // $where = [
            //     [
            //         'user_id', '>', 1
            //     ]
            // ];


        }
        // 模型层处理数据库数据
        $result = $this->model::index($limit, $offset, $where, $id, $data);
        // 处理邮件通知状态
        if (!$id) {
            foreach ($result['data'] as &$item) {
                if (in_array($item->status, [AssessmentConstants::STATUS_CHECK_IN, AssessmentConstants::STATUS_CHECK_END])) {
                    $item->email_status = 1;
                } else {
                    $item->email_status = 0;
                }
            }
        }
        $this->return_data['data'] = $result;
        return $this->return_data;
    }


    public function get_dept_children($dept_id, $num = 0, $result = [])
    {

        // 查询某个部门下的所有直属部门ID
        $where = ['pid' => $dept_id];

        $assessments = DB::connection('mysql_common')->table('structure')
            ->select('id', "name", "group_type")
            ->where($where)
            ->get();


        foreach ($assessments as $value) {


            $result[] = $value->id;
            // 迭代获取子部门
            $this->get_dept_children($value->id, $num, $result);


        }


        $result[] = $dept_id;
        return $result;
    }

    public function get_all_dept_children($dept_id, $num = 0, $result = [])
    {

        // 查询某个部门下的所有直属部门ID
        $where = ['pid' => $dept_id];

        $assessments = DB::connection('mysql_common')->table('structure')
            ->select('id', "name", "group_type")
            ->where($where)
            ->get();


        foreach ($assessments as $value) {


            $result[$value->id] = $value->id;

            var_dump($result);
            // 迭代获取子部门
            $this->get_dept_children($value->id, $num, $result);


        }

        var_dump($result);


        return $result;
    }

    /**
     * 新增
     * @param array $data
     * @return mixed
     */
    public function store($data)
    {


        $assessment_id = DB::connection('mysql_hr')->table('assessments')->insertGetId(
            ['user_id' => $data['user_id']]
        );

        // GOAL_SETTINGyyyymmdd+自增6位数
        $encode = "GOAL_SETTING" . date("Ymd") . $assessment_id;

        // 更新到主表：
        Assessment::where('id', $assessment_id)
            ->update(['encode' => $encode]);


        foreach ($data['detail'] as $value) {
            $value["assessment_id"] = $assessment_id;
            $result                 = DB::connection('mysql_hr')->table('assessments_details')->insert($value);
        }

        $this->update_date($assessment_id);


        $this->return_data['data']['assessment_id'] = $assessment_id;
        return $this->return_data;
    }

    /**
     * 更新考评周期
     * @param $assessment_id
     * @return mixed
     */
    public function update_date($assessment_id)
    {

        // 查询指定考评里时间最早的
        $start_time = AssessmentsDetail::where("assessment_id", $assessment_id)
            ->where("start_time", '>', 1)
            ->oldest('start_time')
            ->first();
        $end_time   = AssessmentsDetail::where("assessment_id", $assessment_id)
            ->latest('end_time')
            ->first();

        if ($start_time == null or $end_time == null) {
            return 0;
        }


        $start_time = $start_time->start_time;


        $end_time = $end_time->end_time;


        $diff_seconds = $end_time - $start_time;


        $diff_days = intval($diff_seconds / 86400);
        $duration  = $diff_days * 8;

        if ($duration < 0) {
            $duration = 0;
        }


        // 更新到主表：
        $result = Assessment::where('id', $assessment_id)
            ->update(['start_time' => $start_time, 'end_time' => $end_time, 'duration' => $duration]);

        return $result;

    }

    /**
     * 编辑
     * @param array $data
     * @return mixed
     */
    public function renewal($interface, $data, $assessment)
    {
        if ($interface == 2) {

            unset($data['detail']);


            // 更新考评的主表信息
            // $assessment = $assessment[0];
            $result = DB::connection('mysql_hr')->table('assessments')
                ->where('id', $data['id'])
                ->update($data);

            $this->return_data['data'] = $result;
            return $this->return_data;

        }

        if ($assessment) {
            // 更新考评的主表信息
            $assessment = $assessment[0];
            $result     = DB::connection('mysql_hr')->table('assessments')
                ->where('id', $assessment['id'])
                ->update($assessment);


        }

        // 循环更新考评详情信息
        $assessment_id = 0;
        foreach ($data['detail'] as $value) {
            if (!$assessment_id) {
                $assessment_id = $value['assessment_id'];
            }

            // array_key_exists 判断数组键名是否存在，不是判断数组数值
            $has_id = array_key_exists('id', $value);
            if ($has_id) {
                $result = DB::connection('mysql_hr')->table('assessments_details')
                    ->where('id', $value['id'])
                    ->update($value);
            } else {
                $result = DB::connection('mysql_hr')->table('assessments_details')->insert($value);
            }
        }

        $this->update_date($assessment_id);


        $this->return_data['data'] = $result;
        return $this->return_data;
    }

    /**
     * @param $data
     * @param string $view 邮件模板 默认为email
     * @param string $content 邮件内容
     * @param string $to 发送人的邮箱地址
     * @param string $subject 邮件标题
     * @param string $name 收件人姓名
     * @param string $user_id 发邮件人user_id 默认1系统
     * @return array
     */
    public function send_email($data)
    {


        $view    = $data['view'] ?? "email";
        $content = $data['content'];
        $message = 'message';
        $from    = "admin@bio-cloud.com.cn";
        $name    = $data['name'];
        $to      = $data['to'];
        $subject = $data['subject'];

        // 发送邮件记录登记
        $value['content'] = json_encode($content, JSON_UNESCAPED_UNICODE);
        $value['view']    = $data['view'] ?? "email";
        $value['from']    = $from;
        $value['to']      = $data['to'];
        $value['user_id'] = $data['user_id'];
        $value['subject'] = $data['subject'];
        $value['msg']     = "0";

        $mail_log_id = EmailLog::insertGetId($value);

        $result = Mail::send($view, $content, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });

        $value['msg'] = "200";
        EmailLog::where('id', $mail_log_id)
            ->update($value);


        $this->return_data['data'] = $result;
        return $this->return_data;


    }

    public function send_email_test()
    {


        $to      = "inform@biolink.com";
        $subject = "My subject";
        $txt     = "Hello world!";
        $headers = "From: webmaster@example.com";

        mail($to, $subject, $txt, $headers);


    }
}
