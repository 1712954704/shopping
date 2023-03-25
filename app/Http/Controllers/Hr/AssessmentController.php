<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Service\Hr\AssessmentService;
use App\Models\Hr\Assessment;
use App\Models\Hr\AssessmentsDetail;
use common;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use PHPMailer\PHPMailer\PHPMailer;


class AssessmentController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // 接收参数
        $columns   = ['*'];
        $user_id   = request('user_id');
        $interface = request('interface');
        $data      = request('data') ?? '';
        $id        = request('id');
        $page      = request('page') ? request('page') : 1;
        $limit     = request('limit') ? request('limit') : 10;
        $offset    = ($page - 1) * $limit;


        // Sever层处理页面逻辑
        $AssessmentService = new AssessmentService();
        $result            = $AssessmentService->index($interface, $user_id, $id, $limit, $offset,$data);

        return Common::format_return_result($result['code'], $result['msg'], $result['data']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $data['user_id'] = request('user_id');
        $data['detail']  = request('detail');


        // Sever层处理页面逻辑
        $AssessmentService = new AssessmentService();
        $result            = $AssessmentService->store($data);

        return Common::format_return_result($result['code'], $result['msg'], $result['data']);


    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $model  = common::getModelPath();
        $date   = new $model;
        $result = $date->show($id);

        $response['code'] = $result->id > 0 ? '200' : '404';
        $response['msg']  = $result->id > 0 ? 'success' : '数据不存在';
        $response['data'] = $result;
        return Common::format_return_result($response['code'], $response['msg'], $response['data']);

    }

    /**
     * update and renewal
     *
     * @param int $id
     * @return Response
     */
    public function renewal()
    {
        $assessment     = request('assessment');
        $interface      = request('interface');
        $data           = request('data');
        $data['detail'] = request('detail');


        // Sever层处理页面逻辑
        $AssessmentService = new AssessmentService();
        $result            = $AssessmentService->renewal($interface, $data, $assessment);

        return Common::format_return_result($result['code'], $result['msg'], $result['data']);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {


        $data['user_id'] = request('user_id');
        $data['detail']  = request('detail');


        // Sever层处理页面逻辑
        $AssessmentService = new AssessmentService();
        $result            = $AssessmentService->update($data);

        return Common::format_return_result($result['code'], $result['msg'], $result['data']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $model           = common::getModelPath();
        $data['user_id'] = request('user_id');


        // Sever层处理页面逻辑
        $AssessmentService = new AssessmentService();
        $result            = $AssessmentService->store($data);

        $response['code'] = $result > 0 ? '200' : '404';
        $response['msg']  = $result > 0 ? 'success' : '数据不存在';
        $response['data'] = $result;
        return json_encode($response);
    }

    public function remove()
    {
        $id         = request('id');
        $details_id = request('details_id');

        if ($id) {
            $result = Assessment::destroy($id);
        } else {
            $result = AssessmentsDetail::destroy($details_id);
        }


        $response['code'] = $result > 0 ? '200' : '404';
        $response['msg']  = $result > 0 ? 'success' : '数据不存在';
        $response['data'] = $result;

        return json_encode($response);
    }

    /**
     * 发送邮件
     * @return array
     */
    public function email()
    {

        //mset存储多个 key 对应的 value
        $array = array(
            'user1' => '张三',
            'user2' => '李四',
            'user3' => '王五'
        );
        // Redis::hset('tom:hash1','tom', array('key3' => 'v3', 'key4' => 'v4'));
        // Redis::hset( 'us:en:tom', 'username', '1008611');
        // Redis::hset( 'usa:usa', 'username', '1008611');
        // Redis::hset( 'token:key:token', 'username', '1008611');

        // $username = "tom";
        // 一条语句设置值和过期时间
        // 时间单位：秒
        // Redis::setex("remote:email_code:username",60*60*24,"120120");
        // Redis::setex("remote:email_code:$username",60*60*24,"785690");
        // Redis::setex("remote:email_code:username",600,$array);
        // Redis::setex("usa:email:key2",600,"value");
        // 时间单位：毫秒
        //redis.psetex("key",6000L,"value");
        // var_dump(Redis::get( 'remote:email_code:usernames'));
        // var_dump(Redis::get( 'remote:email_code:username'));
        // var_dump(Redis::get( 'remote:email_code'));
        // var_dump(Redis::get( 'remote'));
        // var_dump(Redis::get( 'mote'));
        if (Redis::get('mote') == NULL) {
            echo "born redis";
        }
        // var_dump(Redis::mget( 'remote:email_code:username','remote:email_code:tom'));


        // Redis::hset( 'user:zhangsan', 'user', '10086');

        // var_dump(Redis::keys('use*'));//模糊搜索

        exit();

        // Redis::set('name', 'guwenjie');
        // $arr = [
        //   "name"=>"jze",
        //     "test"=> 10086
        //
        // ];
        // Redis::hmset('name_id', $arr);
        //
        // $values = Redis::get('name');

        // $values = Redis::lrange('names', 5, 10);

        // dd($values);
        // HMSET runoobkey name "redis tutorial" description "redis basic commands for caching" likes 20 visitors 23000
        // exit();

        $mail = new PHPMailer(true);
        try {
            /*
             * 【一】服务器配置
             */
            $mail->CharSet   = "UTF-8";                                         //设定邮件编码
            $mail->SMTPDebug = 2;                                            //调试模式输出：0 不输出，2 输出
            $mail->isSMTP();                                                 //使用SMTP
            $mail->Host       = 'smtp.exmail.qq.com';                                     // SMTP服务器：以QQ为例
            $mail->SMTPAuth   = false;                                          // 允许 SMTP 认证
            $mail->Username   = "guomengtao@gudq.com";                     // SMTP用户名: 即发送方的邮箱
            $mail->Password   = "Aa73257690";               // SMTP授权码: 即发送方的邮箱授权码
            $mail->SMTPSecure = 'tls';                                       // 允许 TLS 或者ssl协议
            $mail->Port       = 465;                                               // 服务器端口： 25 或者465 或者587 具体要看邮箱服务器支持

            /*
             * 【二】收件人
             */
            $mail->setFrom("guomengtao@gudq.com", "发件人用户名");              //发件人：邮箱与用户名
            $mail->addAddress("rinuo@qq.com", '收件人1的用户名');     //收件人1：可添加多个收件人
            //$mail->addAddress("收件人2的邮箱", '收件人2的用户名');                  //收件人2：可添加多个收件人

            //$mail->addReplyTo('xxxx@163.com', 'info');                          //回复的时候回复给哪个邮箱 建议和发件人一致
            //$mail->addCC('cc@example.com');                                     //抄送人
            //$mail->addBCC('bcc@example.com');                                   //密送人

            /*
             * 【三】发送附件
             */
            // $mail->addAttachment('王庆国.mp4');           // 添加附件
            //$mail->addAttachment('fzs.png', 'haha.png');     // 发送附件并且重命名

            /*
             * 【四】发送内容
             */
            $mail->isHTML(true);    //是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = '邮件测试 ';   //邮件标题
            $mail->Body    = '<b><span style="font-size: 20px;color:#ff0000;">哈哈哈</span></b>';      //邮件内容
            $mail->AltBody = '哈哈哈';      //邮件内容：如果邮件客户端不支持HTML则显示此内容

            /*
             * 【五】发送请求
             */
            $mail->send();
            return ['msg' => '邮件发送成功'];
        } catch (Exception $e) {
            return ['msg' => '邮件发送失败：' . $mail->ErrorInfo];
        }
    }

    /**
     * 发送邮件
     * @return array
     */
    public function email_run()
    {

        $mail = new PHPMailer(true);
        try {
            /*
             * 【一】服务器配置
             */
            $mail->CharSet   = "UTF-8";                                         //设定邮件编码
            $mail->SMTPDebug = 0;                                            //调试模式输出：0 不输出，2 输出
            $mail->isSMTP();                                                 //使用SMTP
            $mail->Host       = 'smtp.qq.com';                                     // SMTP服务器：以QQ为例
            $mail->SMTPAuth   = true;                                          // 允许 SMTP 认证
            $mail->Username   = "helloptalk@qq.com";                     // SMTP用户名: 即发送方的邮箱
            $mail->Password   = "zbixzjlnpqkhdagc";               // SMTP授权码: 即发送方的邮箱授权码
            $mail->SMTPSecure = 'tls';                                       // 允许 TLS 或者ssl协议
            $mail->Port       = 587;                                               // 服务器端口： 25 或者465 或者587 具体要看邮箱服务器支持

            /*
             * 【二】收件人
             */
            $mail->setFrom("helloptalk@qq.com", "发件人用户名");              //发件人：邮箱与用户名
            $mail->addAddress("rinuo@qq.com", '收件人1的用户名');     //收件人1：可添加多个收件人
            //$mail->addAddress("收件人2的邮箱", '收件人2的用户名');                  //收件人2：可添加多个收件人

            //$mail->addReplyTo('xxxx@163.com', 'info');                          //回复的时候回复给哪个邮箱 建议和发件人一致
            //$mail->addCC('cc@example.com');                                     //抄送人
            //$mail->addBCC('bcc@example.com');                                   //密送人

            /*
             * 【三】发送附件
             */
            // $mail->addAttachment('王庆国.mp4');           // 添加附件
            //$mail->addAttachment('fzs.png', 'haha.png');     // 发送附件并且重命名

            /*
             * 【四】发送内容
             */
            $mail->isHTML(true);    //是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject = '邮件测试 ';   //邮件标题
            $mail->Body    = '<b><span style="font-size: 20px;color:#ff0000;">哈哈哈</span></b>';      //邮件内容
            $mail->AltBody = '哈哈哈';      //邮件内容：如果邮件客户端不支持HTML则显示此内容

            /*
             * 【五】发送请求
             */
            $mail->send();
            return ['msg' => '邮件发送成功'];
        } catch (Exception $e) {
            return ['msg' => '邮件发送失败：' . $mail->ErrorInfo];
        }
    }


    public function basic_email()
    {


        $view    = 'welcome';
        $content = ['message' => '11086', 'name' => 'tom'];
        $message = "10886";
        $from    = 'guomengtao@163.com';
        $name    = 'guomengtao';
        $to      = 'rinuo@qq.com';
        $subject = "3-13 15:33 测试rinuo 发送";


        $flag = Mail::send($view, $content, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
        if ($flag) {
            echo 'ok';
        } else {
            echo 'NO！';
        }


    }

    public function send_email()
    {


        $data['user_id'] = request('user_id');
        $data            = request('data');
        $data['view']    = $data['view'] ?? 'email';
        $data['message'] = 'message';
        $data['from']    = 'guomengtao@gudq.com';


        // Sever层处理页面逻辑
        $AssessmentService = new AssessmentService();
        $result            = $AssessmentService->send_email($data);

        $response['code'] = $result > 0 ? '200' : '404';
        $response['msg']  = $result > 0 ? 'success' : '数据不存在';
        $response['data'] = $result;
        return json_encode($response);
    }

}
