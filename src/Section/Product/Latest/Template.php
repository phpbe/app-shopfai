<?php

namespace Be\App\Shop\Section\Product\Latest;

use Be\Be;
use Be\Theme\Section;

class Template extends Section
{

    public array $positions = ['middle', 'center'];

    public function display()
    {
        if ($this->config->enable === 0) {
            return;
        }

        $request = Be::getRequest();
        $response = Be::getResponse();

        $page = $request->get('page', 1);
        if ($page > $this->config->maxPages) {
            $page = $this->config->maxPages;
        }
        $params = [
            'orderBy' => 'publish_time',
            'orderByDir' => 'desc',
            'pageSize' => $this->config->pageSize,
            'page' => $page,
        ];

        $result = Be::getService('App.Shop.Product')->search('', $params);

        echo Be::getService('App.Shop.Section')->makePagedProductsSection($this, 'app-cms-article-latest', $result);
    }

}

