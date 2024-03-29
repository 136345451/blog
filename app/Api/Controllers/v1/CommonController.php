<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 14:47
 */

namespace App\Api\Controllers\V1;


class CommonController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        //定义中间件
        $this->middleware(\App\Http\Middleware\AuthApi::class)->except('getNoteClassList');
    }
}
