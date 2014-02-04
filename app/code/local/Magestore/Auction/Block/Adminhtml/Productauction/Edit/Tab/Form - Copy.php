<?php
class Magestore_Auction_Block_Adminhtml_Productauction_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{ 
	public function __construct()
	{
		parent::__construct();
		//$this->setTemplate('auction/auction.phtml');
	}
 
     public function getProductauction()     
     { 
        if (!$this->hasData('productauction_data')) {
            $this->setData('productauction_data', Mage::registry('productauction_data'));
        }
        return $this->getData('productauction_data');   
    }
	
	protected function _prepareForm()
	{
      
	  $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('auction_edit', array('legend'=>Mage::helper('auction')->__('Auction information')));
      
	  $image_calendar = Mage::getBaseUrl('skin') .'adminhtml/default/default/images/grid-cal.gif';
	  $data = $this->getProductauction();
	  
	  $disabled = false;
	  if($this->getRequest()->getParam('store'))
		$disabled = true;
      if($data['status'] == 5 || $data['status'] == 6){
        $disabled = true;  
      }
	  //$disabled = ($data['status'] == 5) ? true : $disabled;
	  
	
      $fieldset->addField('product_name', 'text', array(
          'label'     => Mage::helper('auction')->__('Product Name'),
          'class'     => 'required-entry',
          'required'  => true,
		  'readonly'  => 'readonly',
          'name'      => 'product_name',
		  'note'      => '<a target="_blank" href="'. $this->getUrl('adminhtml/catalog_product/edit',array('id'=>$data->getProductId())) .'">'. $this->__('view') .'</a>
							<input type="hidden" name="product_id" id="product_id" value="'. $data->getProductId() .'">',
	  ));	 

      $fieldset->addField('init_price', 'text', array(
          'label'     => Mage::helper('auction')->__('Init Price'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'init_price',
		  'disabled'  => $disabled,
		  'note'      => '',
      ));

	 $fieldset->addField('reserved_price', 'text', array(
          'label'     => Mage::helper('auction')->__('Reserved Price'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'reserved_price',
		  'disabled'  => $disabled,
		  'note'      => '',
      ));

      $fieldset->addField('min_interval_price', 'text', array(
          'label'     => Mage::helper('auction')->__('Min Interval Price'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'min_interval_price',
		  'disabled'  => $disabled,		  
      ));
	  
      $fieldset->addField('max_interval_price', 'text', array(
          'label'     => Mage::helper('auction')->__('Max Interval Price'),
          'name'      => 'max_interval_price',
		  'disabled'  => $disabled,		  
      ));	

      $fieldset->addField('start_date', 'date', array(
          'label'     => Mage::helper('auction')->__('Start Date'),
          'class'     => 'required-entry',
          'required'  => true,		  
          'name'      => 'start_date',
		  'format'    => 'yyyy-MM-dd',
		  'image'     => $image_calendar,
		  'disabled'  => $disabled,		  
      ));	
	
      $fieldset->addField('start_time', 'text', array(
          'label'     => Mage::helper('auction')->__('Start Time'),	  
          'name'      => 'start_time',
		  'note'      => 'Format H:i:s, example 12:30:00',	
		  'disabled'  => $disabled,		  
      ));		
  

      $fieldset->addField('end_date', 'date', array(
          'label'     => Mage::helper('auction')->__('End Date'),
          'class'     => 'required-entry',
          'required'  => true,		  
          'name'      => 'end_date',
		  'format'    => 'yyyy-MM-dd',	
		  'image'     => $image_calendar,
		  'disabled'  => $disabled,		  
      ));
	  
      $fieldset->addField('end_time', 'text', array(
          'label'     => Mage::helper('auction')->__('End Time'),	  
          'name'      => 'end_time',
		  'note'      => 'Format H:i:s, example 12:30:00',
		  'disabled'  => $disabled,		  
      ));	


		$fieldset->addField('limit_time', 'text', array(
          'label'     => Mage::helper('auction')->__('Extended Time'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'limit_time',
		  'note'      => 'The unit is seconds',
		  'disabled'  => $disabled,		  
	  ));
	  
	  
	  $fieldset->addField('multi_winner', 'text', array(
          'label'     => Mage::helper('auction')->__('Multiple Winner'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'multi_winner',
		  'note'      => 'The number of winners in an auction ',
		  'disabled'  => $disabled,		  
	  ));

      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('auction')->__('Status'),
          'name'      => 'status',
          'values'    => Mage::helper('auction')->getOptionStatus(),
		  'disabled'  => $disabled,		  
      ));
		
	if($this->getRequest()->getParam('store'))
	{
      $fieldset->addField('is_applied', 'select', array(
          'label'     => Mage::helper('auction')->__('Is Applied'),	  
          'name'      => 'is_applied', 
		  'values'    => array(
							array('value'=>2,'label'=>$this->__('No')),
							array('value'=>1,'label'=>$this->__('Yes')),
						),
		  'onchange'  => 'changeIsApplyAuction();',
      ));		 		
	}
		
      if ( Mage::getSingleton('adminhtml/session')->getAuctionData() )
      {
          $data = Mage::getSingleton('adminhtml/session')->getAuctionData();
          Mage::getSingleton('adminhtml/session')->setAuctionData(null);
      } elseif ( Mage::registry('productauction_data') ) {
          $data = Mage::registry('productauction_data')->getData();
      }
	  if($data)
	  {
	    if(isset($data['product_id']) && $data['product_id'])
		{
			$product = Mage::getModel('catalog/product')->load($data['product_id']);
			$data['product_name'] = $product->getName();
		}
		$form->setValues($data);
	  }
	  
      return parent::_prepareForm();
	
	}	
}