<?php

namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DingController extends Controller
{


    public function index()
    {


        echo "获取最新token";

        $url = "https://oapi.dingtalk.com/gettoken?appkey=ding3hup154wsucufjuh&appsecret=d1dvkHBk7FKwPPM0W2RkVq5mC72mSLBSS1WaMy4XZlL1w2oKJKKw2c13FugyiIa6";


        $token_all = json_decode($this->http_get($url));

        var_dump($token_all->access_token);
        $access_token = $token_all->access_token;


        setcookie("token", $access_token, time() + 7000);


        $token = $_COOKIE["token"];


        return $token;

    }

    /**
     * GET方式
     * @param $url
     * @return bool|string
     */
    public function http_get($url)
    {
         echo "welcome to ding!";

        // $interface = 1;
        $interface = request('interface');

        /**
         * 获取用户详情信息
         */
        if ($interface == 2) {


            echo 126;


            $param = request('param') ?? '';
            // $status_list = $param['status_list'] ?? 3;

            // 从本地数据库获取所有用户的ID
            $titles = DB::connection('mysql_common')->table('user_ding')->get();


            // $titles = DB::connection('mysql_common')->table('user_ding')->pluck('userid');

            foreach ($titles as $title) {
                $userid = $title->userid;
                // $status_list = $title->status_list;

                $this->get_user_info($userid);
            }


        }
    }

    public function store()
    {
        // $interface = 1;
        $interface = request('interface');


        if ($interface == 6) {

            // 导入hr给的403个Excel
            // 读取数据从 hr_email_list 更新到Ding线上

            $titles = DB::connection('mysql_common')->table('user')->get();


            foreach ($titles as $title) {

                $email  = $title->email;
                $userid = $title->orgin_id;


                $token = $this->get_token();

                $url = "https://oapi.dingtalk.com/topapi/v2/user/update?access_token=";
                $url = $url . $token;

                $param       = request('param') ?? '';
                $offset      = $param['offset'] ?? 0;
                $size        = $param['size'] ?? 0;
                $status_list = $param['status_list'] ?? 3;

                $param = [
                    "userid" => $userid,
                    "email"  => $email
                ];

                // var_dump($param);
                //
                // exit();

                $result = $this->http_post($url, $param);

                $result = json_decode($result);

                var_dump($result);
            }
            echo " update finish!";

            return 0;

        }
        if ($interface == 5) {
            // 导入hr给的403个Excel
            // 读取数据从 hr_email_list 存入本地接口

            // 遍历根据姓名更新到本地user库
            $titles = DB::connection('mysql_hr')->table('email_list')->get();


            foreach ($titles as $title) {
                $member['email'] = $title->email;
                $account         = $title->name;


                // 更新到本地uer表
                $result = DB::connection('mysql_common')->table('user')
                    ->where('account', $account)
                    ->update(
                        $member
                    );
                var_dump($result);
            }


            return 0;

        }
        if ($interface == 4) {
            // 查询部门详情
            // 查询单个组织详情

            ob_end_clean();
            // 获取所有的组织
            // 从本地数据库获取所有组织的ID
            $titles = DB::connection('mysql_common')->table('user_ding_dept')->get();


            foreach ($titles as $title) {
                $id = $title->dept_id;

                var_dump($id);
                // $this->get_dept_detail(836635093);
                // exit();
                $this->get_dept_detail($id);
            }


            return 0;


        }


        if ($interface == 3) {


            // 获取所有的组织
            // 从本地数据库获取所有组织的ID
            $titles = DB::connection('mysql_common')->table('user_ding_dept')->get();


            foreach ($titles as $title) {
                $id = $title->dept_id;

                var_dump($id);
                $this->get_first_dept($id, 15);
            }


            return 0;

        }

        /**
         * 获取用户详情信息
         */
        if ($interface == 2) {


            echo 126;


            $param = request('param') ?? '';
            // $status_list = $param['status_list'] ?? 3;

            // 从本地数据库获取所有用户的ID
            $titles = DB::connection('mysql_common')->table('user_ding')->get();


            // $titles = DB::connection('mysql_common')->table('user_ding')->pluck('userid');

            foreach ($titles as $title) {
                $userid = $title->userid;
                // $status_list = $title->status_list;

                $this->get_user_info($userid);
            }


        }

        /**
         * 获取用户ID接口
         */
        if ($interface == 1) {

            $token = $this->get_token();

            $url = "https://oapi.dingtalk.com/topapi/smartwork/hrm/employee/queryonjob?access_token=";
            $url = $url . $token;

            $param       = request('param') ?? '';
            $offset      = $param['offset'] ?? 0;
            $size        = $param['size'] ?? 0;
            $status_list = $param['status_list'] ?? 3;

            $param = [
                "offset"      => $offset,
                "size"        => $size,
                "status_list" => $status_list
            ];

            var_dump($param);

            $result = $this->http_post($url, $param);

            $result = json_decode($result);
            // var_dump($result);
            // var_dump($result->result);
            // var_dump($result->result->data_list);

            // 批量存入数据库

            $userid      = $result->result->data_list ?? 0;
            $next_cursor = $result->result->next_cursor ?? 0;


            // setcookie("next_cursor", $next_cursor, time() + 100);

            var_dump("next_cursor:" . $next_cursor);


            // var_dump($userid);
            unset($member);
            foreach ($userid as $value) {
                var_dump($value);
                $member["userid"]      = $value;
                $member["status_list"] = $status_list;

                // $result = DB::connection('mysql_common')->table('user_ding')->insert($memeber);
                // 更新，不存在就创建
                $result = DB::connection('mysql_common')->table('user_ding')
                    ->updateOrInsert(
                        ['userid' => $value],
                        $member
                    );

                var_dump($result);
            }
        }


    }

    public function get_token()
    {

        if (empty($_COOKIE["token"])) {

            echo "Start building new token";

            // $app_key    = env('DING_APP_KEY', 'mysql');
            $app_key = env('DING_APP_KEY', 'mysql');
            // $app_secret = env('DING_APP_SECRET', 'mysql');
            $app_secret = env('DING_APP_SECRET', 'mysql');

            var_dump($app_key);
            exit();

            $url = "https://oapi.dingtalk.com/gettoken?appkey=$app_key=$app_secret";


            $token_all = json_decode($this->http_get($url));


            var_dump($token_all);
            var_dump($token_all->access_token);
            $access_token = $token_all->access_token;

            // var_dump($access_token);


            setcookie("token", $access_token, time() + 7000);

            $token = $_COOKIE["token"];

            return $token;
        }


        $token = $_COOKIE["token"];

        return $token;

    }

    /**
     * POST 方式
     * @param $url
     * @param $param
     * @param bool $post_file
     * @return bool|string
     */
    public function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }

        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);//
        curl_setopt($oCurl, CURLOPT_POST, true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        $sContent = curl_exec($oCurl);
        $aStatus  = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    public function get_dept_detail($dept_id)
    {

        var_dump($dept_id);
        // 获取某个部门下的一级部门
        $token = $this->get_token();


        $url = "https://oapi.dingtalk.com/topapi/v2/department/get?access_token=";
        $url = $url . $token;

        // $param   = request('param') ?? '';
        // $dept_id = $param['dept_id'] ?? 1;


        $param = [
            "dept_id" => $dept_id
        ];

        // var_dump($param);

        $result = $this->http_post($url, $param);
        // var_dump($result);
        $result = json_decode($result);

        $member = [];

        // var_dump($result);
        // var_dump($result->result->org_dept_owner) ?? '';
        // var_dump($result->result->dept_manager_userid_list) ?? '';
        // var_dump($result->result->order) ?? '';

        // 与eHR对应关系
        // handler_id 或者 manager_id
        $member['manager_id']     = $result->result->org_dept_owner ?? '';
        $dept_manager_userid_list = $result->result->dept_manager_userid_list ?? '';

        if ($dept_manager_userid_list) {
            $dept_manager_userid_list = implode(",", $dept_manager_userid_list);
        }


        var_dump($dept_manager_userid_list . "---------------");

        // 新建的字段 manager_userid_list
        $member['manager_userid_list'] = $dept_manager_userid_list;


        // 排序
        $member['order'] = $result->result->order ?? '';


        // 存入数据库
        // 更新，不存在就创建
        $result = DB::connection('mysql_common')->table('user_ding_dept')
            ->updateOrInsert(
                ['dept_id' => $dept_id],
                $member
            );
        var_dump($result);


    }

    public function get_first_dept($dept_id, $num)
    {


        // 获取某个部门下的一级部门
        $token = $this->get_token();


        $url = "https://oapi.dingtalk.com/topapi/v2/department/listsub?access_token=";
        $url = $url . $token;

        // $param   = request('param') ?? '';
        // $dept_id = $param['dept_id'] ?? 1;


        $param = [
            "dept_id" => $dept_id
        ];

        // var_dump($param);

        $result = $this->http_post($url, $param);
        // var_dump($result);
        $result = json_decode($result);

        // var_dump($result->result)
        foreach ($result->result as $title) {
            // var_dump($title);
            // var_dump($title->dept_id);
            // var_dump($title->parent_id);
            // var_dump($title->name);


            $member['dept_id'] = $title->dept_id;
            $member['name']    = $title->name;
            $member['pid']     = $title->parent_id;


            // 存入数据库

            // 更新，不存在就创建
            $result = DB::connection('mysql_common')->table('user_ding_dept')
                ->updateOrInsert(
                    ['dept_id' => $member['dept_id']],
                    $member
                );
            var_dump($result);

        }


    }

    /**
     * 更新某个员工的信息到数据库
     * @param $userid
     */
    public function get_user_info($userid)
    {
        $token = $this->get_token();

        // 116964313921590766 唐
        $url = "https://oapi.dingtalk.com/topapi/v2/user/get?access_token=";
        $url = $url . $token;


        $param = [
            "userid" => $userid,
        ];


        $result = $this->http_post($url, $param);
        // var_dump($result);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);
        // var_dump($result);
        // var_dump($result['result']['name']);
        // var_dump($result->name);


        //
        //
        $name           = $result['result']['name'] ?? '';
        $mobile         = $result['result']['mobile'] ?? '';
        $email          = $result['result']['email'] ?? '';
        $title          = $result['result']['title'] ?? '';
        $work_place     = $result['result']['work_place'] ?? '';
        $remark         = $result['result']['remark'] ?? '';
        $unionid        = $result['result']['unionid'] ?? '';
        $manager_userid = $result['result']['manager_userid'] ?? '';
        $dept_id_list   = $result['result']['dept_id_list'] ?? '';
        $org_email      = $result['result']['org_email'] ?? '';
        $admin          = $result['result']['admin'] ?? '';
        $leader_in_dept = $result['result']['leader_in_dept'] ?? '';
        $boss           = $result['result']['boss'] ?? '';
        $senior         = $result['result']['senior'] ?? '';
        $job_number     = $result['result']['job_number'] ?? '';
        $hired_date     = $result['result']['hired_date'] ?? '';


        // 批量存入数据库


        $memeber["origin_id"] = $userid;
        $memeber["name"]      = $name;
        $memeber["phone"]     = $mobile;
        $memeber["email"]     = $email;
        // $memeber["title"]          = $title;
        // $memeber["work_place"]     = $work_place;
        // $memeber["remark"]         = $remark;
        $memeber["job_number"] = $job_number;
        // $memeber["manager_userid"] = $manager_userid;
        // $memeber["unionid"]        = $unionid;
        // $memeber["dept_id_list"]   = implode(",", $dept_id_list);
        // $memeber["org_email"]      = $org_email;
        // $memeber["admin"]          = $admin;
        // $memeber["leader_in_dept"] = $leader_in_dept;
        // $memeber["boss"]           = $boss;
        // $memeber["senior"]         = $senior;
        // $memeber["hired_date"]     = $hired_date;


        // 更新，不存在就创建
        $result = DB::connection('mysql_common')->table('user')
            ->updateOrInsert(
                ['origin_id' => $userid],
                $memeber
            );


        var_dump($result);

        if ($result) {
            var_dump($memeber);

            $result = DB::connection('mysql_common')->table('user_ding_log')
                ->updateOrInsert(
                    ['origin_id', '<>', $userid],
                    $memeber
                );

        }

    }

    /**
     * 更新某个员工的信息到数据库
     * @param $userid
     */
    public function get_user_info_for_go_save_ding_table($userid)
    {
        $token = $this->get_token();

        // 116964313921590766 唐
        $url = "https://oapi.dingtalk.com/topapi/v2/user/get?access_token=";
        $url = $url . $token;


        $param = [
            "userid" => $userid,
        ];


        $result = $this->http_post($url, $param);
        // var_dump($result);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);
        // var_dump($result);
        // var_dump($result['result']['name']);
        // var_dump($result->name);


        //
        //
        $name           = $result['result']['name'] ?? '';
        $mobile         = $result['result']['mobile'] ?? '';
        $email          = $result['result']['email'] ?? '';
        $title          = $result['result']['title'] ?? '';
        $work_place     = $result['result']['work_place'] ?? '';
        $remark         = $result['result']['remark'] ?? '';
        $unionid        = $result['result']['unionid'] ?? '';
        $manager_userid = $result['result']['manager_userid'] ?? '';
        $dept_id_list   = $result['result']['dept_id_list'] ?? '';
        $org_email      = $result['result']['org_email'] ?? '';
        $admin          = $result['result']['admin'] ?? '';
        $leader_in_dept = $result['result']['leader_in_dept'] ?? '';
        $boss           = $result['result']['boss'] ?? '';
        $senior         = $result['result']['senior'] ?? '';
        $job_number     = $result['result']['job_number'] ?? '';
        $hired_date     = $result['result']['hired_date'] ?? '';


        // 批量存入数据库

        $memeber["origin_id"] = $userid;
        $memeber["name"]      = $name;
        $memeber["mobile"]    = $mobile;
        $memeber["email"]     = $email;
        // $memeber["title"]          = $title;
        // $memeber["work_place"]     = $work_place;
        // $memeber["remark"]         = $remark;
        $memeber["job_number"]     = $job_number;
        $memeber["manager_userid"] = $manager_userid;
        // $memeber["unionid"]        = $unionid;
        // $memeber["dept_id_list"]   = implode(",", $dept_id_list);
        // $memeber["org_email"]      = $org_email;
        // $memeber["admin"]          = $admin;
        // $memeber["leader_in_dept"] = $leader_in_dept;
        // $memeber["boss"]           = $boss;
        // $memeber["senior"]         = $senior;
        $memeber["hired_date"] = $hired_date;


        // 更新，不存在就创建
        $result = DB::connection('mysql_common')->table('user')
            ->updateOrInsert(
                ['origin_id' => $userid],
                $memeber
            );


        var_dump($result);

    }

}
