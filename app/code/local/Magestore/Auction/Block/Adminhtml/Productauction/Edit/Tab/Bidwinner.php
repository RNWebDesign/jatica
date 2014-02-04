<?php

class Magestore_Auction_Block_Adminhtml_Productauction_Edit_Tab_Bidwinner extends Mage_Adminhtml_Block_Widget_Grid
{
  
	public function __construct()
	{
		parent::__construct();
		$this->setId('winnerbidGrid');
		$this->setDefaultSort('auctionbid_id');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
	}
	
	protected function _prepareCollection()
	{
		$collection = Mage::getModel('auction/auction')->getCollection()
					->addFieldToFilter('productauction_id',$this->getRequest()->getParam('id'))
					->addFieldToFilter('status', array('in'=>array(5,6)))
					
					;
		

		$this->setCollection($collection);
		return parent::_prepareCollection();
	}
	
    protected function _prepareColumns()
  {
      $this->addColumn('auctionbid_id', array(
          'header'    => Mage::helper('auction')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'auctionbid_id',
      ));

      $this->addColumn('customer_name', array(
          'header'    => Mage::helper('auction')->__('Customer'),
          'align'     =>'left',
          'index'     => 'customer_name',
		  'renderer'  => 'auction/adminhtml_productauction_renderer_customer',
      ));
	  
      $this->addColumn('price', array(
          'header'    => Mage::helper('auction')->__('Price'),
          'align'     =>'left',
          'index'     => 'price',
      ));		  
	  
      $this->addColumn('created_date', array(
          'header'    => Mage::helper('auction')->__('Date'),
          'align'     =>'left',
          'index'     => 'created_date',
		  'type'      => 'date',
      ));	  
	   	  
      $this->addColumn('created_time', array(
          'header'    => Mage::helper('auction')->__('Time'),
          'align'     =>'left',
          'index'     => 'created_time',
		  'width'     => '50px',
      ));	 		  

      $this->addColumn('status', array(
          'header'    => Mage::helper('auction')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   =>  Mage::helper('auction')->getListBidStatus(),
      ));
	  
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('auction')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('auction')->__('View'),
                        'url'       => array('base'=> '*/adminhtml_auction/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		$this->addExportType('*/*/exportCsv', Mage::helper('auction')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('auction')->__('XML'));
	  
      return parent::_prepareColumns();
  }
}