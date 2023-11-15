<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 14:48
 */

namespace App\Api\Controllers\V1;


use App\Common\Service\V1\BaseService;
use Illuminate\Routing\Controller;
use App\Http\Controllers\ApiResponse;
use App\Jobs\CloseOrder;
use App\Models\Product;
use App\pool\redisPool;
use App\Services\PublicService;
use App\Services\ElasticsearchService;

class BaseController extends Controller
{
    public function __construct()
    {
        //定义中间件
        $this->middleware(\App\Http\Middleware\EncryptApi::class)->except('a');
        //添加访问量
        (new BaseService())->addVisit();
    }
}
