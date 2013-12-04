<?php

class Genmato_StockUpdatePush_Model_Observer
{

    public function catalogInventorySave(Varien_Event_Observer $observer)
    {
        if ($this->isEnabled()) {
            $event = $observer->getEvent();
            $_item = $event->getItem();

            if ((int)$_item->getData('qty') != (int)$_item->getOrigData('qty')) {
                $params = array();
                $params['product_id'] = $_item->getProductId();
                if ($_item->getProduct()) {
                    $params['sku'] = $_item->getProduct()->getSku();
                } else {
                    $product = Mage::getModel('catalog/product')->load($_item->getProductId());
                    $params['sku'] = $product->getSku();
                    $product->clearInstance();
                }
                $params['qty'] = $_item->getQty();
                $params['qty_change'] = $_item->getQty() - $_item->getOrigData('qty');

                $this->pushQtyUpdate($params, 'cataloginventory_save', $_item->getProductId());
            }
        }
        return $this;
    }

    public function subtractQuoteInventory(Varien_Event_Observer $observer)
    {
        if ($this->isEnabled()) {
            $quote = $observer->getEvent()->getQuote();
            foreach ($quote->getAllItems() as $item) {
                $params = array();
                $params['product_id'] = $item->getProductId();
                $params['sku'] = $item->getSku();
                $params['qty'] = $item->getProduct()->getStockItem()->getQty();
                $params['qty_change'] = ($item->getTotalQty() * -1);

                $this->pushQtyUpdate($params, 'order_create', $quote->getId());
            }
        }
        return $this;
    }

    public function revertQuoteInventory(Varien_Event_Observer $observer)
    {
        if ($this->isEnabled()) {
            $quote = $observer->getEvent()->getQuote();
            foreach ($quote->getAllItems() as $item) {
                $params = array();
                $params['product_id'] = $item->getProductId();
                $params['sku'] = $item->getSku();
                $params['qty'] = $item->getProduct()->getStockItem()->getQty();
                $params['qty_change'] = ($item->getTotalQty());

                $this->pushQtyUpdate($params, 'order_create_failed', $quote->getId());
            }
        }
        return $this;
    }

    public function cancelOrderItem(Varien_Event_Observer $observer)
    {
        if ($this->isEnabled()) {
            $item = $observer->getEvent()->getItem();
            $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
            $params = array();
            $params['product_id'] = $item->getProductId();
            $params['sku'] = $item->getSku();
            $params['qty'] = $item->getProduct()->getStockItem()->getQty();
            $params['qty_change'] = $qty;

            $this->pushQtyUpdate($params, 'order_cancel_item', $item->getId());
        }
        return $this;
    }

    public function refundOrderInventory(Varien_Event_Observer $observer)
    {
        if ($this->isEnabled()) {
            $creditmemo = $observer->getEvent()->getCreditmemo();
            foreach ($creditmemo->getAllItems() as $item) {
                $params = array();
                $params['product_id'] = $item->getProductId();
                $params['sku'] = $item->getSku();
                if ($item->getProduct()) {
                    $params['qty'] = $item->getProduct()->getSku();
                } else {
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                    $params['qty'] = $product->getStockItem()->getQty();
                    $product->clearInstance();
                }
                $params['qty_change'] = ($item->getQty());

                $this->pushQtyUpdate($params, 'order_creditmemo_create', $creditmemo->getIncrementId());
            }
        }
        return $this;
    }

    protected function pushQtyUpdate($params, $source = null, $sourceId = null)
    {

        $remote_url = Mage::getStoreConfig('genmato_stockupdatepush/configuration/remote_url');
        $remote_type = Mage::getStoreConfig('genmato_stockupdatepush/configuration/remote_type');

        if (!is_null($source)) {
            $params['source'] = $source;
        }
        if (!is_null($sourceId)) {
            $params['source_id'] = $sourceId;
        }

        $url_param = http_build_query($params);

        if ($remote_type != Genmato_StockUpdatePush_Model_System_Config_Source_Remote_Type::REMOTE_POST) {
            $remote_url = $remote_url . '?' . $url_param;
        }

        if ($remote = curl_init()) {
            curl_setopt($remote, CURLOPT_URL, $remote_url);
            curl_setopt($remote, CURLOPT_HEADER, 0);
            curl_setopt($remote, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($remote, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($remote, CURLOPT_SSL_VERIFYPEER, false);
            if ($remote_type == Genmato_StockUpdatePush_Model_System_Config_Source_Remote_Type::REMOTE_POST) {
                curl_setopt($remote, CURLOPT_POST, 1);
                curl_setopt($remote, CURLOPT_POSTFIELDS, $url_param);
            }

            $response = curl_exec($remote);
            curl_close($remote);

            return $response;
        }
        return false;

    }

    protected function isEnabled()
    {
        return Mage::getStoreConfigFlag('genmato_stockupdatepush/configuration/enabled');
    }

}