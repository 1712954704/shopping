<?php
/**
 * User: Jack
 * Date: 2023/02/28
 * Email: <1712954704@qq.com>
 */
namespace App\Http\Controllers\Common;


use App\Http\Controllers\BaseController;
use App\Http\Service\Common\CalendarService;
use App\Http\Service\Common\DictionaryService;
use library\Constants\StatusConstants;


class DictionaryController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 数据字典分类相关
     *
     */
    public function dictionary_type_operate()
    {
        $dictionary_service = new DictionaryService();
        switch ($this->method) {
            case 'GET':  // 获取字典类别列表
                // 检测参数
                $page             = $this->get_safe_int_param('page',1);
                $limit            = $this->get_safe_int_param('limit',10);
                $offset = ($page - 1) * $limit;
                $params['full_name']         = $this->data_arr['full_name'] ?? '';   // 名称
                $params['system_type']             = $this->system_type;   // 系统类型
                $data = $dictionary_service->get_dictionary_list($params,$limit,$offset);
                break;
            case 'POST':  // 添加字典类别
                // 检测参数
                $params['full_name']             = $this->check_param('full_name'); // 名称
                $params['parent_id']             = $this->data_arr['parent_id'] ?? 0;   // 起始时间
                $params['encode']             = $this->data_arr['encode'] ?? '';   // 编码
                $params['system_type']             = $this->system_type;   // 系统类型
                $params['user_id']             = $this->user_info['id'];   // 系统类型
                $data = $dictionary_service->add_dictionary($params);
                break;
            case 'PUT':  // 更新字典类别
                // 检测参数
                $id                      = $this->check_param('id');
                $params['full_name']             = $this->check_param('full_name'); // 名称
                $params['parent_id']             = $this->data_arr['start_time'] ?? 0;   // 起始时间
                $params['encode']             = $this->data_arr['encode'] ?? '';   // 编码
                $params['system_type']             = $this->system_type;   // 系统类型
                $params['user_id']             = $this->user_info['id'];   // 系统类型
                $data = $dictionary_service->update_dictionary($id,$params);
                break;
            case 'DELETE':  // 删除字典类别
                // 检测参数
                $id                      = $this->check_param('id');
                $data = $dictionary_service->del_dictionary($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }


    /**
     * 数据字典值相关
     *
     */
    public function dictionary_value_operate()
    {
        $dictionary_service = new DictionaryService();
        switch ($this->method) {
            case 'GET':  // 获取字典类别列表
                // 检测参数
                $page             = $this->get_safe_int_param('page',1);
                $limit            = $this->get_safe_int_param('limit',10);
                $offset = ($page - 1) * $limit;
                $params['full_name']         = $this->data_arr['full_name'] ?? '';   // 名称
                $params['type_id']         = $this->data_arr['type_id'] ?? '';   // 类别主键
                $params['system_type']             = $this->system_type;   // 系统类型
                $data = $dictionary_service->get_dictionary_value_list($params,$limit,$offset);
                break;
            case 'POST':  // 添加字典类别
                // 检测参数
                $params['full_name']                = $this->check_param('full_name'); // 名称
                $params['dictionary_type_id']       = $this->check_param('type_id'); // 数据字典类型表id
                $params['parent_id']                = $this->data_arr['parent_id'] ?? 0;   // 起始时间
                $params['encode']                   = $this->data_arr['encode'] ?? '';   // 编码
                $params['system_type']              = $this->system_type;   // 系统类型
                $params['user_id']                  = $this->user_info['id'];   // 添加者id
                $data = $dictionary_service->add_dictionary_value($params);
                break;
            case 'PUT':  // 更新字典类别值
                // 检测参数
                $id                      = $this->check_param('id');
                $params['full_name']                = $this->check_param('full_name'); // 名称
                $params['dictionary_type_id']       = $this->check_param('type_id'); // 数据字典类型表id
                $params['parent_id']                = $this->data_arr['parent_id'] ?? 0;   // 起始时间
                $params['encode']                   = $this->data_arr['encode'] ?? '';   // 编码
                $params['system_type']              = $this->system_type;   // 系统类型
                $params['user_id']                  = $this->user_info['id'];   // 添加者id
                $data = $dictionary_service->update_dictionary_value($id,$params);
                break;
            case 'DELETE':  // 删除字典类别值
                // 检测参数
                $id                      = $this->check_param('id');
                $data = $dictionary_service->del_dictionary_value($id);
                break;
            default:
                return \Common::format_return_result(StatusConstants::ERROR_ILLEGAL,'Invalid Method');
        }
        return \Common::format_return_result($data['code'], $data['msg'], $data['data']);
    }

}
