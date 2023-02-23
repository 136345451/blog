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
     * Notes: 发送验证码
     * User: fangcan
     * DateTime: 2023/2/22 16:44
     */
    public function sendCode(Request $request)
    {
        $params['email'] = $request->post('email', '');
        $params['mobile'] = $request->post('mobile', '');
        $params['openid'] = $request->post('openid', '');
        $params['login_type'] = $request->post('login_type', 1);
        $params['image_key'] = $request->post('image_key', '');
        $params['image_code'] = $request->post('image_code', '');
        $res = (new LoginService())->sendCode($params);
        return jsonReturn($res, 'sendCode');
    }

    /**
     * Notes: 登录处理
     * User: fangcan
     * DateTime: 2023/2/22 11:34
     */
    public function doLogin(Request $request)
    {
        $params['email'] = $request->post('email', '');
        $params['mobile'] = $request->post('mobile', '');
        $params['openid'] = $request->post('openid', '');
        $params['login_type'] = $request->post('login_type', 1);
        $params['validate_code'] = $request->post('validate_code', '');
        $res = (new LoginService())->doLogin($params);
        return jsonReturn($res, 'doLogin');
    }

    /**
     * Notes: 久登录接口-获取用户token
     * User: fangcan
     * DateTime: 2023/2/23 10:04
     */
    public function getUserToken(Request $request)
    {
        $params = $request->only('openid');
        $res = (new LoginService())->getUserToken($params);
        return jsonReturn($res, 'getUserToken');
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
