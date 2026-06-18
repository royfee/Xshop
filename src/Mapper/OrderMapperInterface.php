<?php
namespace royfee\xshop\Mapper;

interface OrderMapperInterface
{
    public function mapOrder(array $rawOrder);
    public function mapOrders(array $rawOrders);
    public function mapItem(array $rawItem);
}