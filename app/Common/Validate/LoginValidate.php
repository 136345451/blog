<?php
/**
 * User: fangcan
 * DateTime: 2023/2/22 11:43
 */

namespace App\Common\Validate;


class LoginValidate extends BaseValidate
{
    /**
     * 当前验证规则
     * @var array
     */
    protected $rule = [
        'user_id' => 'required|integer',
        'mobile' => 'required|mobile',
        'email' => 'required|email',
        'openid' => 'required',
        'image_key' => 'required',
        'image_code' => 'required',
        'validate_code' => 'required',
        'login_type' => 'required|in:1,2',
    ];

    /**
     * 验证提示信息
     * @var array
     */
    protected $message = [
        'user_id.required' => '用户ID不能为空',
        'user_id.integer' => '用户ID格式异常',
        'mobile.required' => '手机号不能为空',
        'mobile.mobile' => '手机号格式异常',
        'email.required' => '邮箱不能为空',
        'email.email' => '邮箱格式异常',
        'openid.required' => 'openid不能为空',
        'image_key.required' => '验证码标识不能为空',
        'image_code.required' => '图形验证码不能为空',
        'validate_code.required' => '验证码不能为空',
        'login_type.required' => '登录类型不能为空',
        'login_type.in' => '登录类型异常',
        'to_email.required' => '收件人邮箱不能为空',
        'to_email.email' => '收件人邮箱格式异常',
    ];

    /**
     * 验证场景定义
     * @var array
     */
    protected $scene = [
        'checkSendCode' => ['login_type', 'image_key', 'image_code'],
        'checkDoLogin' => ['login_type', 'validate_code'],
        'checkGetUserToken' => ['openid'],
        'checkImageCode' => ['image_key', 'image_code'],
        'checkSendEmailValidateCode' => ['to_email', 'validate_code'],
    ];
}
