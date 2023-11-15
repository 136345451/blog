<?php
/**
 * User: fangcan
 * DateTime: 2023/2/20 16:03
 */

namespace App\Common\Service\V1;


use App\Jobs\IncVisitProduct;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\DB;

class BaseService
{
    use DispatchesJobs;

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

    /**
     * Notes: 添加访问量
     * User: fangcan
     * DateTime: 2023/11/2 19:38
     */
    public function addVisit()
    {
        try {
            $data = [
                'visit_ip' => request()->ip(),
                'visit_year' => date('Y'),
                'visit_month' => date('m'),
                'visit_day' => date('d'),
                'visit_time' => time(),
            ];

            // 派遣
            $result = $this->dispatch(new IncVisitProduct($data));
            _logs('添加访问量', ['params' => $data ?? [], 'result' => $result ?? []], 'base/addVisit');

            if(!$result){
                _logs('添加访问量-消息添加失败', ['params' => $data ?? [], 'result' => $result ?? []], 'base/addVisit');
                return ['code' => 1001, 'msg' => '失败'];
            }

            _logs('添加访问量-消息添加成功', ['params' => $data ?? [], 'result' => $result ?? []], 'base/addVisit');
            return ['code' => 1000, 'msg' => '成功'];
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('exception_msg')];
        }
    }
}
