<?php

class Genmato_StockUpdatePush_Model_System_Config_Source_Remote_Type
{

    CONST REMOTE_GET = 'GET';
    CONST REMOTE_POST = 'POST';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => $this::REMOTE_GET, 'label' => Mage::helper('genmato_stockupdatepush')->__('HTTP GET')),
            array('value' => $this::REMOTE_POST, 'label' => Mage::helper('genmato_stockupdatepush')->__('HTTP POST')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            $this::REMOTE_GET => Mage::helper('genmato_stockupdatepush')->__('HTTP GET'),
            $this::REMOTE_POST => Mage::helper('genmato_stockupdatepush')->__('HTTP POST'),
        );
    }

}