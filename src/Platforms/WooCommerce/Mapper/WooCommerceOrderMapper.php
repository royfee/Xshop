<?php

namespace Royfee\XShop\Platforms\WooCommerce\Mapper;

use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Data\XOrder;
use Royfee\XShop\Data\XOrderItem;

/**
 * WooCommerce Order Mapper
 * Maps WooCommerce REST API v3 order data to XShop unified format
 */
class WooCommerceOrderMapper extends AbstractMapper
{
    protected $statusTextMap = [
        'pending'    => '待付款',
        'processing' => '处理中',
        'on-hold'    => '暂停中',
        'completed'  => '已完成',
        'cancelled'  => '已取消',
        'refunded'   => '已退款',
        'failed'     => '失败',
        'trash'      => '已删除',
    ];

    public function map(array $rawData): XOrder
    {
        $order = new XOrder();
        $order->platform = 'woocommerce';
        $order->rawData = $rawData;

        $order->orderNo = (string) $this->get($rawData, 'id');
        $order->platformOrderNo = (string) ($this->get($rawData, 'order_key') ?: $this->get($rawData, 'id'));
        $order->shopId = '';
        $order->buyerId = (string) $this->get($rawData, 'customer_id');
        $order->buyerNickname = $this->buildBuyerNickname($rawData);

        $status = $this->get($rawData, 'status', 'pending');
        $order->status = $status;
        $order->statusText = $this->statusTextMap[$status] ?? $status;

        $order->totalAmount = $this->toFloat($this->get($rawData, 'total'));
        $order->goodsAmount = $this->toFloat($this->get($rawData, 'subtotal'));
        $order->postage = $this->toFloat($this->get($rawData, 'total_shipping'));
        $order->discountAmount = $this->toFloat($this->get($rawData, 'total_discount'));

        $order->payType = $this->get($rawData, 'payment_method_title') ?: $this->get($rawData, 'payment_method');
        $order->payTime = $this->formatIsoTime($this->get($rawData, 'date_paid'));
        $order->createTime = $this->formatIsoTime($this->get($rawData, 'date_created'));
        $order->sendTime = null;
        $order->receiveTime = $this->formatIsoTime($this->get($rawData, 'date_completed'));

        $this->mapBilling($order, $rawData);
        $this->mapShipping($order, $rawData);

        $order->buyerRemark = $this->get($rawData, 'customer_note');
        $order->sellerRemark = $this->extractSellerNote($rawData);

        $this->mapLogistics($order, $rawData);

        $lineItems = $this->get($rawData, 'line_items', []);
        $order->items = array_map(function ($item) {
            return $this->mapItem($item);
        }, $lineItems);

        return $order;
    }

    protected function buildBuyerNickname(array $rawData): string
    {
        $billing = $rawData['billing'] ?? [];
        $first = $billing['first_name'] ?? '';
        $last = $billing['last_name'] ?? '';
        $name = trim("{$first} {$last}");
        return $name ?: (string) ($rawData['customer_id'] ?? '');
    }

    protected function mapBilling(XOrder $order, array $rawData): void
    {
        $billing = $rawData['billing'] ?? [];
        $order->receiverName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
        $order->receiverPhone = $billing['phone'] ?? '';
        $order->receiverProvince = $billing['state'] ?? '';
        $order->receiverCity = $billing['city'] ?? '';
        $order->receiverDistrict = '';
        $order->receiverAddress = implode(', ', array_filter([
            $billing['address_1'] ?? '',
            $billing['address_2'] ?? '',
            $billing['city'] ?? '',
            $billing['state'] ?? '',
            $billing['postcode'] ?? '',
            $billing['country'] ?? '',
        ]));
    }

    protected function mapShipping(XOrder $order, array $rawData): void
    {
        $shipping = $rawData['shipping'] ?? [];
        if (empty($shipping)) {
            return;
        }

        if (empty($order->receiverName)) {
            $order->receiverName = trim(($shipping['first_name'] ?? '') . ' ' . ($shipping['last_name'] ?? ''));
        }
        if (empty($order->receiverProvince)) {
            $order->receiverProvince = $shipping['state'] ?? '';
        }
        if (empty($order->receiverCity)) {
            $order->receiverCity = $shipping['city'] ?? '';
        }
        if (empty($order->receiverAddress)) {
            $order->receiverAddress = implode(', ', array_filter([
                $shipping['address_1'] ?? '',
                $shipping['address_2'] ?? '',
                $shipping['city'] ?? '',
                $shipping['state'] ?? '',
                $shipping['postcode'] ?? '',
                $shipping['country'] ?? '',
            ]));
        }
    }

    protected function mapLogistics(XOrder $order, array $rawData): void
    {
        $shippingLines = $this->get($rawData, 'shipping_lines', []);
        foreach ($shippingLines as $line) {
            $metaData = $line['meta_data'] ?? [];
            foreach ($metaData as $meta) {
                if (($meta['key'] ?? '') === '_tracking_number') {
                    $order->trackingNo = $meta['value'] ?? '';
                }
                if (($meta['key'] ?? '') === '_tracking_company') {
                    $order->logisticsCompany = $meta['value'] ?? '';
                }
            }
        }

        $metaData = $this->get($rawData, 'meta_data', []);
        foreach ($metaData as $meta) {
            if (($meta['key'] ?? '') === '_tracking_number' && empty($order->trackingNo)) {
                $order->trackingNo = $meta['value'] ?? '';
            }
            if (($meta['key'] ?? '') === '_tracking_company' && empty($order->logisticsCompany)) {
                $order->logisticsCompany = $meta['value'] ?? '';
            }
        }
    }

    protected function extractSellerNote(array $rawData): string
    {
        $notes = $rawData['order_notes'] ?? [];
        $sellerNotes = [];
        foreach ($notes as $note) {
            if (!($note['customer_note'] ?? false)) {
                $sellerNotes[] = $note['note'] ?? '';
            }
        }
        return implode('; ', $sellerNotes);
    }

    protected function mapItem(array $rawItem): XOrderItem
    {
        $item = new XOrderItem();
        $item->goodsId = (string) $this->get($rawItem, 'product_id');
        $item->sku = (string) $this->get($rawItem, 'variation_id');
        $item->goodsName = $this->get($rawItem, 'name');
        $item->skuSpec = $this->extractSkuSpec($rawItem);
        $item->goodsImage = '';
        $item->quantity = (int) $this->get($rawItem, 'quantity', 0);
        $item->price = $this->toFloat($this->get($rawItem, 'price'));
        $item->subtotal = $this->toFloat($this->get($rawItem, 'subtotal'));
        $item->subtotalTax = $this->toFloat($this->get($rawItem, 'subtotal_tax'));
        $item->outerNo = $this->get($rawItem, 'sku');

        return $item;
    }

    protected function extractSkuSpec(array $rawItem): string
    {
        $metaData = $this->get($rawItem, 'meta_data', []);
        $specs = [];
        foreach ($metaData as $meta) {
            $key = $meta['key'] ?? '';
            if ($key === '_variation_attributess' || strpos($key, 'pa_') === 0 || strpos($key, 'attribute_') === 0) {
                $specs[] = ($meta['display_key'] ?? $key) . ': ' . ($meta['display_value'] ?? $meta['value'] ?? '');
            }
        }
        return implode('; ', $specs);
    }

    protected function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        return round((float) $value, 2);
    }

    protected function formatIsoTime($datetime): ?string
    {
        if (empty($datetime)) {
            return null;
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
