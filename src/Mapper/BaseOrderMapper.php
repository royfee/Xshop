<?php
namespace royfee\xshop\Mapper;

use royfee\xshop\Data\XOrder;
use royfee\xshop\Data\XOrderItem;

abstract class BaseOrderMapper implements OrderMapperInterface
{
    protected $platform;
    
    public function __construct(string $platform)
    {
        $this->platform = $platform;
    }
    
    public function mapOrders(array $rawOrders): array
    {
        $unifiedOrders = [];
        foreach ($rawOrders as $rawOrder) {
            $unifiedOrders[] = $this->mapToUnifiedOrder($rawOrder);
        }
        return $unifiedOrders;
    }
    
    /**
     * ��ȡ������Ʒ�б�
     */
    abstract protected function extractItems(array $rawOrder): array;
    
    /**
     * ��ȡ�����Ϣ
     */
    abstract protected function extractBuyerInfo(array $rawOrder): array;
    
    /**
     * ��ȡ�ջ���Ϣ
     */
    abstract protected function extractReceiverInfo(array $rawOrder): array;
    
    /**
     * ��ȡ�����Ϣ
     */
    abstract protected function extractAmountInfo(array $rawOrder): array;
    
    /**
     * ��ȡʱ����Ϣ
     */
    abstract protected function extractTimeInfo(array $rawOrder): array;
}