<?php

class Magestore_Auction_Block_Adminhtml_Auction_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('auctionGrid');
      $this->setDefaultSort('auctionbid_id');
      $this->setDefaultDir('DESC');
      $this->setSaveParametersInSession(true);
	  $this->setUseAjax(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('auction/auction')->getCollection();
	  
	  $auction_id = $this->getRequest()->getParam('id');
	  
	  $collection->addFieldToFilter('productauction_id',$auction_id);
	  
      $this->setCollection($collection);
	        
	  return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
	  $store = $this->_getStore();
	  
      $this->addColumn('auctionbid_id', array(
          'header'    => Mage::helper('auction')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'auctionbid_id',
      ));

      $this->addColumn('product_name', array(
          'header'    => Mage::helper('auction')->__('Product'),
          'align'     =>'left',
          'index'     => 'product_name',
		  'renderer'  => 'auction/adminhtml_productauction_renderer_product',
      ));

      $this->addColumn('customer_name', array(
          'header'    => Mage::helper('auction')->__('Customer'),
          'align'     =>'left',
          'index'     => 'customer_name',
		  'renderer'  => 'auction/adminhtml_productauction_renderer_customer',
      ));
	  
      $this->addColumn('bidder_name', array(
          'header'    => Mage::helper('auction')->__('Bidder Name'),
          'align'     =>'left',
          'index'     => 'bidder_name',
      ));	  
	  
	    $this->addColumn('price', array(
            'header'    => Mage::helper('auction')->__('Price'),
            'align'     =>'left',
            'index'     => 'price',
		    'type'  => 'price',
		    'currency_code' => $store->getBaseCurrency()->getCode(),			  
        ));			  
	  
      $this->addColumn('created_date', array(
          'header'    => Mage::helper('auction')->__('Date'),
          'align'     =>'right',
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
          'options'   => Mage::helper('auction')->getListBidStatus(),
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
                        'url'       => array('base'=> '*/*/edit'),
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

    protected function _prepareMassaction()
    {
        /*
		$this->setMassactionIdField('auction_id');
        $this->getMassactionBlock()->setFormFieldName('auction');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('auction')->__('Delete'),
			 */
        //     'url'      => $this->getUrl('*/*/massDelete'),
		/*
             'confirm'  => Mage::helper('auction')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('auction/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('auction')->__('Change status'),
			 */
      //       'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
	  /*
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('auction')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
		*/
        return $this;
    }

	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}

    public function getGridUrl()
    {
        return $this->getUrl('*/*/listbid', array('id'=>$this->getRequest()->getParam('id')));
    }	  
	
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }	
}