<?php
namespace royfee\xshop\Platforms\Yueyan\Mapper;

use royfee\xshop\Data\XOrder;
use royfee\xshop\Data\XOrderItem;
use royfee\xshop\Mapper\BaseOrderMapper;

class OrderMapper extends BaseOrderMapper
{
    public function __construct()
    {
        parent::__construct('yueyan');
    }
    
    public function mapOrder(array $rawOrder)
    {
exit('eeeff');
        $order = new XOrder();
  
        $order->platform = $this->platform;
        $order->originalId = $rawOrder['tid'] ?? '';
        $order->orderId = $this->platform . '_' . $order->originalId;
        //$order->orderStatus = OrderStatusMapper::mapTaobaoStatus($rawOrder['status'] ?? '');
        //$order->orderStatusText = OrderStatusMapper::getStatusText($order->orderStatus);
        
        //
        $amountInfo = $this->extractAmountInfo($rawOrder);
var_dump($amountInfo);
exit('222');              
        $order->totalAmount = $amountInfo['total_amount'] ?? 0;
        $order->paymentAmount = $amountInfo['payment_amount'] ?? 0;
        $order->discountAmount = $amountInfo['discount_amount'] ?? 0;
        $order->shippingFee = $amountInfo['shipping_fee'] ?? 0;
        $order->currency = $amountInfo['currency'] ?? 'CNY';
        
        //
        $timeInfo = $this->extractTimeInfo($rawOrder);
        $order->createdAt = $timeInfo['created_at'] ?? '';
        $order->paidAt = $timeInfo['paid_at'] ?? '';
        $order->shippedAt = $timeInfo['shipped_at'] ?? '';
        $order->completedAt = $timeInfo['completed_at'] ?? '';
        
        //
        $buyerInfo = $this->extractBuyerInfo($rawOrder);
        $order->buyerNick = $buyerInfo['buyer_nick'] ?? '';
        $order->buyerName = $buyerInfo['buyer_name'] ?? '';
        
        //
        $receiverInfo = $this->extractReceiverInfo($rawOrder);
        $order->receiverName = $receiverInfo['receiver_name'] ?? '';
        $order->receiverPhone = $receiverInfo['receiver_phone'] ?? '';
        $order->receiverAddress = $receiverInfo['receiver_address'] ?? '';
        $order->receiverZip = $receiverInfo['receiver_zip'] ?? '';
        
        //
        $order->items = $this->extractItems($rawOrder);
        $order->itemCount = count($order->items);
        
        //
        $order->logisticsCompany = $rawOrder['logistics_company'] ?? '';
        $order->logisticsNo = $rawOrder['logistics_no'] ?? '';
        
        //
        $order->buyerMessage = $rawOrder['buyer_message'] ?? '';
        $order->sellerMessage = $rawOrder['seller_memo'] ?? '';
        
        //
        $order->rawData = $rawOrder;
        
        return $order;
    }
    
    public function mapItem(array $rawItem): UnifiedOrderItem
    {
        $item = new UnifiedOrderItem();
        $item->skuId = $rawItem['sku_id'] ?? '';
        $item->productId = $rawItem['num_iid'] ?? '';
        $item->productTitle = $rawItem['title'] ?? '';
        $item->productImage = $rawItem['pic_path'] ?? '';
        $item->price = $rawItem['price'] ?? 0;
        $item->quantity = $rawItem['num'] ?? 0;
        $item->totalPrice = ($rawItem['price'] ?? 0) * ($rawItem['num'] ?? 0);
        $item->discountPrice = $rawItem['discount_fee'] ?? 0;
        $item->skuProperties = $rawItem['sku_properties_name'] ?? '';
        $item->rawData = $rawItem;
        
        return $item;
    }
    
    protected function extractItems(array $rawOrder): array
    {
        $items = [];
        $orderItems = $rawOrder['orders']['order'] ?? [];
        
        if (isset($orderItems['num_iid']) && !isset($orderItems[0])) {
            $orderItems = [$orderItems];
        }
        
        foreach ($orderItems as $rawItem) {
            $items[] = $this->mapToUnifiedItem($rawItem);
        }
        
        return $items;
    }
    
    protected function extractBuyerInfo(array $rawOrder): array
    {
        return [
            'buyer_nick' => $rawOrder['buyer_nick'] ?? '',
            'buyer_name' => $rawOrder['buyer_name'] ?? $rawOrder['receiver_name'] ?? '',
        ];
    }
    
    protected function extractReceiverInfo(array $rawOrder): array
    {
        return [
            'receiver_name' => $rawOrder['receiver_name'] ?? '',
            'receiver_phone' => $rawOrder['receiver_phone'] ?? '',
            'receiver_address' => $this->formatAddress([
                $rawOrder['receiver_state'] ?? '',
                $rawOrder['receiver_city'] ?? '',
                $rawOrder['receiver_district'] ?? '',
                $rawOrder['receiver_address'] ?? '',
            ]),
            'receiver_zip' => $rawOrder['receiver_zip'] ?? '',
        ];
    }
    
    protected function extractAmountInfo(array $rawOrder): array
    {
        return [
            'total_amount' => $rawOrder['rmb_payment'] ?? 0,
            'payment_amount' => $rawOrder['payment'] ?? 0,
            'discount_amount' => $rawOrder['discount_fee'] ?? 0,
            'shipping_fee' => $rawOrder['rmb_shipping_fee'] ?? 0,
            'currency' => $rawOrder['payment_currency'] ?? '',
        ];
    }
    
    protected function extractTimeInfo(array $rawOrder): array
    {
        return [
            'created_at' => $rawOrder['created'] ?? '',
            'paid_at' => $rawOrder['pay_time'] ?? '',
            'shipped_at' => $rawOrder['consign_time'] ?? '',
            'completed_at' => $rawOrder['end_time'] ?? '',
        ];
    }
    
    private function formatAddress(array $parts): string
    {
        return implode('', array_filter($parts));
    }
}