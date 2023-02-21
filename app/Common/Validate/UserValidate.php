<?php
/**
 * User: fangcan
 * DateTime: 2023/2/21 14:35
 */

namespace App\Common\Validate;


class UserValidate extends BaseValidate
{
    /**
     * 当前验证规则
     * @var array
     */
    protected $rule = [
        'user_id' => 'required|integer',
    ];

    /**
     * 验证提示信息
     * @var array
     */
    protected $message = [
        'user_id.required' => '用户ID不能为空',
        'user_id.integer' => '用户ID异常',
    ];

    /**
     * 验证场景定义
     * @var array
     */
    protected $scene = [
        'checkGetUserInfo' => ['user_id'],
    ];
}
