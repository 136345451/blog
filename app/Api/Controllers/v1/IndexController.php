<?php
/**
 * User: fangcan
 * DateTime: 2023/2/21 16:50
 */

namespace App\Api\Controllers\v1;


use App\Common\Service\v1\IndexService;
use Illuminate\Http\Request;

class IndexController extends CommonController
{
    /**
     * Notes: 获取笔记类型列表
     * User: fangcan
     * DateTime: 2023/2/21 16:58
     */
    public function getNoteClassList()
    {
        $res = (new IndexService())->getNoteClassList();
        return jsonReturn($res, 'getNoteClassList');
    }

    /**
     * Notes: 加锁示例
     * User: fangcan
     * DateTime: 2023/2/22 10:13
     */
    public function getLock(Request $request)
    {
        $params = $request->only(['user_id', 'mobile']);
        $res = (new IndexService())->getLock($params);
        return jsonReturn($res, 'getLock');
    }
}
