<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Service\Common\DepartmentService;
use App\Models\Common\Structure;
use Common;
use Illuminate\Http\Request;
use library\Constants\StatusConstants;

class DepartmentController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pid        = request('pid') ?? 0;
        $id         = request('id');
        $data       = request('data')??"";
        $group_type = request('group_type');
        $page       = request('page') ?? 1;
        $limit      = request('limit') ?? 10;
        $offset     = ($page - 1) * $limit;
        $interface  = request('interface');

        // Sever层处理页面逻辑
        $Service = new DepartmentService();
        $result  = $Service->index($interface, $pid, $id, $group_type, $limit, $offset,$data);

        return \Common::format_return_result($result['code'], $result['msg'], $result['data']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data['user_id']       = request('user_id');
        $data['data']          = request('data');
        $data['data']['order'] = \Common::check_empty($data['data']['order'], 2);
        // Sever层处理页面逻辑
        $Service = new DepartmentService();
        $result  = $Service->store($data);
        return Common::format_return_result($result['code'], $result['msg'], $result['data']);
    }

    /**
     * update and renewal
     *
     * @param int $id
     * @return Response
     */
    public function renewal()
    {
        $data['user_id'] = request('user_id');
        $data['data']    = request('data');


        // Sever层处理页面逻辑
        $Service = new DepartmentService();
        $result  = $Service->renewal($data);

        return Common::format_return_result($result['code'], $result['msg'], $result['data']);

    }

    /**
     * @return array
     */
    public function statistics()
    {
        $data['user_id'] = request('user_id');
        $data['data']    = request('data');
        // Sever层处理页面逻辑
        $Service = new DepartmentService();
        $result  = $Service->statistics($data);
        return Common::format_return_result($result['code'], $result['msg'], $result['data']);
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $result = Structure::where('id', '=', $id)
            ->select('id', 'name', 'pid', 'code', 'order', 'group_type', 'leader')
            ->first();


        $response['code'] = $result->id > 0 ? '200' : '404';
        $response['msg']  = $result->id > 0 ? 'success' : '数据不存在';
        $response['data'] = $result;

        return \Common::format_return_result($response['code'], $response['msg'], $response['data']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {


        try {
            // 验证...

            $pid    = $request->input('pid');
            $name   = $request->input('name');
            $status = $request->input('status');
            $order  = $request->input('order');
            $code   = $request->input('code');
            $leader = $request->input('leader');

            $manager_id = $this->data_arr['manager_id'] ?? null;
            $handler_id = $this->data_arr['handler_id'] ?? null;
            $duty_id    = $this->data_arr['duty_id'] ?? null;

            $data_update = [
                'name'   => $name,
                'status' => $status,
                'pid'    => $pid,
                'code'   => $code,
                'order'  => $order,
                'leader' => $leader,
            ];

            if ($manager_id) {
                $data_update['manager_id'] = $manager_id;
            }
            if ($manager_id) {
                $data_update['handler_id'] = $handler_id;
            }
            if ($manager_id) {
                $data_update['duty_id'] = $duty_id;
            }

//            $result = Structure::where('id', $id)
//                ->update([
//                    'name'   => $name,
//                    'status' => $status,
//                    'pid'    => $pid,
//                    'code'   => $code,
//                    'order'  => $order,
//                    'leader' => $leader,
//                ]);

            $result = Structure::where('id', $id)
                ->update($data_update);

            $response['code'] = $result > 0 ? '200' : '404';
            $response['msg']  = $result > 0 ? 'success' : '更新失败';
            $response['data'] = $result;

            return \Common::format_return_result($response['code'], $response['msg'], $response['data']);


        } catch (\Exception $e) {

            $response['code'] = StatusConstants::ERROR_DATA_NUMERIC_VALUE_EXIST;
            return \Common::format_return_result($response['code'], '', '');;
        }


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = Structure::where('id', $id)->delete();

        return Common::format_return_result($result['code'], $result['msg'], $result['data']);

    }

    public function remove()
    {
        $id = request('id');


        $result = Structure::destroy($id);


        $response['code'] = $result > 0 ? '200' : '404';
        $response['msg']  = $result > 0 ? 'success' : '数据不存在';
        $response['data'] = $result;

        return json_encode($response);
    }

}
