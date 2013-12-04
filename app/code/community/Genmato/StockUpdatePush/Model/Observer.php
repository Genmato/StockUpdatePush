<?php

class Genmato_StockUpdatePush_Model_Observer
{

    public function qtyUpdatePush(Varien_Event_Observer $observer)
    {
        Mage::log('EVENT!');
        if (Mage::getStoreConfigFlag('genmato_stockupdatepush/configuration/enabled')) {
            $event = $observer->getEvent();
            $_item = $event->getItem();
            $_product = $_item->getProduct();

            if ((int)$_item->getData('qty') != (int)$_item->getOrigData('qty') || true) {

                $remote_url = Mage::getStoreConfig('genmato_stockupdatepush/configuration/remote_url');
                $remote_type = Mage::getStoreConfig('genmato_stockupdatepush/configuration/remote_type');

                $params = array();
                $params['product_id'] = $_product->getId();
                $params['sku'] = $_product->getSku();
                $params['qty_orig'] = $_item->getOrigData('qty');
                $params['qty_new'] = $_item->getQty();
                $params['qty_change'] = $_item->getQty() - $_item->getOrigData('qty');

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

                    $observer->setData('response', $response);
                }
            }
        }
        return $this;
    }

}