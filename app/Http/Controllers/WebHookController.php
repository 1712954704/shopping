<?php
/**
 * User: Jack
 * Date: 2023/03/08
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers;

class WebHookController
{
    /**
     * web_hook回调
     *
    */
    public function run()
    {
        $msg = '';
        $code = '';
        $line = '';
        try {
            $valid_token = '5RBRNoOd8TWTfjeX';
            $client_token = $_SERVER['HTTP_X_GITLAB_TOKEN'];
            if ($client_token !== $valid_token) die('Token mismatch!');
//            $shell = "cd /www/wwwroot/api.bio-cloud.com.cn/ && pwd && /usr/local/git/bin/git pull 2>&1";
//            $shell = "cd /www/wwwroot/api.bio-cloud.com.cn/ && pwd && /usr/local/git/bin/git pull 2>&1";
//            exec($shell,$out_one);
            $shell = "cd /www/wwwroot/api_test.bio-cloud.com.cn/ && pwd && /usr/local/git/bin/git pull 2>&1";
            exec($shell,$out_two);
        }catch (\Exception $e){
            $msg = $e->getMessage();
            $code = $e->getCode();
            $line = $e->getFile();
        }
        $data = [
            '__server' => $_SERVER,
            'client_token' => $client_token,
//            'output_one' => $out_one,
            'output_two' => $out_two,
            'whoami' => exec('whoami'),
            'git_pull' => exec('git pull'),
            'ls' => exec('cd /www/wwwroot/api.bio-cloud.com.cn; ls -l'),
            'data' => date("Y-m-d H:i:s"),
            'error' => [
                'code' => $code,
                'msg' => $msg,
                'line' => $line,
            ],
        ];
        file_put_contents('web_hook.log',json_encode($data,256));
    }


}
