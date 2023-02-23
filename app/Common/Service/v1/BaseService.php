<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 16:03
 */

namespace App\Common\Service\v1;


use Illuminate\Support\Facades\DB;

class BaseService
{
    /**
     * Notes: 获取用户信息
     * User: fangcan
     * DateTime: 2023/2/20 16:06
     * @param string $user_iden 用户标识
     * @param string $user_iden_key 标识字段名称
     */
    public function _getUserInfo($user_iden, $user_iden_key = 'user_id', $table_name = 'user')
    {
        $where = [$user_iden_key => $user_iden];
        $userInfo = objectToArray(DB::table($table_name)->where($where)->first());
        return $userInfo;
    }
}
