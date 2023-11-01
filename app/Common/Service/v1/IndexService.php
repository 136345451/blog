<?php
/**
 * User: fangcan
 * DateTime: 2023/2/21 16:59
 */

namespace App\Common\Service\V1;


use App\Common\Cache\BaseCache;

class IndexService extends BaseService
{
    /**
     * Notes: 获取笔记类型列表
     * User: fangcan
     * DateTime: 2023/2/21 17:00
     */
    public function getNoteClassList()
    {
        try {
            $data['noteClassList'] = (new DataService())->getFullNoteClassList();
            return ['code' => 1000, 'msg' => '成功', 'data' => $data];
        } catch (\Exception $e) {
            errorLogs($e);
            return ['code' => 1300, 'msg' => config('app.exception_msg')];
        }
    }

    /**
     * Notes: 加锁示例
     * User: fangcan
     * DateTime: 2023/2/22 10:14
     * @param int user_id 用户编号
     * @param string mobile 手机号
     */
    public function getLock($params)
    {
        try {
            // 防止重复请求验锁
            $baseCache = new BaseCache();
            $lock_name = 'getLock:' . $params['mobile'];
            if ($baseCache->getLock($lock_name) > 1) {
                return ['code' => 1600, 'msg' => '操作过于频繁，请稍后再试'];
            }
            $baseCache->putLock($lock_name);

            $baseCache->pullLock($lock_name);
            return ['code' => 1000, 'msg' => '成功'];
        } catch (\Exception $e) {
            errorLogs($e);
            if (isset($lock_name)) $baseCache->pullLock($lock_name);
            return ['code' => 1300, 'msg' => config('exception_msg')];
        }
    }
}
