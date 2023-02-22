<?php
/**
 * User: fangcan
 * DateTime: 2023/2/22 10:46
 */

namespace App\Api\Controllers\v1;


use App\Common\Service\v1\LoginService;
use Illuminate\Http\Request;

class LoginController extends BaseController
{
    /**
     * Notes: 登录处理
     * User: fangcan
     * DateTime: 2023/2/22 11:34
     */
    public function doLogin(Request $request)
    {
        $params = $request->only(['mobile', 'openid', 'image_key', 'image_code']);
        $res = (new LoginService())->doLogin($params);
        return jsonReturn($res, 'doLogin');
    }

    /**
     * Notes: 获取图形验证码
     * User: fangcan
     * DateTime: 2023/2/22 10:50
     */
    public function getImageCode()
    {
        $res = (new LoginService())->getImageCode();
        return jsonReturn($res, 'getImageCode');
    }
}
