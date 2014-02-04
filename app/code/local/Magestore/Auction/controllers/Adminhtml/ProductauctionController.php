<?php

class Magestore_Auction_Adminhtml_ProductauctionController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('auction/productauction')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Product-Auction Manager'), Mage::helper('adminhtml')->__('Product-Auction Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		if(!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)){return;}
		Mage::helper('auction')->updateAuctionStatus();
		//$this->_title($this->__('Auction'))->_title($this->__('Manage Auctions'));
		$this->_initAction()
			->renderLayout();
	}

	public function editAction(){
		if(!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)){return;}
		Mage::getSingleton('core/session')->setData('is_search',false);	
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('auction/productauction')
					->setId($id)
					->setStoreId($this->getRequest()->getParam('store'))
					->loadByStore();
					
		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('productauction_data', $model);
			
			
			$this->loadLayout();
			$this->_setActiveMenu('auction/productauction');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('ProductAuction Manager'), Mage::helper('adminhtml')->__('ProductAuction Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Add ProductAuction'), Mage::helper('adminhtml')->__('Add ProductAuction'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('auction/adminhtml_productauction_edit'))
				->_addLeft($this->getLayout()->createBlock('auction/adminhtml_productauction_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('auction')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->editAction();
		
	}
	
	public function detailAction()
	{
		$id     = $this->getRequest()->getParam('id');
		
		Mage::helper('auction')->autoUpdateBidStatus($id);
		
		$model  = Mage::getModel('auction/productauction')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('productauction_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('auction/productauction');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('ProductAuction Manager'), Mage::helper('adminhtml')->__('ProductAuction Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Add ProductAuction'), Mage::helper('adminhtml')->__('Add ProductAuction'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('auction/adminhtml_productauction_bid'))
				->_addLeft($this->getLayout()->createBlock('auction/adminhtml_productauction_edit_bidtabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('auction')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}		
	}
	
	public function changeproductAction()
	{
		$product_id = $this->getRequest()->getParam('product_id');
		if($product_id)
		{
			$product = Mage::getModel('catalog/product')->load($product_id);
			$product_name = $product->getName();
			$product_name = str_replace('"','',$product_name);
			$product_name = str_replace("'",'',$product_name);
			$html = '<input type="hidden" id="newproduct_name" name="newproduct_name" value="'. $product_name .'" >';
			$this->getResponse()->setHeader('Content-type', 'application/x-json');
			$this->getResponse()->setBody($html);				
		}
	}
	
	public function importAction()
	{	
		if(!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)){return;}
		$this->loadLayout();
		$this->_setActiveMenu('auction/productauction');
		$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Import Auctions'), Mage::helper('adminhtml')->__('Import Auctions'));
		$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
		$this->_addContent($this->getLayout()->createBlock('auction/adminhtml_productauction_import'));		
		$this->renderLayout();
	}

	public function importPostAction()
	{
		if(isset($_FILES['filecsv']))
		{
			try{
				$fileName   = $_FILES['filecsv']['tmp_name'];
				$Object  	= new Varien_File_Csv();
				$dataFile 	= $Object->getData($fileName);
				$fields = array();
				$auctionData = array();
				$auction = Mage::getModel('auction/productauction');
				$count = 0;
				if(count($dataFile))
				foreach($dataFile as $col => $row){
					if($col == 1){
						if(count($row))
						foreach($row as $index=>$cell)
							$fields[$index] = (string)$cell;			
					}elseif($col>1){
						if(count($row))
						foreach($row as $index=>$cell)
							if(isset($fields[$index])){
								$auctionData[$fields[$index]] = $cell;
							}
						if($auction->import($auctionData))
							$count++;
						$auctionData = array();
					}		
				}
			//	if($count){
					Mage::getSingleton('core/session')->addSuccess(Mage::helper('auction')->__('Imported success %s Auction(s)',$count));
			//	}
				$this->_redirect('*/*/index');

				return;

			}catch(Exception $e){
				Mage::getSingleton('core/session')->addError($e->getMessage());
				$this->_redirect('*/*/import');
				return;
			}
		}
		Mage::getSingleton('core/session')->addError(Mage::helper('auction')->__('No uploaded files'));
		$this->_redirect('*/*/import');
		return;		
	}		
	
	public function autobidlistAction()
	{
        $this->loadLayout();
        $this->renderLayout();				
	}
	
	public function watcherlistAction()
	{
        $this->loadLayout();
        $this->renderLayout();		
	}
	
	public function listproductAction()
	{
        $this->loadLayout();
        $this->getLayout()->getBlock('auction.edit.tab.product')
            ->setProduct($this->getRequest()->getPost('aproduct', null));
        $this->renderLayout();	
	}
	
	public function listproductGridAction()
	{
        $this->loadLayout();
        $this->getLayout()->getBlock('auction.edit.tab.product')
            ->setProduct($this->getRequest()->getPost('aproduct', null));
        $this->renderLayout();		
	}
	
	public function saveAction() {
		
		if ($data = $this->getRequest()->getPost()) {

			if(isset($data['candidate_product_id']) && $data['candidate_product_id'])
			{
				$data['product_id'] = $data['candidate_product_id'];
			}
			
			if(isset($data['product_name']) && $data['product_name'] == '')
			{
				unset($data['product_name']);
			}
			
			$model = Mage::getModel('auction/productauction');		
			$model->setData($data)
				->setStoreId($this->getRequest()->getParam('store'))
				->setId($this->getRequest()->getParam('id'));
			
			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}	
				
				if($model->getStoreId())
				{
					$valueModel = Mage::getModel('auction/value')->loadByAuctionStore($model->getId(),$model->getStoreId());
					$valueId = $valueModel->getId();
					$valueModel->setData($model->getData())
								->setId($valueId)
								->save();
				}else{
					$model->save();
					$valueModel = Mage::getModel('auction/value');
									
					$stores = Mage::app()->getStores();
					foreach($stores as $store)
					{
						$valueModel->loadByAuctionStore($model->getId(),$store->getId());
						$valueId = $valueModel->getId();
						$valueModel->setData($model->getData())
									->setId($valueId)
									->setStoreId($store->getId())
									->setIsApplied(1)
									->save();
					}
				}	
                
                $status = $model->getStatus();
                if(($status == 5) ||($status == 6)){
                    Mage::helper('auction')->setStautsAution($status, $model->getId());
                }				
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('auction')->__('Auction was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId(),'store'=>$this->getRequest()->getParam('store')));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('auction')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
	}
	
	public function duplicateAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		if(! $id)
		{
			$this->_redirect('*/*/index',array());
			return;
		}
		
		$auction = Mage::getModel('auction/productauction')->load($id);
		
		$auction->setId(null);
		$auction->setProductId($auction->getProductId());
		$auction->setProductName($auction->getProductName());
		$auction->setStatus(2);
		
		try{
			$auction->save();
			Mage::getSingleton('core/session')->addSuccess(Mage::helper('auction')->__('Auction was successfully duplicated'));
			$this->_redirect('*/*/edit',array('id' => $auction->getId()));
			return;
			
		} catch(Exception $e) {
			Mage::getSingleton('core/session')->addError($e->getMessage());
			$this->_redirect('*/*/edit',array('id' => $auction->getId()));
			return;			
		}
		
	}
	
	public function cancelAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		if(! $id)
		{
			$this->_redirect('*/*/index',array());
			return;
		}
		
		$auction = Mage::getModel('auction/productauction')->load($id);

		try{
			
			if($auction->getStatus() <= 4)
			{
				$auction->setStatus(2);
				$auction->save();
			}
			
			Mage::getSingleton('core/session')->addSuccess(Mage::helper('auction')->__('Auction was successfully canceled'));		
			$this->_redirect('*/*/edit',array('id' => $auction->getId()));
			return;
			
		} catch(Exception $e) {
			
			Mage::getSingleton('core/session')->addError($e->getMessage());
			$this->_redirect('*/*/edit',array('id' => $auction->getId()));			
			return;
		}
	}
 
	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel('auction/productauction');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

    public function massDeleteAction() {
        $auctionIds = $this->getRequest()->getParam('productauction');
        if(!is_array($auctionIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($auctionIds as $auctionId) {
                    $auction = Mage::getModel('auction/productauction')->load($auctionId);
					$auction->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($auctionIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
	
    public function massStatusAction()
    {
        $auctionIds = $this->getRequest()->getParam('productauction');
        if(!is_array($auctionIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($auctionIds as $auctionId) {
                    $auction = Mage::getSingleton('auction/productauction')
                        ->load($auctionId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($auctionIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
  
    public function exportCsvAction()
    {
        $fileName   = 'auction.csv';
        $content    = $this->getLayout()->createBlock('auction/adminhtml_productauction_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'auction.xml';
        $content    = $this->getLayout()->createBlock('auction/adminhtml_productauction_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}