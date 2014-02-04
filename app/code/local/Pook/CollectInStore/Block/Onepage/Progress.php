<?php
class Pook_CollectInStore_Block_Onepage_Progress extends Mage_Checkout_Block_Onepage_Progress
{
    /*
    * Rewrite to show collect in store rather than address in progress if collect in store option is selected.
    */
    public function getShipping()
    {
        if($this->getQuote()->getShippingAddress()->getShippingMethod() == 'collectinstore_collectinstore') {
            return Mage::getModel('pook_collectinstore/address');
        }

        return $this->getQuote()->getShippingAddress();
    }
}
