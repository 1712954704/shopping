<?php
/**
 * User: Jack
 * Date: 2023/03/18
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Service\Common;

use App\Http\Service\ServiceBase;
use App\Models\Common\File;

class FileService extends ServiceBase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取文件地址
     * @param array|string $file_id
     * @return array [
     *  'file_url' // 根据传值确定返回值为string还是array
     *  'file_name' // 根据传值确定返回值为string还是array
     * ]
     */
    public function get_file_url($file_id)
    {
        try {
            if (is_array($file_id)){
                $file_result = File::whereIn('file_id',$file_id)->get();
                $file_result = \Common::laravel_to_array($file_result);
                $this->return_data['data']['file_url'] = array_column($file_result,'file_url');
                $this->return_data['data']['file_original_name'] = array_column($file_result,'file_original_name');
            }else{
                $file_result = File::where(['file_id'=>$file_id])->first();
                $this->return_data['data']['file_url'] = $file_result->file_url;
                $this->return_data['data']['file_original_name'] = $file_result->file_original_name;
            }
        }catch (\Exception $e){
            $this->return_data['code'] = 201; // 返回错误
        } finally {
            return $this->return_data;
        }
    }


}
