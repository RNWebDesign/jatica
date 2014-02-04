<?php
class Magestore_Auction_Block_Adminhtml_Productauction_Edit_Tab_Autobids
	extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('autobidsGrid');
      $this->setDefaultSort('autobid_id');
	  $this->setUseAjax(true);
      $this->setDefaultDir('DESC');
      $this->setSaveParametersInSession(true);
  }
	
  public function getAuctionId()
  {
	  return $this->getRequest()->getParam('id');
  }
	
  protected function _prepareCollection()
  {
	  $collection = Mage::getResourceModel('auction/autobid_collection')
							->addFieldToFilter('productauction_id',$this->getAuctionId());
					
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

    protected function _prepareColumns()
    {
		$store = $this->_getStore();
		
		$this->addColumn('autobid_id', array(
				'header'    => Mage::helper('auction')->__('ID'),
				'width'     => '50px',
				'index'     => 'autobid_id',
				'type'  => 'number',
			));

        $this->addColumn('bidder_name', array(
            'header'    => Mage::helper('auction')->__('Bidder'),
            'index'     => 'bidder_name'
        ));
		
        $this->addColumn('customer_email', array(
            'header'    => Mage::helper('auction')->__('Email'),
            'index'     => 'customer_email',
			'renderer'  => 'auction/adminhtml_productauction_renderer_customeremail',
        ));
		
	    $this->addColumn('price', array(
            'header'    => Mage::helper('auction')->__('Price'),
            'align'     =>'left',
            'index'     => 'price',
		    'type'  => 'price',
		    'currency_code' => $store->getBaseCurrency()->getCode(),			  
        ));		
		
        $this->addColumn('created_time', array(
            'header'    => Mage::helper('auction')->__('Placed Date'),
            'align'     =>'right',
            'index'     => 'created_time',
		    'type'      => 'date',
        ));	  		
	  
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getData('grid_url') ? $this->getData('grid_url') : $this->getUrl('*/*/autobidlist', array('_current'=>true));
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }	
}