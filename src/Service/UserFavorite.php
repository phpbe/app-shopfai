<?php

namespace Be\App\Shop\Service;

use Be\Be;

class UserFavorite
{

    /**
     * 获取用户收藏列表
     *
     * @return array
     */
    public function getProducts(): array
    {
        $my = Be::getUser();
        $products = [];

        $redis = Be::getRedis();

        $productIds = null;
        $config = Be::getConfig('App.Shop.User');
        if ($config->favoriteDrive === 'redis') {
            $productIds = $redis->sMembers('App:Shop:User:Favorite:' . $my->id);
        } else {
            $sql = 'SELECT product_id FROM shop_user_favorite WHERE user_id = ? AND is_enable = 1 AND is_delete = 0';
            $productIds = Be::getDb()->getValues($sql, [$my->id]);
        }

        foreach ($productIds as $productId) {
            $product = $redis->get('App:Shop:Product:' . $productId);
            if (!$product) {
                continue;
            }

            $product = json_decode($product, true);

            $imageUrl = '';
            foreach ($product['images'] as $image) {
                if ($image['is_main']) {
                    $imageUrl = $image['url'];
                    break;
                }
            }
            if (!$imageUrl && count($product['images']) > 0) {
                $imageUrl = $product['images'][0]['url'];
            }

            $products[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'image' => $imageUrl,
                'price' => $product['price'],
                'url' =>  beUrl('Shop.Product.detail', ['id' => $product['id']]),
            ];
        }

        return $products;
    }

    /**
     * 添加收藏
     *
     * @param string $productId
     */
    public function addFavorite(string$productId)
    {
        $my = Be::getUser();
        $config = Be::getConfig('App.Shop.User');
        if ($config->favoriteDrive === 'redis') {
            $redis = Be::getRedis();
            $redis->sAdd('App:Shop:User:Favorite:' . $my->id, $productId);
        } else {
            $tupleUserFavorite = Be::getTuple('shop_user_favorite');
            try {
                $tupleUserFavorite->load([
                    'user_id' => $my->id,
                    'product_id' => $productId,
                ]);
            } catch (\Throwable $t) {
                $tupleUserFavorite->user_id = $my->id;
                $tupleUserFavorite->product_id = $productId;
                $tupleUserFavorite->is_enable = 1;
                $tupleUserFavorite->is_delete = 0;
                $tupleUserFavorite->create_time = date('Y-m-d H:i:s');
                $tupleUserFavorite->update_time = date('Y-m-d H:i:s');
                $tupleUserFavorite->insert();
            }
        }
    }

    /**
     * 用户是否收藏指定商品
     *
     * @param string $productId
     * @return bool
     */
    public function isFavorite(string $productId): bool
    {
        $my = Be::getUser();
        $config = Be::getConfig('App.Shop.User');
        if ($config->favoriteDrive === 'redis') {
            $redis = Be::getRedis();
            return $redis->sIsMember('App:Shop:User:Favorite:' . $my->id, $productId);
        } else {
            $sql = 'SELECT COUNT(*) FROM shop_user_favorite WHERE user_id = ? AND product_id = ? AND is_enable = 1 AND is_delete = 0';
            return Be::getDb()->getValue($sql, [$my->id, $productId]) > 0;
        }
    }

    /**
     * 删除用户收藏
     *
     * @param string $productId
     */
    public function deleteFavorite(string $productId)
    {
        $my = Be::getUser();
        $config = Be::getConfig('App.Shop.User');
        if ($config->favoriteDrive === 'redis') {
            $redis = Be::getRedis();
            $redis->sRem('App:Shop:User:Favorite:' . $my->id, $productId);
        } else {
            $tupleUserFavorite = Be::getTuple('shop_user_favorite');
            try {
                $tupleUserFavorite->loadBy([
                    'user_id' => $my->id,
                    'product_id' => $productId,
                ]);

                $tupleUserFavorite->is_delete = 1;
                $tupleUserFavorite->update_time = date('Y-m-d H:i:s');
                $tupleUserFavorite->update();
            } catch (\Throwable $t) {
            }
        }
    }


}
