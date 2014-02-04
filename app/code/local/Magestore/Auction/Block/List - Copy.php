<?php
class Magestore_Auction_Block_List extends Mage_Catalog_Block_Product_List
{
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        if ($this->getMode() == 'grid') {
            $this->setTemplate('auction/grid.phtml');
        } else {
            $this->setTemplate('auction/list.phtml');
        }

        $this->setTemplate('auction/grid.phtml');

        return $this;
    }

    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $layer = Mage::getSingleton('catalog/layer');
            /* @var $layer Mage_Catalog_Model_Layer */

            $store_id = Mage::app()->getStore()->getId();
            $Ids = Mage::helper('auction')->getProductAuctionIds($store_id);

            $cat = $this->getRequest()->getParam('cat');



            if (!$cat) {
                Mage::log('categorie empty ');
                $productIds = Mage::getModel('catalog/category')->load($cat)->setIsAnchor(1)->getProductCollection()->getAllIds();
                $this->_productCollection = $layer->getProductCollection()
                    ->addFieldToFilter('entity_id', array('in' => $productIds));
                $this->_productCollection->addCategoryFilter(Mage::getModel('catalog/category')->load($cat)->setIsAnchor(1));
            } else {
                Mage::log('categorie full ');
                $productIds = Mage::getModel('catalog/category')->load($cat)->getProductCollection()->getAllIds();
                $this->_productCollection = $layer->getProductCollection()
                    ->addFieldToFilter('entity_id', array('in' => $productIds));

            }
            $this->_productCollection->addFieldToFilter('entity_id', array('in' => $Ids));
            Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($this->_productCollection);
            Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($this->_productCollection);

        }
        return $this->_productCollection;
    }

    public function getStoreId()
    {
        if ($this->hasData('store_id')) {
            return $this->_getData('store_id');
        }
        return Mage::app()->getStore()->getId();
    }

    public function getHistoryUrl($auction)
    {
        $auction_id = $auction->getId();

        return $this->getUrl('auction/index/viewbids', array('id' => $auction_id));
    }

    public function getColumnCount()
    {
        return 4;
    }
}