<?php

namespace Be\App\ShopFai\Service\Admin;

use Be\App\ServiceException;
use Be\Be;
use Be\Util\File\FileSize;
use Be\Util\File\Mime;
use Be\Util\Net\Curl;
use Be\Util\Net\FileUpload;


/**
 * 商品 计划任务
 */
class TaskProduct
{

    /**
     * 同步到 ES
     *
     * @param array $products
     */
    public function syncEs(array $products)
    {
        if (count($products) === 0) return;

        $config = Be::getConfig('App.ShopFai.Es');

        $db = Be::getDb();

        $batch = [];
        foreach ($products as $product) {
            $product->is_enable = (int)$product->is_enable;

            // 采集的商品，不处理
            if ($product->is_enable === -1) {
                continue;
            }

            $batch[] = [
                'index' => [
                    '_index' => $config->indexProduct,
                    '_id' => $product->id,
                ]
            ];

            $product->is_delete = (int)$product->is_delete;

            if ($product->is_delete === 1) {
                $batch[] = [
                    'id' => $product->id,
                    'is_delete' => true
                ];
            } else {
                $categories = [];
                $sql = 'SELECT category_id FROM shopfai_product_category WHERE product_id = ?';
                $categoryIds = $db->getValues($sql, [$product->id]);
                if (count($categoryIds) > 0) {
                    $sql = 'SELECT id, `name` FROM shopfai_category WHERE id IN (\'' . implode('\',\'', $categoryIds) . '\')';
                    $categories = $db->getObjects($sql);
                }

                $sql = 'SELECT tag FROM shopfai_product_tag WHERE product_id = ?';
                $tags = $db->getValues($sql, [$product->id]);

                $sql = 'SELECT small, medium, large, is_main, `ordering`  FROM shopfai_product_image WHERE product_id = ? ORDER BY `ordering` ASC';
                $images = $db->getObjects($sql, [$product->id]);
                foreach ($images as &$image) {
                    $image->is_main = $image->is_main ? true : false;
                    $image->ordering = (int)$image->ordering;
                }
                unset($image);

                $sql = 'SELECT id, image, sku, barcode, style, price, original_price, weight, weight_unit, stock FROM shopfai_product_item WHERE product_id = ?';
                $items = $db->getObjects($sql, [$product->id]);
                foreach ($items as &$item) {
                    $item->price = (float)$item->price;
                    $item->original_price = (float)$item->original_price;
                    $item->weight = (float)$item->weight;
                    $item->stock = (int)$item->stock;
                }
                unset($item);

                $batch[] = [
                    'id' => $product->id,
                    'spu' => $product->spu,
                    'name' => $product->name,
                    'summary' => $product->summary,
                    'url' => $product->url,
                    'categories' => $categories,
                    'brand' => $product->brand,
                    'tags' => $tags,
                    'style' => (int)$product->style,
                    'stock_tracking' => (int)$product->stock_tracking,
                    'stock_out_action' => (int)$product->stock_out_action,
                    'ordering' => (int)$product->ordering,
                    'hits' => (int)$product->hits,
                    'sales_volume' => (int)$product->sales_volume_base + (int)$product->sales_volume,
                    'price_from' => (float)$product->price_from,
                    'price_to' => (float)$product->price_to,
                    'original_price_from' => (float)$product->original_price_from,
                    'original_price_to' => (float)$product->original_price_to,
                    'rating_sum' => (int)$product->rating_sum,
                    'rating_count' => (int)$product->rating_count,
                    'rating_avg' => (float)$product->rating_avg,
                    'is_enable' => $product->is_enable === 1,
                    'is_delete' => $product->is_delete === 1,
                    'create_time' => $product->create_time,
                    'update_time' => $product->update_time,
                    'images' => $images,
                    'items' => $items,
                ];
            }
        }

        if (count($batch) > 0) {
            $es = Be::getEs();
            $response = $es->bulk(['body' => $batch]);
            if ($response['errors'] > 0) {
                $reason = '';
                if (isset($response['items']) && count($response['items']) > 0) {
                    foreach ($response['items'] as $item) {
                        if (isset($item['index']['error']['reason'])) {
                            $reason = $item['index']['error']['reason'];
                            break;
                        }
                    }
                }
                throw new ServiceException('商品同步到ES出错：' . $reason);
            }
        }
    }

    /**
     * 同步到 缓存
     *
     * @param array $products
     */
    public function syncCache(array $products)
    {
        if (count($products) === 0) return;

        $db = Be::getDb();
        $cache = Be::getCache();

        $keyValues = [];
        foreach ($products as $product) {

            $product->is_enable = (int)$product->is_enable;

            // 采集的商品，不处理
            if ($product->is_enable === -1) {
                continue;
            }

            $product->is_delete = (int)$product->is_delete;

            $key = 'ShopFai:Product:' . $product->id;
            if ($product->is_delete === 1) {
                $cache->delete($key);
            } else {

                $product->url_custom = (int)$product->url_custom;
                $product->seo_title_custom = (int)$product->seo_title_custom;
                $product->seo_description_custom = (int)$product->seo_description_custom;
                $product->style = (int)$product->style;
                $product->stock_tracking = (int)$product->stock_tracking;
                $product->stock_out_action = (int)$product->stock_out_action;
                $product->ordering = (int)$product->ordering;
                $product->hits = (int)$product->hits;
                $product->sales_volume = (int)$product->sales_volume_base + (int)$product->sales_volume;
                $product->rating_sum = (int)$product->rating_sum;
                $product->rating_count = (int)$product->rating_count;
                $product->is_enable = (int)$product->is_enable;

                if ($product->relate_id !== '') {
                    $sql = 'SELECT * FROM shopfai_product_relate WHERE id = ?';
                    $relate = $db->getObject($sql, [$product->relate_id]);

                    $sql = 'SELECT * FROM shopfai_product_relate_detail WHERE relate_id = ? ORDER BY ordering ASC';
                    $details = $db->getObjects($sql, [$product->relate_id]);
                    foreach ($details as &$detail) {
                        $sql = 'SELECT `name` FROM shopfai_product WHERE id = ?';
                        $detail->product_name = $db->getValue($sql, [$detail->product_id]);
                    }
                    unset($detail);

                    $relate->details = $details;

                    $product->relate = $relate;
                }

                $sql = 'SELECT * FROM shopfai_product_image WHERE product_id = ? ORDER BY ordering ASC';
                $images = $db->getObjects($sql, [$product->id]);
                foreach ($images as $image) {
                    $image->is_main = (int)$image->is_main;
                    $image->ordering = (int)$image->ordering;
                }
                $product->images = $images;

                $sql = 'SELECT category_id FROM shopfai_product_category WHERE product_id = ?';
                $categoryIds = $db->getValues($sql, [$product->id]);
                if (count($categoryIds) > 0) {
                    $product->category_ids = $categoryIds;

                    $sql = 'SELECT * FROM shopfai_category WHERE id IN (?)';
                    $categories = $db->getObjects($sql, ['\'' . implode('\',\'', $categoryIds) . '\'']);
                    foreach ($categories as $category) {
                        $category->ordering = (int)$category->ordering;
                    }
                    $product->categories = $categories;
                } else {
                    $product->category_ids = [];
                    $product->categories = [];
                }

                $sql = 'SELECT tag FROM shopfai_product_tag WHERE product_id = ?';
                $product->tags = $db->getValues($sql, [$product->id]);

                $sql = 'SELECT * FROM shopfai_product_style WHERE product_id = ?';
                $styles = $db->getObjects($sql, [$product->id]);
                $product->styles = $styles;

                $sql = 'SELECT * FROM shopfai_product_item WHERE product_id = ?';
                $items = $db->getObjects($sql, [$product->id]);
                foreach ($items as $item) {
                    $item->stock = (int)$item->stock;
                }
                $product->items = $items;

                $keyValues[$key] = $product;
            }
        }

        if (count($keyValues) > 0) {
            $cache->setMany($keyValues);
        }
    }

    /**
     * 下载商品信息中的远程文件
     *
     * @param object $product
     * @return void
     */
    public function downloadRemoteFile(object $product)
    {
        $storage = Be::getStorage();
        $storageRootUrl = $storage->getRootUrl();
        $storageRootUrlLen = strlen($storageRootUrl);

        $db = Be::getDb();
        $now = date('Y-m-d H:i:s');

        try {

            $imageKeyValues = [];

            $images = $db->getObjects('SELECT * FROM shopfai_product_image WHERE `product_id`=?', [$product->id]);
            foreach ($images as $image) {
                $remoteImage = trim($image->original);
                if ($remoteImage !== '') {
                    if (strlen($remoteImage) < $storageRootUrlLen || substr($remoteImage, $storageRootUrlLen) !== $storageRootUrl) {
                        $storageImage = false;
                        try {
                            $storageImage = $this->uploadRemoteFile('/products/' . $product->id. '/', $remoteImage);
                        } catch (\Throwable $t) {
                        }

                        if ($storageImage) {
                            $imageKeyValues[$image->original] = $storageImage;

                            $obj = new \stdClass();
                            $obj->id = $image->id;
                            $obj->small = $storageImage;
                            $obj->medium = $storageImage;
                            $obj->large = $storageImage;
                            $obj->original = $storageImage;
                            $obj->update_time = $now;
                            $db->update('shopfai_product_image', $obj, 'id');
                        }
                    }
                }
            }

            $product->style = (int) $product->style;

            // 多款式SKU
            if ($product->style === 2) {
                $items = $db->getObjects('SELECT * FROM shopfai_product_item WHERE `product_id`=?', [$product->id]);
                foreach ($items as $item) {
                    $remoteImage = trim($item->image);
                    if ($remoteImage !== '') {
                        if (strlen($remoteImage) < $storageRootUrlLen || substr($remoteImage, $storageRootUrlLen) !== $storageRootUrl) {
                            $storageImage = false;
                            if (isset($imageKeyValues[$remoteImage])) {
                                $storageImage = $imageKeyValues[$remoteImage];
                            } else {
                                try {
                                    $storageImage = $this->uploadRemoteFile('/products/' . $product->id. '/', $remoteImage);
                                } catch (\Throwable $t) {
                                }
                            }

                            if ($storageImage) {
                                $obj = new \stdClass();
                                $obj->id = $item->id;
                                $obj->image = $storageImage;
                                $obj->update_time = $now;
                                $db->update('shopfai_product_item', $obj, 'id');
                            }
                        }
                    }
                }
            }

            if ($product->relate_id !== '') {
                $productRelateDetail = $db->getObject('SELECT * FROM shopfai_product_relate_detail WHERE relate_id=? AND product_id=?', [$product->relate_id, $product->id]);
                if ($productRelateDetail) {
                    $remoteImage = trim($productRelateDetail->icon_image);
                    if ($remoteImage !== '') {
                        if (strlen($remoteImage) < $storageRootUrlLen || substr($remoteImage, $storageRootUrlLen) !== $storageRootUrl) {
                            $storageImage = false;
                            if (isset($imageKeyValues[$remoteImage])) {
                                $storageImage = $imageKeyValues[$remoteImage];
                            } else {
                                try {
                                    $storageImage = $this->uploadRemoteFile('/products/' . $product->id. '/', $remoteImage);
                                } catch (\Throwable $t) {
                                }
                            }

                            if ($storageImage) {
                                $obj = new \stdClass();
                                $obj->id = $item->id;
                                $obj->icon_image = $storageImage;
                                $obj->update_time = $now;
                                $db->update('shopfai_product_relate_detail', $obj, 'id');
                            }
                        }
                    }
                }
            }

            $db->query('UPDATE shopfai_product SET download_remote=2, update_time=\'' . date('Y-m-d H:i:s') . '\' WHERE `id`=\'' . $product->id . '\'');

        } catch (\Throwable $t) {
            Be::getLog()->error($t);
            $db->query('UPDATE shopfai_product SET download_remote=-1, update_time=\'' . date('Y-m-d H:i:s') . '\' WHERE `id`=\'' . $product->id . '\'');
            throw new ServiceException('导入采集的商品下载远程图像时发生异常！');
        }

    }

    /**
     * 上传远程文件
     *
     * @param string $dir 目录
     * @param string $remoteFile 远端文件
     * @return string 上传成功的CDN网址
     * @throws \Be\Runtime\RuntimeException
     */
    public function uploadRemoteFile(string $dir, string $remoteFile, bool $image = false): string
    {
        // 示例：https://cdn.shopify.com/s/files/1/0139/8942/products/Womens-Zamora-Jogger-Scrub-Pant_martiniolive-4.jpg
        $remoteFile = trim($remoteFile);

        $name = substr($remoteFile, strrpos($remoteFile, '/') + 1);
        $name = trim($name);

        $defaultExt = strrchr($name, '.');
        if ($defaultExt && strlen($defaultExt) > 1) {
            $defaultExt = substr($defaultExt, 1);
            $defaultExt = strtolower($defaultExt);
            $defaultExt = trim($defaultExt);
        } else {
            $defaultExt = '';
        }

        $tmpDir = Be::getRuntime()->getRootPath() . '/data/tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
            chmod($tmpDir, 0777);
        }
        $tmpFile = $tmpDir . uniqid(date('Ymdhis') . '-' . rand(1, 999999) . '-', true);

        $fileData = null;
        $success = false;
        $n = 0;
        do {
            $n++;
            try {
                $fileData = Curl::get($remoteFile);
                $success = true;
            } catch (\Throwable $t) {
                if (Be::getRuntime()->isSwooleMode()) {
                    \Swoole\Coroutine::sleep(rand(1, 3));
                } else {
                    sleep(rand(1, 3));
                }
            }
        } while ($success === false && $n < 3);

        if (!$success) {
            throw new ServiceException('获取远程文件（' . $remoteFile . '）失败！');
        }

        file_put_contents($tmpFile, $fileData);

        try {
            $configSystem = Be::getConfig('App.System.System');
            $maxSize = $configSystem->uploadMaxSize;
            $maxSizeInt = FileSize::string2Int($maxSize);
            $size = filesize($tmpFile);
            if ($size > $maxSizeInt) {
                throw new ServiceException('您上传的文件尺寸已超过最大限制：' . $maxSize . '！');
            }

            $ext = Mime::detectExt($tmpFile, $defaultExt);

            $configSystem = Be::getConfig('App.System.System');
            if ($image) {
                if (!in_array($ext, $configSystem->allowUploadImageTypes)) {
                    throw new ServiceException('禁止上传的图像类型：' . $ext . '！');
                }
            } else {
                if (!in_array($ext, $configSystem->allowUploadFileTypes)) {
                    throw new ServiceException('禁止上传的文件类型：' . $ext . '！');
                }
            }

            $configCollectProductApi = Be::getConfig('App.ShopFai.CollectProductApi');

            $newName = null;
            switch ($configCollectProductApi->downloadRemoteFileRename) {
                case 'orginal':
                    $newName = $name;
                    break;
                case 'md5':
                    $newName = md5_file($tmpFile) . '.' . $ext;
                    break;
                case 'sha1':
                    $newName = sha1_file($tmpFile) . '.' . $ext;
                    break;
                case 'timestamp':
                    $newName = uniqid(date('Ymdhis') . '-' . rand(1, 999999) . '-', true) . '.' . $ext;
                    break;
            }

            $storage = Be::getStorage();
            $object = $dir . $newName;
            if ($storage->isFileExist($object)) {
                $url = $storage->getFileUrl($object);
            } else {
                $url = $storage->uploadFile($object, $tmpFile);
            }

        } catch (\Throwable $t) {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            Be::getLog()->warning($t);

            throw $t;
        }

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        return $url;
    }
}