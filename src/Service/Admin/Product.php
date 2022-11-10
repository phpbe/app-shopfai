<?php

namespace Be\App\ShopFai\Service\Admin;

use Be\AdminPlugin\Form\Item\FormItemSelect;
use Be\AdminPlugin\Table\Item\TableItemImage;
use Be\App\ServiceException;
use Be\Be;
use Be\Db\Tuple;

class Product
{

    /**
     * 编辑商品
     *
     * @param array $data 商品数据
     * @return Tuple
     * @throws \Throwable
     */
    public function edit(array $data): Tuple
    {
        $db = Be::getDb();

        $isNew = true;
        $productId = null;
        if (isset($data['id']) && $data['id'] !== '') {
            $isNew = false;
            $productId = $data['id'];
        }

        $tupleProduct = Be::getTuple('shopfai_product');
        if (!$isNew) {
            try {
                $tupleProduct->load($productId);
            } catch (\Throwable $t) {
                throw new ServiceException('商品（# ' . $productId . '）不存在！');
            }
        }

        if (!isset($data['spu']) || !is_string($data['spu'])) {
            $data['spu'] = '';
        }

        if ($data['spu'] !== '') {
            $exist = null;
            if ($isNew) {
                $exist = Be::getTable('shopfai_product')
                        ->where('is_delete', 0)
                        ->where('spu', $data['spu'])
                        ->getValue('COUNT(*)') > 0;
            } else {
                $exist = Be::getTable('shopfai_product')
                        ->where('is_delete', 0)
                        ->where('spu', $data['spu'])
                        ->where('id', '!=', $productId)
                        ->getValue('COUNT(*)') > 0;
            }

            if ($exist) {
                throw new ServiceException('SPU（' . $data['spu'] . '）已存在！');
            }
        }

        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new ServiceException('商品名称未填写！');
        }
        $name = $data['name'];

        if (!isset($data['summary']) || !is_string($data['summary'])) {
            $data['summary'] = '';
        }

        if (!isset($data['description']) || !is_string($data['description'])) {
            $data['description'] = '';
        }

        if (!isset($data['url_custom']) || $data['url_custom'] !== 1) {
            $data['url_custom'] = 0;
        }

        $url = null;
        if (!isset($data['url']) || !is_string($data['url'])) {
            $url = strtolower($name);
            $url = preg_replace('/[^a-z0-9]/', '-', $url);
            $url = str_replace(' ', '-', $url);
            while (strpos($url, '--') !== false) {
                $url = str_replace('--', '-', $url);
            }
            $data['url_custom'] = 0;
        } else {
            $url = $data['url'];
        }
        $urlUnique = $url;
        $urlIndex = 0;
        $urlExist = null;
        do {
            if ($isNew) {
                $urlExist = Be::getTable('shopfai_product')
                        ->where('url', $urlUnique)
                        ->where('is_delete', 0)
                        ->getValue('COUNT(*)') > 0;
            } else {
                $urlExist = Be::getTable('shopfai_product')
                        ->where('url', $urlUnique)
                        ->where('is_delete', 0)
                        ->where('id', '!=', $productId)
                        ->getValue('COUNT(*)') > 0;
            }

            if ($urlExist) {
                $urlIndex++;
                $urlUnique = $url . '-' . $urlIndex;
            }
        } while ($urlExist);
        $url = $urlUnique;

        if (!isset($data['seo_title']) || !is_string($data['seo_title'])) {
            $data['seo_title'] = '';
        }

        if (!isset($data['seo_title_custom']) || !is_numeric($data['seo_title_custom']) || $data['seo_title_custom'] !== 1) {
            $data['seo_title_custom'] = 0;
        }

        if (!isset($data['seo_description']) || !is_string($data['seo_description'])) {
            $data['seo_description'] = '';
        }

        if (!isset($data['seo_description_custom']) || !is_numeric($data['seo_description_custom']) || $data['seo_description_custom'] !== 1) {
            $data['seo_description_custom'] = 0;
        }

        if (!isset($data['seo_keywords']) || !is_string($data['seo_keywords'])) {
            $data['seo_keywords'] = '';
        }

        if (!isset($data['sales_volume_base']) || !is_numeric($data['sales_volume_base'])) {
            $data['sales_volume_base'] = 0;
        } else {
            $data['sales_volume_base'] = (int)$data['sales_volume_base'];
        }
        if ($data['sales_volume_base'] < 0) {
            $data['sales_volume_base'] = 0;
        }

        if (!isset($data['brand']) || !is_string($data['brand'])) {
            $data['brand'] = '';
        }

        if (!isset($data['stock_tracking']) || !is_numeric($data['stock_tracking'])) {
            $data['stock_tracking'] = 0;
        } else {
            $data['stock_tracking'] = (int)$data['stock_tracking'];
        }
        if ($data['stock_tracking'] !== 0 && $data['stock_tracking'] !== 1) {
            $data['stock_tracking'] = 0;
        }

        if (!isset($data['stock_out_action']) || !is_numeric($data['stock_out_action'])) {
            $data['stock_out_action'] = 1;
        } else {
            $data['stock_out_action'] = (int)$data['stock_out_action'];
        }
        if (!in_array($data['stock_out_action'], [-1, 0, 1])) {
            $data['stock_out_action'] = 1;
        }

        if (!isset($data['related']) || !is_numeric($data['related'])) {
            $data['related'] = 0;
        } else {
            $data['related'] = (int)$data['related'];
            if ($data['related'] !== 0 && $data['related'] !== 1) {
                $data['related'] = 0;
            }
        }
        // 关联商品
        if ($data['related'] === 1) {
            if (!isset($data['relate']) || !is_array($data['relate'])) {
                throw new ServiceException('商品关联数据缺失！');
            }

            if (!isset($data['relate']['id']) || !is_string($data['relate']['id'])) {
                $data['relate']['id'] = '';
            }

            if (!isset($data['relate']['name']) || !is_string($data['relate']['name'])) {
                throw new ServiceException('商品关联的名称缺失！');
            } else {
                $data['relate']['name'] = trim($data['relate']['name']);
                if ($data['relate']['name'] === '') {
                    throw new ServiceException('商品关联的名称不能为空！');
                }
            }

            if (!isset($data['relate']['icon_type']) || !is_string($data['relate']['icon_type'])) {
                $data['relate']['icon_type'] = 'text';
            }

            if (!in_array($data['relate']['icon_type'], ['text', 'image', 'color'])) {
                $data['relate']['icon_type'] = 'text';
            }

            if (!isset($data['relate']['details']) || !is_array($data['relate']['details'])) {
                throw new ServiceException('商品关联明细数据缺失！');
            }

            $i = 1;
            foreach ($data['relate']['details'] as &$detail) {
                if (!isset($detail['id']) || !is_string($detail['id'])) {
                    $detail['id'] = '';
                }

                if (!isset($detail['product_id']) || !is_string($detail['product_id'])) {
                    throw new ServiceException('商品关联第' . $i . '项的商品ID缺失！');
                }

                if (!isset($detail['value']) || !is_string($detail['value'])) {
                    throw new ServiceException('商品关联第' . $i . '项的值缺失！');
                }

                if (!isset($detail['icon_image']) || !is_string($detail['icon_image'])) {
                    $detail['icon_image'] = '';
                }

                if (!isset($detail['icon_color']) || !is_string($detail['icon_color'])) {
                    $detail['icon_color'] = '';
                }
                $i++;
            }
            unset($detail);
        }

        $style = isset($data['style']) ? ((int)$data['style']) : 1;
        if ($style !== 1 && $style !== 2) {
            $style = 1;
        }

        if ($style === 2) {
            if (!isset($data['styles']) || !is_array($data['styles']) || count($data['styles']) === 0) {
                throw new ServiceException('多款式数据缺失！');
            }

            $validStyles = 0;
            $styles = $data['styles'];
            foreach ($styles as $s) {
                if (!isset($s['name']) || !$s['name']) continue;
                if (!isset($s['values']) || !is_array($s['values']) || count($s['values']) === 0) continue;
                $validStyles++;
            }

            if ($validStyles === 0) {
                throw new ServiceException('请配置多款式！');
            }
        }

        if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
            throw new ServiceException('商品款式子项数据缺失！');
        }
        $items = $data['items'];

        $priceFrom = null;
        $priceTo = null;
        $originalPriceFrom = null;
        $originalPriceTo = null;
        foreach ($items as $item) {
            $price = $item['price'] ?? '0';
            $originalPrice = $item['original_price'] ?? '0';

            if ($priceFrom === null) {
                $priceFrom = $price;
            } else {
                if (bccomp($priceFrom, $price, 2) === 1) {
                    $priceFrom = $price;
                }
            }

            if ($priceTo === null) {
                $priceTo = $price;
            } else {
                if (bccomp($priceTo, $price) === -1) {
                    $priceTo = $price;
                }
            }

            if ($originalPriceFrom === null) {
                $originalPriceFrom = $originalPrice;
            } else {
                if (bccomp($originalPriceFrom, $originalPrice, 2) === 1) {
                    $originalPriceFrom = $originalPrice;
                }
            }

            if ($originalPriceTo === null) {
                $originalPriceTo = $originalPrice;
            } else {
                if (bccomp($originalPriceTo, $originalPrice, 2) === -1) {
                    $originalPriceTo = $originalPrice;
                }
            }
        }

        if (!isset($data['collect_product_id']) || !is_string($data['collect_product_id'])) {
            $data['collect_product_id'] = '';
        }

        if (!isset($data['is_enable']) || !is_numeric($data['is_enable'])) {
            $data['is_enable'] = 0;
        } else {
            $data['is_enable'] = (int)$data['is_enable'];
        }
        if (!in_array($data['is_enable'], [-1, 0, 1])) {
            $data['is_enable'] = 0;
        }

        $db->startTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $tupleProduct->spu = $data['spu'];
            $tupleProduct->name = $name;
            $tupleProduct->summary = $data['summary'];
            $tupleProduct->description = $data['description'];
            $tupleProduct->url = $url;
            $tupleProduct->url_custom = $data['url_custom'];
            $tupleProduct->seo_title = $data['seo_title'];
            $tupleProduct->seo_title_custom = $data['seo_title_custom'];
            $tupleProduct->seo_description = $data['seo_description'];
            $tupleProduct->seo_description_custom = $data['seo_description_custom'];
            $tupleProduct->seo_keywords = $data['seo_keywords'];
            $tupleProduct->sales_volume_base = $data['sales_volume_base'];
            $tupleProduct->sales_volume = 0;
            $tupleProduct->price_from = $priceFrom;
            $tupleProduct->price_to = $priceTo;
            $tupleProduct->original_price_from = $originalPriceFrom;
            $tupleProduct->original_price_to = $originalPriceTo;
            $tupleProduct->brand = $data['brand'];
            $tupleProduct->style = $style;
            $tupleProduct->stock_tracking = $data['stock_tracking'];
            $tupleProduct->stock_out_action = $data['stock_out_action'];
            if ($data['collect_product_id'] !== '') {
                $tupleProduct->collect_product_id = $data['collect_product_id'];
            }
            if (isset($data['relate']['id']) && $data['relate']['id'] !== '') {
                $tupleProduct->relate_id = $data['relate']['id'];
            }
            $tupleProduct->is_enable = $data['is_enable'];
            $tupleProduct->is_delete = 0;
            $tupleProduct->update_time = $now;
            if ($isNew) {
                $tupleProduct->create_time = $now;
                $tupleProduct->insert();
            } else {
                $tupleProduct->update();
            }

            if (isset($data['category_ids']) && is_array($data['category_ids']) && count($data['category_ids']) > 0) {
                if ($isNew) {
                    foreach ($data['category_ids'] as $category_id) {
                        $tupleProductCategory = Be::getTuple('shopfai_product_category');
                        $tupleProductCategory->product_id = $tupleProduct->id;
                        $tupleProductCategory->category_id = $category_id;
                        $tupleProductCategory->insert();
                    }
                } else {
                    $existCategoryIds = Be::getTable('shopfai_product_category')
                        ->where('product_id', $productId)
                        ->getValues('category_id');

                    // 需要删除的分类
                    if (count($existCategoryIds) > 0) {
                        $removeCategoryIds = array_diff($existCategoryIds, $data['category_ids']);
                        if (count($removeCategoryIds) > 0) {
                            Be::getTable('shopfai_product_category')
                                ->where('product_id', $productId)
                                ->where('category_id', 'IN', $removeCategoryIds)
                                ->delete();
                        }
                    }

                    // 新增的分类
                    $newCategoryIds = null;
                    if (count($existCategoryIds) > 0) {
                        $newCategoryIds = array_diff($data['category_ids'], $existCategoryIds);
                    } else {
                        $newCategoryIds = $data['category_ids'];
                    }
                    if (count($newCategoryIds) > 0) {
                        foreach ($newCategoryIds as $category_id) {
                            $tupleProductCategory = Be::getTuple('shopfai_product_category');
                            $tupleProductCategory->product_id = $tupleProduct->id;
                            $tupleProductCategory->category_id = $category_id;
                            $tupleProductCategory->insert();
                        }
                    }
                }
            }

            // 标签
            if (isset($data['tags']) && is_array($data['tags']) && count($data['tags']) > 0) {
                if ($isNew) {
                    foreach ($data['tags'] as $tag) {
                        $tupleProductTag = Be::getTuple('shopfai_product_tag');
                        $tupleProductTag->product_id = $tupleProduct->id;
                        $tupleProductTag->tag = $tag;
                        $tupleProductTag->insert();
                    }
                } else {
                    $existTags = Be::getTable('shopfai_product_tag')
                        ->where('product_id', $productId)
                        ->getValues('tag');

                    // 需要删除的标签
                    if (count($existTags) > 0) {
                        $removeTags = array_diff($existTags, $data['tags']);
                        if (count($removeTags) > 0) {
                            Be::getTable('shopfai_product_tag')
                                ->where('product_id', $productId)
                                ->where('tag', 'IN', $removeTags)
                                ->delete();
                        }
                    }

                    // 新增的标签
                    $newTags = null;
                    if (count($existTags) > 0) {
                        $newTags = array_diff($data['tags'], $existTags);
                    } else {
                        $newTags = $data['tags'];
                    }
                    if (count($newTags) > 0) {
                        foreach ($newTags as $newTag) {
                            $tupleProductTag = Be::getTuple('shopfai_product_tag');
                            $tupleProductTag->product_id = $tupleProduct->id;
                            $tupleProductTag->tag = $newTag;
                            $tupleProductTag->insert();
                        }
                    }
                }
            }

            if (isset($data['images']) && is_array($data['images']) && count($data['images']) > 0) {
                if ($isNew) {
                    $ordering = 0;
                    foreach ($data['images'] as $image) {
                        $tupleProductImage = Be::getTuple('shopfai_product_image');
                        $tupleProductImage->product_id = $tupleProduct->id;
                        $tupleProductImage->small = $image['small'];
                        $tupleProductImage->medium = $image['medium'];
                        $tupleProductImage->large = $image['large'];
                        $tupleProductImage->original = $image['large'];
                        if ($ordering === 0) {
                            $tupleProductImage->is_main = 1;
                        } else {
                            $tupleProductImage->is_main = 0;
                        }
                        $tupleProductImage->ordering = $ordering;
                        $tupleProductImage->create_time = $now;
                        $tupleProductImage->update_time = $now;
                        $tupleProductImage->insert();
                        $ordering++;
                    }
                } else {
                    $keepIds = [];
                    foreach ($data['images'] as $image) {
                        if (isset($image['id']) && $image['id'] !== '') {
                            $keepIds[] = $image['id'];
                        }
                    }

                    if (count($keepIds) > 0) {
                        Be::getTable('shopfai_product_image')
                            ->where('product_id', $productId)
                            ->where('id', 'NOT IN', $keepIds)
                            ->delete();
                    } else {
                        Be::getTable('shopfai_product_image')
                            ->where('product_id', $productId)
                            ->delete();
                    }

                    $ordering = 0;
                    foreach ($data['images'] as $image) {
                        $tupleProductImage = Be::getTuple('shopfai_product_image');
                        if (isset($image['id']) && $image['id'] !== '') {
                            try {
                                $tupleProductImage->loadBy([
                                    'id' => $image['id'],
                                    'product_id' => $tupleProduct->id,
                                ]);
                            } catch (\Throwable $t) {
                                throw new ServiceException('商品（# ' . $productId . ' ' . $tupleProduct->name . '）下的图像（# ' . $image['id'] . '）不存在！');
                            }
                        }

                        $tupleProductImage->product_id = $tupleProduct->id;
                        $tupleProductImage->small = $image['small'];
                        $tupleProductImage->medium = $image['medium'];
                        $tupleProductImage->large = $image['large'];
                        $tupleProductImage->original = $image['large'];
                        if ($ordering === 0) {
                            $tupleProductImage->is_main = 1;
                        } else {
                            $tupleProductImage->is_main = 0;
                        }
                        $tupleProductImage->ordering = $ordering;

                        if (!isset($image['id']) || $image['id'] === '') {
                            $tupleProductImage->create_time = $now;
                        }

                        $tupleProductImage->update_time = $now;
                        $tupleProductImage->save();
                        $ordering++;
                    }
                }
            }

            if ($data['related'] === 1) {

                $isNewProductRelate = true;

                $tupleProductRelate = Be::getTuple('shopfai_product_relate');
                if ($data['relate']['id'] !== '') {
                    $tupleProductRelate->load($data['relate']['id']);
                    $isNewProductRelate = false;
                }
                $tupleProductRelate->name = $data['relate']['name'];
                $tupleProductRelate->icon_type = $data['relate']['icon_type'];
                if ($isNewProductRelate) {
                    if ($tupleProduct->is_enable === -1) {
                        $tupleProductRelate->is_enable = -1;
                    } else {
                        $tupleProductRelate->is_enable = 1;
                    }

                    $tupleProductRelate->is_delete = 0;
                    $tupleProductRelate->create_time = $now;
                    $tupleProductRelate->update_time = $now;

                    $tupleProductRelate->insert();
                } else {

                    $tupleProductRelate->update_time = $now;

                    $tupleProductRelate->update();
                }

                if (!$isNewProductRelate) {
                    // 删除旧的移除的关联商品

                    $keepIds = [];
                    foreach ($data['relate']['details'] as $detail) {
                        if (isset($detail['id']) && $detail['id'] !== '') {
                            $keepIds[] = $detail['id'];
                        }
                    }

                    $productRelateDetails = Be::getTable('shopfai_product_relate_detail')
                        ->where('relate_id', $tupleProductRelate->id)
                        ->getObjects();

                    foreach ($productRelateDetails as $productRelateDetail) {
                        $tDelete = true;
                        if (count($keepIds) > 0) {
                            $tDelete = !in_array($productRelateDetail->id, $keepIds);
                        }

                        if ($tDelete) {
                            // 删除商品的关联
                            $tProduct = Be::getTuple('shopfai_product');
                            $tProduct->load($productRelateDetail->product_id);
                            $tProduct->relate_id = '';
                            $tProduct->update_time = $now;
                            $tProduct->update();

                            Be::getTable('shopfai_product_relate_detail')
                                ->delete($productRelateDetail->id);
                        }
                    }
                }

                $relateDetailOrdering = 0;
                foreach ($data['relate']['details'] as $detail) {

                    if ($detail['id'] === '') {

                        // 检查该商品是否已有旧关联记录
                        $hasRelate = false;
                        $tupleProductRelateDetail = Be::getTuple('shopfai_product_relate_detail');
                        try {
                            $tupleProductRelateDetail->loadBy('product_id', $detail['product_id']);
                            $hasRelate = true;
                        } catch (\Throwable $t) {
                        }

                        // 已有旧关联记录
                        if ($hasRelate) {
                            $relateId = $tupleProductRelateDetail->relate_id;

                            // 删除旧关联明细
                            $tupleProductRelateDetail->delete();

                            $relateUpdate = [
                                'update_time' => $now,
                            ];

                            // 如果关联明细已全部删除，则删除关联本身
                            if (Be::getTable('shopfai_product_relate_detail')
                                    ->where('relate_id', $relateId)
                                    ->count() === 0) {
                                $relateUpdate['is_delete'] = 1;
                            }

                            Be::getTable('shopfai_product_relate')
                                ->where('id', $relateId)
                                ->update($relateUpdate);
                        }
                    }

                    $tupleProductRelateDetail = Be::getTuple('shopfai_product_relate_detail');

                    $isNewProductRelateDetail = true;
                    if ($detail['id'] !== '') {
                        $tupleProductRelateDetail->load($detail['id']);
                        $isNewProductRelateDetail = false;
                    } else {
                        $tupleProductRelateDetail->relate_id = $tupleProductRelate->id;

                        // 商品ID为空时，表示当前新增的商品
                        if ($detail['product_id'] === '') {
                            $tupleProductRelateDetail->product_id = $tupleProduct->id;
                        } else {
                            $tupleProductRelateDetail->product_id = $detail['product_id'];
                        }
                    }

                    $tupleProductRelateDetail->value = $detail['value'];
                    $tupleProductRelateDetail->icon_image = $detail['icon_image'];
                    $tupleProductRelateDetail->icon_color = $detail['icon_color'];
                    $tupleProductRelateDetail->ordering = $relateDetailOrdering;

                    if ($isNewProductRelateDetail) {
                        $tupleProductRelateDetail->insert();
                    } else {
                        $tupleProductRelateDetail->update();
                    }

                    // 非当前商品，则更新关联ID
                    if ($detail['product_id'] !== '' && $detail['product_id'] !== $tupleProduct->id) {
                        // 桔记商品的关联
                        $tProduct = Be::getTuple('shopfai_product');
                        $tProduct->load($detail['product_id']);
                        $tProduct->relate_id = $tupleProductRelate->id;
                        $tProduct->update_time = $now;
                        $tProduct->update();
                    }

                    $relateDetailOrdering++;
                }

                $tupleProduct->relate_id = $tupleProductRelate->id;
                $tupleProduct->update();
            } else {
                if (!$isNew) {

                    // 删除该商品的 关联记录
                    $hasRelate = false;
                    $tupleProductRelateDetail = Be::getTuple('shopfai_product_relate_detail');
                    try {
                        $tupleProductRelateDetail->loadBy('product_id', $tupleProduct->id);
                        $hasRelate = true;
                    } catch (\Throwable $t) {
                    }

                    if ($hasRelate) {
                        $relateId = $tupleProductRelateDetail->relate_id;

                        // 删除关联明细
                        $tupleProductRelateDetail->delete();

                        $relateUpdate = [
                            'update_time' => $now,
                        ];

                        // 如果关联明细已全部删除，则删除关联本身
                        if (Be::getTable('shopfai_product_relate_detail')
                                ->where('relate_id', $relateId)
                                ->count() === 0) {
                            $relateUpdate['is_delete'] = 1;
                        }

                        Be::getTable('shopfai_product_relate')
                            ->where('id', $relateId)
                            ->update($relateUpdate);
                    }
                }

                $tupleProduct->relate_id = '';
                $tupleProduct->update();
            }

            if ($style === 1) {
                if (!$isNew) {
                    // 删除旧数据
                    Be::getTable('shopfai_product_style')
                        ->where('product_id', $productId)
                        ->delete();
                }
            } elseif ($style === 2) {
                if ($isNew) {
                    $styles = $data['styles'];
                    foreach ($styles as $s) {
                        if (!isset($s['name']) || !$s['name']) continue;
                        if (!isset($s['values']) || !is_array($s['values']) || count($s['values']) === 0) continue;
                        $tupleProductStyle = Be::getTuple('shopfai_product_style');
                        $tupleProductStyle->product_id = $tupleProduct->id;
                        $tupleProductStyle->name = $s['name'];
                        $tupleProductStyle->values = json_encode($s['values']);
                        $tupleProductStyle->insert();
                    }
                } else {
                    $styles = $data['styles'];

                    $keepIds = [];
                    foreach ($styles as $s) {
                        if (!isset($s['name']) || !$s['name']) continue;
                        if (!isset($s['values']) || !is_array($s['values']) || count($s['values']) === 0) continue;
                        if (isset($s['id']) && $s['id'] !== '') {
                            $keepIds[] = $s['id'];
                        }
                    }

                    if (count($keepIds) > 0) {
                        Be::getTable('shopfai_product_style')
                            ->where('product_id', $productId)
                            ->where('id', 'NOT IN', $keepIds)
                            ->delete();
                    } else {
                        Be::getTable('shopfai_product_style')
                            ->where('product_id', $productId)
                            ->delete();
                    }

                    foreach ($styles as $s) {
                        if (!isset($s['name']) || !$s['name']) continue;
                        if (!isset($s['values']) || !is_array($s['values']) || count($s['values']) === 0) continue;

                        $tupleProductStyle = Be::getTuple('shopfai_product_style');
                        if (isset($s['id']) && $s['id'] !== '') {
                            try {
                                $tupleProductStyle->load($s['id']);
                            } catch (\Throwable $t) {
                                throw new ServiceException('商品（# ' . $productId . '）下的款式（# ' . $s['id'] . '）不存在！');
                            }
                        }

                        $tupleProductStyle->product_id = $tupleProduct->id;
                        $tupleProductStyle->name = $s['name'];
                        $tupleProductStyle->values = json_encode($s['values']);
                        $tupleProductStyle->save();
                    }
                }
            }

            if ($isNew) {
                foreach ($items as $item) {
                    $tupleProductItem = Be::getTuple('shopfai_product_item');
                    $tupleProductItem->product_id = $tupleProduct->id;

                    if ($style === 2) {
                        $tupleProductItem->image = $item['image'] ?? '';
                    } else {
                        $tupleProductItem->image = '';
                    }

                    $tupleProductItem->sku = $item['sku'] ?? '';
                    $tupleProductItem->barcode = $item['barcode'] ?? '';
                    $tupleProductItem->style = $item['style'] ?? '';
                    $tupleProductItem->style_json = $item['style_json'] ? json_encode($item['style_json']) : '';
                    $tupleProductItem->price = $item['price'] ?? '0';
                    $tupleProductItem->original_price = $item['original_price'] ?? '0';
                    $tupleProductItem->weight = $item['weight'] ?? '0';
                    $tupleProductItem->weight_unit = $item['weight_unit'] ?? '';
                    $tupleProductItem->stock = $item['stock'] ?? 0;
                    $tupleProductItem->create_time = $now;
                    $tupleProductItem->update_time = $now;
                    $tupleProductItem->insert();
                }
            } else {
                $keepIds = [];
                foreach ($items as $item) {
                    if (isset($item['id']) && $item['id'] !== '') {
                        $keepIds[] = $item['id'];
                    }
                }

                if (count($keepIds) > 0) {
                    Be::getTable('shopfai_product_item')
                        ->where('product_id', $productId)
                        ->where('id', 'NOT IN', $keepIds)
                        ->delete();
                } else {
                    Be::getTable('shopfai_product_item')
                        ->where('product_id', $productId)
                        ->delete();
                }

                foreach ($items as $item) {
                    $tupleProductItem = Be::getTuple('shopfai_product_item');

                    if (isset($item['id']) && $item['id'] !== '') {
                        try {
                            $tupleProductItem->load($item['id']);
                        } catch (\Throwable $t) {
                            throw new ServiceException('商品（# ' . $productId . '）下的SKU（# ' . $item['id'] . '）不存在！');
                        }
                    }

                    $tupleProductItem->product_id = $tupleProduct->id;

                    if ($style === 2) {
                        $tupleProductItem->image = $item['image'] ?? '';
                    } else {
                        $tupleProductItem->image = '';
                    }

                    $tupleProductItem->sku = $item['sku'] ?? '';
                    $tupleProductItem->barcode = $item['barcode'] ?? '';
                    $tupleProductItem->style = $item['style'] ?? '';
                    $tupleProductItem->style_json = $item['style_json'] ? json_encode($item['style_json']) : '';
                    $tupleProductItem->price = $item['price'] ?? '0';
                    $tupleProductItem->original_price = $item['original_price'] ?? '0';
                    $tupleProductItem->weight = $item['weight'] ?? '0';
                    $tupleProductItem->weight_unit = $item['weight_unit'] ?? '';
                    $tupleProductItem->stock = $item['stock'] ?? 0;

                    if (!isset($item['id']) || $item['id'] === '') {
                        $tupleProductItem->create_time = $now;
                    }

                    $tupleProductItem->update_time = $now;
                    $tupleProductItem->save();
                }
            }

            Be::getService('App.ShopFai.Admin.Store')->setUp(1);

            $db->commit();

            Be::getService('App.System.Task')->trigger('ShopFai.ProductSyncEsAndCache');
            Be::getService('App.System.Task')->trigger('ShopFai.ProductRelateSyncCache');

        } catch (\Throwable $t) {
            $db->rollback();
            Be::getLog()->error($t);
            throw new ServiceException(($isNew ? '新建' : '编辑') . '商品发生异常！');
        }

        return $tupleProduct;
    }

    /**
     * 删除商品
     *
     * @param array $productIds
     * @return void
     * @throws ServiceException
     * @throws \Be\Db\DbException
     * @throws \Be\Runtime\RuntimeException
     */
    public function delete(array $productIds)
    {
        if (count($productIds) === 0) return;

        $db = Be::getDb();

        $now = date('Y-m-d H:i:s');
        foreach ($productIds as $productId) {
            $tupleProduct = Be::getTuple('shopfai_product');
            try {
                $tupleProduct->loadBy([
                    'id' => $productId,
                    'is_delete' => 0
                ]);
            } catch (\Throwable $t) {
                throw new ServiceException('商品（# ' . $productId . '）不存在！');
            }

            $db->startTransaction();
            try {

                // 删除商品分类
                Be::getTable('shopfai_product_category')
                    ->where('product_id', '=', $productId)
                    ->delete();

                // 删除商品标签
                Be::getTable('shopfai_product_tag')
                    ->where('product_id', $productId)
                    ->delete();

                // 如查商品有设置关联，删除商品关联
                if ($tupleProduct->relate_id !== '') {
                    Be::getTable('shopfai_product_relate_detail')
                        ->where('relate_id', $tupleProduct->relate_id)
                        ->where('product_id', $productId)
                        ->delete();

                    $relateUpdate = [
                        'update_time' => $now,
                    ];

                    // 如果关联明细已全部删除，则删除关联本身
                    if (Be::getTable('shopfai_product_relate_detail')
                            ->where('relate_id', $tupleProduct->relate_id)
                            ->count() === 0) {
                        $relateUpdate['is_delete'] = 1;
                    }

                    Be::getTable('shopfai_product_relate')
                        ->where('id', $tupleProduct->relate_id)
                        ->update($relateUpdate);

                    $tupleProduct->relate_id = '';
                }

                /*
                // 删除商品评论
                $reviewIds = Be::getTable('shopfai_product_review')
                    ->where('product_id', $productId)
                    ->getValues('id');
                if (count($reviewIds) > 0) {
                    Be::getTable('shopfai_product_review_detail')
                        ->where('product_review_id', 'IN', $reviewIds)
                        ->delete();

                    Be::getTable('shopfai_product_review')
                        ->where('product_id', $productId)
                        ->delete();
                }
                */

                /*
                // 删除商品主图
                Be::getTable('shopfai_product_image')
                    ->where('product_id', $product->id)
                    ->delete();

                // 删除商品款式
                Be::getTable('shopfai_product_style')
                    ->where('product_id', $productId)
                    ->delete();

                // 删除商品子项
                Be::getTable('shopfai_product_item')
                    ->where('product_id', $productId)
                    ->delete();
                */

                $tupleProduct->url = $productId;
                $tupleProduct->is_delete = 1;
                $tupleProduct->update_time = $now;
                $tupleProduct->update();

                $db->commit();

                Be::getService('App.System.Task')->trigger('ShopFai.ProductSyncEsAndCache');
                Be::getService('App.System.Task')->trigger('ShopFai.ProductRelateSyncCache');

            } catch (\Throwable $t) {
                $db->rollback();
                Be::getLog()->error($t);

                throw new ServiceException('删除商品发生异常！');
            }
        }
    }

    /**
     * 获取商品
     *
     * @param $productId
     * @param array $with
     * @return object
     */
    public function getProduct($productId, $with = []): object
    {
        $db = Be::getDb();

        $sql = 'SELECT * FROM `shopfai_product` WHERE id=?';
        $product = $db->getObject($sql, [$productId]);
        if (!$product) {
            throw new ServiceException('商品（# ' . $productId . '）不存在！');
        }

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
        $product->is_delete = (int)$product->is_delete;

        if (isset($with['relate'])) {
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
        }

        if (isset($with['images'])) {
            $sql = 'SELECT * FROM shopfai_product_image WHERE product_id = ? ORDER BY ordering ASC';
            $images = $db->getObjects($sql, [$productId]);
            foreach ($images as $image) {
                $image->is_main = (int)$image->is_main;
                $image->ordering = (int)$image->ordering;
            }
            $product->images = $images;
        }

        if (isset($with['categories'])) {
            $sql = 'SELECT category_id FROM shopfai_product_category WHERE product_id = ?';
            $categoryIds = $db->getValues($sql, [$productId]);
            if (count($categoryIds) > 0) {
                $product->categoryIds = $categoryIds;

                $sql = 'SELECT * FROM shopfai_category WHERE id IN (?)';
                $categories = $db->getObjects($sql, ['\'' . implode('\',\'', $categoryIds) . '\'']);
                foreach ($categories as $category) {
                    $category->ordering = (int)$category->ordering;
                }
                $product->categories = $categories;
            } else {
                $product->categoryIds = [];
                $product->categories = [];
            }
        }

        if (isset($with['tags'])) {
            $sql = 'SELECT tag FROM shopfai_product_tag WHERE product_id = ?';
            $product->tags = $db->getValues($sql, [$productId]);
        }

        if (isset($with['styles'])) {
            $sql = 'SELECT * FROM shopfai_product_style WHERE product_id = ?';
            $styles = $db->getObjects($sql, [$productId]);
            $product->styles = $styles;
        }

        if (isset($with['items'])) {
            $sql = 'SELECT * FROM shopfai_product_item WHERE product_id = ?';
            $items = $db->getObjects($sql, [$productId]);
            foreach ($items as $item) {
                $item->stock = (int)$item->stock;
            }
            $product->items = $items;
        }

        return $product;
    }

    /**
     * 获取选择器
     *
     * @return array
     */
    public function getProductPicker(int $multiple = 0): array
    {
        $categoryKeyValues = Be::getService('App.ShopFai.Admin.Category')->getCategoryKeyValues();
        return [
            'table' => 'shopfai_product',
            'grid' => [
                'title' => $multiple === 1 ? '选择商品' : '选择一个商品',

                'filter' => [
                    ['is_enable', '=', '1'],
                    ['is_delete', '=', '0'],
                ],

                'form' => [
                    'items' => [
                        [
                            'name' => 'category_id',
                            'label' => '分类',
                            'driver' => FormItemSelect::class,
                            'keyValues' => $categoryKeyValues,
                            'buildSql' => function ($dbName, $formData) {
                                if (isset($formData['category_id']) && $formData['category_id']) {
                                    $productIds = Be::getTable('shopfai_product_category', $dbName)
                                        ->where('category_id', $formData['category_id'])
                                        ->getValues('product_id');
                                    if (count($productIds) > 0) {
                                        return ['id', 'IN', $productIds];
                                    } else {
                                        return ['id', '=', ''];
                                    }
                                }
                                return '';
                            },
                        ],
                        [
                            'name' => 'name',
                            'label' => '商品名称',
                        ],
                    ],
                ],

                'table' => [

                    // 未指定时取表的所有字段
                    'items' => [
                        [
                            'name' => 'image',
                            'label' => '商品图片',
                            'width' => '90',
                            'driver' => TableItemImage::class,
                            'value' => function ($row) {
                                $sql = 'SELECT small FROM shopfai_product_image WHERE product_id = ? AND is_main = 1';
                                $image = Be::getDb()->getValue($sql, [$row['id']]);
                                if ($image) {
                                    return $image;
                                } else {
                                    return Be::getProperty('App.ShopFai')->getWwwUrl() . '/images/product/no-image.jpg';
                                }
                            },
                            'ui' => [
                                'style' => 'max-width: 60px; max-height: 60px'
                            ],
                        ],
                        [
                            'name' => 'name',
                            'label' => '商品名称',
                            'align' => 'left',
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * 获取菜单参数选择器
     *
     * @return array
     */
    public function getProductMenuPicker(): array
    {
        $categoryKeyValues = Be::getService('App.ShopFai.Admin.Category')->getCategoryKeyValues();
        return [
            'name' => 'id',
            'value' => '商品详情页：{name}',
            'table' => 'shopfai_product',
            'grid' => [
                'title' => '选择一个商品',

                'filter' => [
                    ['is_enable', '=', '1'],
                    ['is_delete', '=', '0'],
                ],

                'form' => [
                    'items' => [
                        [
                            'name' => 'category_id',
                            'label' => '分类',
                            'driver' => FormItemSelect::class,
                            'keyValues' => $categoryKeyValues,
                            'buildSql' => function ($dbName, $formData) {
                                if (isset($formData['category_id']) && $formData['category_id']) {
                                    $productIds = Be::getTable('shopfai_product_category', $dbName)
                                        ->where('category_id', $formData['category_id'])
                                        ->getValues('product_id');
                                    if (count($productIds) > 0) {
                                        return ['id', 'IN', $productIds];
                                    } else {
                                        return ['id', '=', ''];
                                    }
                                }
                                return '';
                            },
                        ],
                        [
                            'name' => 'name',
                            'label' => '商品名称',
                        ],
                    ],
                ],

                'table' => [

                    // 未指定时取表的所有字段
                    'items' => [
                        [
                            'name' => 'image',
                            'label' => '商品图片',
                            'width' => '90',
                            'driver' => TableItemImage::class,
                            'value' => function ($row) {
                                $sql = 'SELECT small FROM shopfai_product_image WHERE product_id = ? AND is_main = 1';
                                $image = Be::getDb()->getValue($sql, [$row['id']]);
                                if ($image) {
                                    return $image;
                                } else {
                                    return Be::getProperty('App.ShopFai')->getWwwUrl() . '/images/product/no-image.jpg';
                                }
                            },
                            'ui' => [
                                'style' => 'max-width: 60px; max-height: 60px'
                            ],
                        ],
                        [
                            'name' => 'name',
                            'label' => '商品名称',
                            'align' => 'left',
                        ],
                        [
                            'name' => 'create_time',
                            'label' => '创建时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                        [
                            'name' => 'update_time',
                            'label' => '更新时间',
                            'width' => '180',
                            'sortable' => true,
                        ],
                    ],
                ],
            ]
        ];
    }
}