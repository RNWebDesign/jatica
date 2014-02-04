<?php

class Magestore_Auction_Block_Adminhtml_Productauction_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('productauctionGrid');
      $this->setDefaultSort('productauction_id');
      $this->setDefaultDir('DESC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('auction/productauction')->getCollection();
      $this->setCollection($collection);
      
	  return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('productauction_id', array(
          'header'    => Mage::helper('auction')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'productauction_id',
      ));

      $this->addColumn('product_name', array(
          'header'    => Mage::helper('auction')->__('Product'),
          'align'     =>'left',
          'index'     => 'product_name',
      ));

      $this->addColumn('start_date', array(
          'header'    => Mage::helper('auction')->__('Start Date'),
          'align'     =>'left',
          'index'     => 'start_date',
		  'type'      => 'date',
      ));
	  
      $this->addColumn('end_date', array(
          'header'    => Mage::helper('auction')->__('End Date'),
          'align'     =>'left',
          'index'     => 'end_date',
		  'type'      => 'date',
      ));	  

      $this->addColumn('status', array(
          'header'    => Mage::helper('auction')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => Mage::helper('auction')->getListAuctionStatus(),
      ));
	  
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('auction')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('auction')->__('View Bids'),
                        'url'       => array('base'=> '*/*/detail'),
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
        $this->setMassactionIdField('productauction_id');
        $this->getMassactionBlock()->setFormFieldName('productauction');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('auction')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('auction')->__('Are you sure?')
        ));

        //$statuses = Mage::getSingleton('auction/status')->getOptionArray();
		$statuses = Mage::helper('auction')->getListAuctionStatus();
		
        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('auction')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
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
        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}