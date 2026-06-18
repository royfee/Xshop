<?php

namespace Royfee\XShop\Mapper;

use Royfee\XShop\Contracts\MapperInterface;

abstract class BaseMapper implements MapperInterface
{
    abstract public function transform($data);
}