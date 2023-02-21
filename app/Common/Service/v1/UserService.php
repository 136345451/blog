<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 16:03
 */

namespace App\Common\Service\v1;


class UserService extends BaseService
{
    /**
     * Notes: 获取用户信息
     * User: fangcan
     * DateTime: 2023/2/20 16:06
     */
    public function getUserInfo($params)
    {
        try {
            $data = $params;
            return ['code' => 1000, 'msg' => '成功', 'data' => $data];
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('app.exception_msg')];
        }
    }
}
