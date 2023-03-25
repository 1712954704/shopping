<?php

/*** Common ***/
use App\Http\Controllers\Common\DepartmentController;
use App\Http\Controllers\Common\FileController;
use App\Http\Controllers\Common\PositionController;
use App\Http\Controllers\Common\UserController;
use App\Http\Controllers\Common\AuthController;
use App\Http\Controllers\Common\RoleController;
use \App\Http\Controllers\Common\NoticeController;
use \App\Http\Controllers\Common\CalendarController;
use \App\Http\Controllers\Common\DictionaryController;
/*** Hr ***/
use App\Http\Controllers\Hr\StructureController;
use \App\Http\Controllers\Hr\ApprovalController;
use App\Http\Controllers\Hr\AssessmentController;
use \App\Http\Controllers\Hr\QuarterSummaryController;
/*** web_hook ***/
use App\Http\Controllers\WebHookController;
/*** system ***/
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::controller(UserController::class)->group(function () {
    Route::post('/user/login', 'login');   // 登录
    Route::get('/user/info', 'user_info');   // 获取用户信息
    Route::post('/user/register', 'user_register_or_edit');   // 用户新增
    Route::post('/user/clear_user_lock', 'clear_user_lock');   // 清除用户锁定
    Route::get('/user/list', 'user_list');   // 获取用户列表
    Route::post('/user/user_reset', 'user_reset');   // 重置用户缓存信息
    Route::put('/user/edit', 'user_register_or_edit');   // 编辑用户
    Route::delete('/user/del', 'user_del');   // 删除用户
    Route::post('/user/reset_pwd', 'user_reset_pwd');   // 用户重置密码
    Route::post('/user/reset_password', 'reset_password');   // 邮件重置密码
    Route::get('/nation_list', 'get_nation_list');   // 获取民族列表
    Route::get('/user/get_new_staff_list', 'get_new_staff_list');   // 获取新员工介绍
    Route::get('/user/get_staff_found_info', 'get_staff_found_info');   // 获取员工分布信息
    Route::post('/user/update_structure_data', 'update_structure_data');   // 更新部门
    Route::post('/user/user_reset_default_pwd', 'user_reset_default_pwd');   // 重置用户密码为默认密码
});

Route::controller(AuthController::class)->group(function () {
    Route::get('/auth/rule', 'rule_operate');   // 获取路由列表
    Route::post('/auth/rule', 'rule_operate');  // 添加路由规则
    Route::put('/auth/rule', 'rule_operate');   // 修改路由规则
    Route::delete('/auth/rule', 'rule_operate');// 删除路由规则
    Route::get('/auth/system_info', 'get_system_info');// 获取系统信息
    Route::put('/auth/batch_update_auth', 'batch_update_auth');// 批量更新系统信息
    Route::get('/auth/get_route_way_info', 'get_route_way_info');// 获取单一路径信息
});


Route::controller(RoleController::class)->group(function () {
    Route::get('/auth/role', 'role_operate');   // 获取角色列表
    Route::post('/auth/role', 'role_operate');  // 添加角色
    Route::put('/auth/role', 'role_operate');   // 修改角色信息
    Route::delete('/auth/role', 'role_operate');   // 删除角色信息
    Route::post('/auth/change_user_role', 'change_user_role');   // 添加角色用户关联
    Route::post('/auth/change_role_auth', 'change_role_auth');   // 添加角色权限关联
//    Route::put('/auth/change_user_role', 'change_user_role');   // 修改用户角色关联 已废弃
});

// 组织架构相关
Route::controller(StructureController::class)->group(function () {
    Route::get('/structure', 'structure_operate');   // 获取组织架构列表
    Route::post('/structure', 'structure_operate');   // 添加组织
    Route::put('/structure', 'structure_operate');   // 更新组织
    Route::delete('/structure', 'structure_operate');   // 删除组织架构
    Route::get('/region', 'get_region');   // 获取地区信息
    Route::get('/structure/group', 'get_group_list');   // 获取上级单位信息
//    Route::get('/structure/tree_list', 'get_tree_list');   // 获取组织结构树形结构 已废弃
    Route::get('/structure/get_structure_list', 'get_structure_list');   // 搜索部门-组织结构信息
});

// 职务相关接口
Route::controller(PositionController::class)->group(function () {
    Route::get('/position', 'position_operate');   // 获取职务列表
    Route::post('/position', 'position_operate');  // 添加角色
    Route::put('/position', 'position_operate');   // 修改角色信息
    Route::delete('/position', 'position_operate');   // 删除角色信息
//    Route::post('/auth/change_user_role', 'change_user_role');   // 更改角色用户关联
});

// 审核流程相关接口
Route::controller(ApprovalController::class)->group(function () {
    Route::get('/approval/flow_log', 'flow_log');   // 获取流转记录
    Route::get('/approval/await_approval_list', 'await_approval_list');   // 待我审批列表
    Route::post('/approval/approval_flow', 'approval_flow');   // 流程流转
    Route::get('/approval/approval_copy_list', 'approval_copy_operate');   // 我的抄送列表
    Route::post('/approval/approval_copy_add', 'approval_copy_operate');   // 添加抄送
    Route::post('/approval/approval_copy_check', 'approval_copy_check');   // 抄送查阅
});

// 系统公告相关接口
Route::controller(NoticeController::class)->group(function () {
    Route::get('/notice', 'notice_operate');   // 查看系统公告
    Route::post('/notice', 'notice_operate');   // 添加系统公告
    Route::put('/notice', 'notice_operate');   // 编辑系统公告
    Route::delete('/notice', 'notice_operate');   // 删除系统公告
});

// 日程安排相关接口
Route::controller(CalendarController::class)->group(function () {
    Route::get('/calendar', 'calendar_operate');   // 查看日程安排
    Route::post('/calendar', 'calendar_operate');   // 添加日程安排
    Route::put('/calendar', 'calendar_operate');   // 编辑日程安排
    Route::delete('/calendar', 'calendar_operate');   // 删除日程安排
});

// 季度会议相关接口
Route::controller(QuarterSummaryController::class)->group(function () {
    Route::get('/quarter_summary', 'quarter_summary_operate');   // 查看会议纪要
    Route::post('/quarter_summary', 'quarter_summary_operate');   // 添加会纪要
    Route::put('/quarter_summary', 'quarter_summary_operate');   // 编辑季度纪要
    Route::delete('/quarter_summary', 'quarter_summary_operate');   // 删除季度纪要
});

// 数据字典相关接口
Route::controller(DictionaryController::class)->group(function () {
    Route::get('/dictionary_type', 'dictionary_type_operate');   // 获取字典类型
    Route::post('/dictionary_type', 'dictionary_type_operate');   // 添加字典类型
    Route::put('/dictionary_type', 'dictionary_type_operate');   // 编辑字典类型
    Route::delete('/dictionary_type', 'dictionary_type_operate');   // 删除字典类型
    Route::get('/dictionary_value', 'dictionary_value_operate');   // 获取字典类型值
    Route::post('/dictionary_value', 'dictionary_value_operate');   // 添加字典类型值
    Route::put('/dictionary_value', 'dictionary_value_operate');   // 编辑字典类型值
    Route::delete('/dictionary_value', 'dictionary_value_operate');   // 删除字典类型值
});

// 文件上传
Route::controller(FileController::class)->group(function () {
    Route::post('/file/add_file', 'add_file');   // 文件上传
});

// 回调相关
Route::controller(WebHookController::class)->group(function () {
    Route::post('/web_hook', 'run');   // web_hook接口
});

// rabbitmq相关
Route::controller(\App\Http\Controllers\Common\RabbitmqController::class)->group(function () {
    Route::post('/rabbitmq', 'rabbitmq_operate');   // web_hook接口
});


// Send Email
Route::get('/email_log', [AssessmentController::class, 'email_log']);
Route::post('/send_email', [AssessmentController::class, 'send_email']);


Route::apiResource('ding', \App\Http\Controllers\Common\DingController::class);
Route::apiResource('department', \App\Http\Controllers\Common\DepartmentController::class);
Route::apiResource("hr/check", \App\Http\Controllers\Hr\CheckController::class);
Route::apiResource("hr/assessment", \App\Http\Controllers\Hr\AssessmentController::class);


Route::controller(AssessmentController::class)->group(function () {
    Route::get('/hr/assessment_user', 'index');   // 修改考评信息
    Route::put('/hr/assessment', 'renewal');   // 修改考评信息
    Route::delete('/hr/assessment', 'remove');   // 删除考评信息
});
Route::controller(DepartmentController::class)->group(function () {
    Route::put('/department', 'renewal');   // 修改部门信息
    Route::delete('/department', 'remove');   // 删除部门信息
});
