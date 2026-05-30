<?php
namespace MultiShop\Platforms\Taobao\Api;

use MultiShop\Platforms\BaseApi;

class Goods extends BaseApi
{
    /**
     * 获取商品列表
     * @param array $params ['keyword' => '手机', 'page' => 1, 'page_size' => 20]
     */
    public function getList($params = [])
    {
        return $this->call('taobao.items.get', [
            'fields' => 'num_iid,title,price,pic_url,detail_url',
            'q' => $params['keyword'] ?? '',
            'page_no' => $params['page'] ?? 1,
            'page_size' => $params['page_size'] ?? 20,
            'order_by' => $params['order_by'] ?? 'sale:desc'
        ]);
    }
    
    /**
     * 获取商品详情
     */
    public function getDetail($goodsId)
    {
        return $this->call('taobao.item.get', [
            'fields' => 'num_iid,title,price,desc,sku,props_name',
            'num_iid' => $goodsId
        ]);
    }
    
    /**
     * 搜索商品
     */
    public function search($keyword, $page = 1, $pageSize = 20)
    {
        return $this->getList([
            'keyword' => $keyword,
            'page' => $page,
            'page_size' => $pageSize
        ]);
    }
}