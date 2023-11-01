<?php
/**
 * User: fangcan
 * DateTime: 2023/2/21 17:04
 */

namespace App\Common\Service\V1;


use App\Common\Cache\BaseCache;
use App\Common\Cache\NoteCache;
use Illuminate\Support\Facades\DB;

class DataService extends BaseCache
{
    /**
     * Notes: 获取有效笔记类别列表并存入缓存
     * User: fangcan
     * DateTime: 2022/5/18 10:08
     * @return array
     */
    public function getFullNoteClassList()
    {
        $noteClassCache = new NoteCache();
        $list = $noteClassCache->getNoteClassList();
        if (empty($list)) {
            $list = DB::table('note_class')->where(['nc_status' => 1])->orderBy('nc_sort', 'desc')->get()->toArray();
            // 存缓存
            $noteClassCache->putNoteClassList($list);
        }
        return $list;
    }
}
