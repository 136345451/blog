<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 16:03
 */

namespace App\Common\Service\v1;


use App\Common\Validate\UserValidate;

class UserService extends BaseService
{
    /**
     * Notes: 获取用户信息
     * User: fangcan
     * DateTime: 2023/2/21 14:30
     * @param $params
     * @param int user_id 用户编号
     * @param string mobile 手机号
     */
    public function getUserInfo($params)
    {
        try {
            $userValidate = new UserValidate();
            if (!$userValidate->scene('checkGetUserInfo')->check($params)){
                return ['code' => 1001, 'msg' => $userValidate->getError()];
            }
            $data['userInfo'] = $this->_getUserInfo($params['user_id']);
            return ['code' => 1000, 'msg' => '成功', 'data' => $data];
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('app.exception_msg')];
        }
    }
}
