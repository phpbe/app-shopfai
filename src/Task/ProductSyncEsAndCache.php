<?php
namespace Be\App\Shop\Task;

use Be\App\ServiceException;
use Be\Be;
use Be\Task\TaskInterval;

/**
 * 间隔一段时间晨，定时执行 商品同步到ES和缓存
 *
 * @BeTask("商品增量同步到ES和缓存", schedule="20 * * * *")
 */
class ProductSyncEsAndCache extends TaskInterval
{

    // 时间间隔：1天
    protected $step = 86400;

    public function execute()
    {
        if (!$this->breakpoint) {
            $this->breakpoint = date('Y-m-d h:i:s', time() - $this->step);
        }

        $t0 = time();
        $t1 = strtotime($this->breakpoint);
        $t2 = $t1 + $this->step;

        if ($t1 >= $t0) return;
        if ($t2 > $t0) {
            $t2 = $t0;
        }

        $configSystemEs = Be::getConfig('App.System.Es');

        $d1 = date('Y-m-d H:i:s', $t1 - 60);
        $d2 = date('Y-m-d H:i:s', $t2);

        $service = Be::getService('App.Shop.Admin.TaskProduct');

        $db = Be::getDb();
        $sql = 'SELECT * FROM shop_product WHERE is_enable != -1 AND update_time >= ? AND update_time <= ?';
        $products = $db->getYieldObjects($sql, [$d1, $d2]);

        $batch = [];
        $i = 0;
        foreach ($products as $product) {
            $batch[] = $product;

            $i++;
            if ($i >= 100) {
                if ($configSystemEs->enable === 1) {
                    $service->syncEs($batch);
                }

                $service->syncCache($batch);

                $batch = [];
                $i = 0;
            }
        }

        if ($i > 0) {
            if ($configSystemEs->enable === 1) {
                $service->syncEs($batch);
            }

            $service->syncCache($batch);
        }

        $this->breakpoint = $d2;
    }


}
