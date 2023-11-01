<?php

namespace App\Api\Controllers\V1;


use App\Common\Service\V1\UserService;
use Illuminate\Http\Request;

class UserController extends CommonController
{
    /**
     * Notes: 获取用户信息
     * UserController: fangcan
     * DateTime: 2023/2/20 11:34
     */
    public function getUserInfo(Request $request)
    {
        $params = $request->only(['user_id', 'mobile']);
        $res = (new UserService())->getUserInfo($params);
        return jsonReturn($res, 'getUserInfo');
    }
}
