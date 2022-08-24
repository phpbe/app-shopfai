<?php

namespace Be\App\ShopFai\Service;

use Be\Be;

class Section
{

    /**
     * 生成文章列表部件
     *
     * @param object $section
     * @param string $class
     * @param array $products
     * @param string $moreLink
     * @return string
     */
    public function makeProductSection(object $section, string $class, array $products, string $moreLink = null): string
    {
        $count = count($products);
        if ($count === 0) {
            return '';
        }

        $html = '';
        $html .= '<style type="text/css">';

        $html .= Be::getService('App.ShopFai.Ui')->getProductGlobalCss();

        $html .= $section->getCssBackgroundColor($class);
        $html .= $section->getCssPadding($class);
        $html .= $section->getCssMargin($class);

        if ($count === 1) {
            $itemWidthMobile = $itemWidthTablet = '100%';
        } elseif ($count === 2) {
            $itemWidthMobile = $itemWidthTablet = '50%';
        } else {
            $itemWidthMobile = '50%';
            $itemWidthTablet = (100 / 3) . '%';
        }

        $cols = $section->config->cols ?? 4;
        $itemWidthDesktop = (100 / $cols) . '%;';

        $html .= $section->getCssSpacing($class . '-products', $class . '-product', $itemWidthMobile, $itemWidthTablet, $itemWidthDesktop);

        // 手机端小于 320px 时, 100% 宽度
        $html .= '@media (max-width: 320px) {';
        $html .= '#' . $section->id . ' .' . $class . '-product {';
        $html .= 'width: 100% !important;';
        $html .= '}';
        $html .= '}';

        $html .= '#' . $section->id . ' .' . $class . '-title {';
        $html .= 'position: relative;';
        $html .= '}';

        $html .= '#' . $section->id . ' .' . $class . '-title h3 {';
        $html .= 'text-align: ' . $section->config->titleAlign . ';';
        $html .= '}';

        $html .= '#' . $section->id . ' .' . $class . '-title a {';
        $html .= 'position: absolute;';
        $html .= 'top: 0;';
        $html .= 'right: 0;';
        $html .= '}';

        $html .= '#' . $section->id . ' .' . $class . '-product-image {';
        $html .= '}';

        $html .= '#' . $section->id . ' .' . $class . '-product-image .' . $class . '-product-image-1 {';
        $html .= 'width: 100%;';
        $html .= '}';

        if ($section->config->hoverEffect != 'none') {
            if ($section->config->hoverEffect == 'scale' || $section->config->hoverEffect == 'rotateScale') {
                $html .= '#' . $section->id . ' .' . $class . '-product-image a .' . $class . '-product-image-1 {';
                $html .= 'transition: all 0.7s ease;';
                $html .= '}';
            }

            switch ($section->config->hoverEffect) {
                case 'scale':
                    $html .= '#' . $section->id . ' .' . $class . '-product-image a:hover .' . $class . '-product-image-1 {';
                    $html .= 'transform: scale(1.1);';
                    $html .= '}';
                    break;
                case 'rotateScale':
                    $html .= '#' . $section->id . ' .' . $class . '-product-image a:hover .' . $class . '-product-image-1 {';
                    $html .= 'transform: rotate(3deg) scale(1.1);';
                    $html .= '}';
                    break;
                case 'toggleImage':
                    $html .= '#' . $section->id . ' .' . $class . '-product-image a {';
                    $html .= 'display:block;';
                    $html .= 'position:relative;';
                    $html .= '}';

                    $html .= '#' . $section->id . ' .' . $class . '-product-image a .' . $class . '-product-image-1 {';
                    $html .= '}';

                    $html .= '#' . $section->id . ' .' . $class . '-product-image a .' . $class . '-product-image-2 {';
                    $html .= 'position:absolute;';
                    $html .= 'top:0;';
                    $html .= 'left:0;';
                    $html .= 'right:0;';
                    $html .= 'bottom:0;';
                    $html .= 'width:100%;';
                    $html .= 'height:100%;';
                    $html .= 'opacity:0;';
                    $html .= 'cursor:pointer;';
                    $html .= 'transition: all 0.7s ease;';
                    $html .= '}';

                    $html .= '#' . $section->id . ' .' . $class . '-product-image a:hover .' . $class . '-product-image-1 {';
                    $html .= '}';

                    $html .= '#' . $section->id . ' .' . $class . '-product-image a:hover .' . $class . '-product-image-2 {';
                    $html .= 'opacity:1;';
                    $html .= '}';
                    break;
            }
        }
        $html .= '</style>';

        $isMobile = \Be\Be::getRequest()->isMobile();

        $html .= '<div class="' . $class . '">';

        if ($section->position === 'middle' && $section->config->width === 'default') {
            $html .= '<div class="be-container">';
        }

        if ($section->config->title !== '') {
            $html .= $section->pageTemplate->tag0('be-section-title', true);

            $html .= '<div class="' . $class . '-title">';
            $html .= '<h3 class="be-h3">' . $section->config->title . '</h3>';

            if ($moreLink !== null && isset($section->config->more) && $section->config->more !== '') {
                $html .= '<a href="' . $moreLink . '"';
                if (!$isMobile) {
                    $html .= ' target="_blank"';
                }
                $html .= '>' . $section->config->more . '</a>';
            }
            $html .= '</div>';

            $html .= $section->pageTemplate->tag1('be-section-title', true);
        }

        $nnImage = Be::getProperty('App.ShopFai')->getWwwUrl() . '/images/product/no-image.jpg';

        $html .= $section->pageTemplate->tag0('be-section-content', true);
        $html .= '<div class="' . $class . '-products">';
        foreach ($products as $product) {
            $defaultImage = null;
            $hoverImage = null;
            foreach ($product->images as $image) {
                if ($section->config->hoverEffect == 'toggleImage') {
                    if ($image->is_main) {
                        $defaultImage = $image;
                    } else {
                        $hoverImage = $image;
                    }

                    if ($defaultImage && $hoverImage) {
                        break;
                    }
                } else {
                    if ($image->is_main) {
                        $defaultImage = $image;
                        break;
                    }
                }
            }

            if (!$defaultImage && count($product->images) > 0) {
                $defaultImage = $product->images[0];
            }

            if (!$defaultImage) {
                $defaultImage = (object)[
                    'id' => '',
                    'product_id' => $product->id,
                    'small' => $nnImage,
                    'medium' => $nnImage,
                    'large' => $nnImage,
                    'original' => $nnImage,
                    'is_main' => 1,
                    'ordering' => 0,
                ];
            }

            $html .= '<div class="' . $class . '-product">';

            $html .= '<div class="' . $class . '-product-image">';
            $html .= '<a href="' . beUrl('ShopFai.Product.detail', ['id' => $product->id]) . '"';
            if (!$isMobile) {
                $html .= ' target="_blank"';
            }
            $html .= '>';
            if ($defaultImage) {
                $html .= '<img src="' . $defaultImage->medium . '" class="' . $class . '-product-image-1" />';
                if ($section->config->hoverEffect == 'toggleImage' && $hoverImage) {
                    $html .= '<img src="' . $hoverImage->medium . '" class="' . $class . '-product-image-2" />';
                }
            }

            $html .= '</a>';
            $html .= '</div>';

            $html .= '<div class="be-mt-50">';
            $averageRating = round($product->rating_avg);
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $averageRating) {
                    $html .= '<i class="icon-star-fill icon-star-fill-150"></i>';
                } else {
                    $html .= '<i class="icon-star icon-star-150"></i>';
                }
            }
            $html .= '</div>';

            $html .= '<div class="be-mt-50">';
            $html .= '<a class="be-d-block be-t-ellipsis-2" href="' . beUrl('ShopFai.Product.detail', ['id' => $product->id]) . '"';
            if (!$isMobile) {
                $html .= ' target="_blank"';
            }
            $html .= '>';
            $html .= $product->name;
            $html .= '</a>';
            $html .= '</div>';

            $html .= '<div class="be-mt-50">';
            if ($product->original_price_from > 0 && $product->original_price_from != $product->price_from) {
                $html .= '<span class="be-td-line-through be-mr-50 be-c-999">$';
                if ($product->original_price_from === $product->original_price_to) {
                    $html .= $product->original_price_from;
                } else {
                    $html .= $product->original_price_from . '~' . $product->original_price_to;;
                }
                $html .= '</span>';
            }

            $html .= '<span class="be-fw-bold">$';
            if ($product->price_from === $product->price_to) {
                $html .= $product->price_from;
            } else {
                $html .= $product->price_from . '~' . $product->price_to;;
            }
            $html .= '</span>';

            $html .= '</div>';

            $buttonClass = 'be-btn';
            if (isset($section->config->buttonClass) && $section->config->buttonClass !== '') {
                $buttonClass = $section->config->buttonClass;
            } elseif (isset($section->pageTemplate->_page->buttonClass) && $section->pageTemplate->_page->buttonClass !== '') {
                $buttonClass = $section->pageTemplate->_page->buttonClass;
            }

            $html .= '<div class="be-mt-50">';
            if (count($product->items) > 1) {
                $html .= '<input type="button" class="' . $buttonClass . '" value="Quick Buy" onclick="quickBuy(\'' . $product->id . '\')">';
            } else {
                $productItem = $product->items[0];
                $html .= '<input type="button" class="' . $buttonClass . '" value="Add to Cart" onclick="addToCart(\'' . $product->id . '\', \'' . $productItem->id . '\')">';
            }
            $html .= '</div>';

            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= $section->pageTemplate->tag1('be-section-content', true);

        if ($section->position === 'middle' && $section->config->width === 'default') {
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }


}
