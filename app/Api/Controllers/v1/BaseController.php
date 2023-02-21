<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 14:48
 */

namespace App\Api\Controllers\v1;

use Illuminate\Routing\Controller;

class BaseController extends Controller
{
    public function __construct()
    {
        //定义中间件
        $this->middleware(\App\Http\Middleware\EncryptApi::class)->except('a');
    }
}
