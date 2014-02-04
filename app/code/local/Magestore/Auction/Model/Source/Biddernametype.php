<?php

class Magestore_Auction_Model_Source_Biddernametype
{
    public function toOptionArray()
    {
        return array(
            array('value'=>1, 'label'=>Mage::helper('auction')->__('System Generate')),
            array('value'=>2, 'label'=>Mage::helper('auction')->__('Customer Create')),
        );
    }
}