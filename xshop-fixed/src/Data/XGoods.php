<?php

namespace Royfee\XShop\Data;

class XGoods
{
    public $goodsId;
    public $title;
    public $price;
    public $stock;
    public $status;
    public $quantity;
    public $platform;
    public $spec;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}