<?php
/**
 * User: fangcan
 * DateTime: 2023/2/21 16:59
 */

namespace App\Common\Cache;



use Illuminate\Support\Facades\Cache;

class BaseCache
{
    //公共接口锁，默认锁定120秒。
    protected $lockKey = 'lock';
    public function getLock($name)
    {
        return Cache::increment($this->lockKey . ':' . $name);
    }
    public function putLock($name, $lock_time = 120)
    {
        return Cache::put($this->lockKey . ':' . $name, 1, $lock_time);
    }
    public function pullLock($name)
    {
        return Cache::pull($this->lockKey . ':' . $name);
    }
}
