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
        'image_key' => 'required',
        'image_code' => 'required',
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
        'image_key.required' => 'image_key不能为空',
        'image_code.required' => 'image_code不能为空',
    ];

    /**
     * 验证场景定义
     * @var array
     */
    protected $scene = [
        'checkDoLogin' => ['mobile'],
        'checkImageCode' => ['mobile', 'image_key', 'image_code'],
    ];
}
