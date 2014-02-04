<?php
class Magestore_Auction_IndexController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
    {
		if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
		Mage::helper('auction')->updateAuctionStatus();	
		$this->loadLayout();  
		$this->getLayout()
				->getBlock('head')
				->setTitle(Mage::helper('core')->__('Jatica - Veilingen en Stockverkoop'));
				
		$toolbar = $this->getLayout()->getBlock('product_list_toolbar');
		$toolbar->addPagerLimit('grid',8,'8');
		$toolbar->addPagerLimit('grid',16,'16');
		$toolbar->addPagerLimit('grid',40,'40');
		$toolbar->addPagerLimit('grid','all','All');
		
		$this->renderLayout();
	}
	
	public function checkbiddernameAction()
	{
		$html = "";
		$bidder_name = $this->getRequest()->getParam('bidder_name');
		
		$collection = Mage::getResourceModel('customer/customer_collection')
						->addAttributeToFilter('bidder_name',$bidder_name);
		if(count($collection)){
			$html .= '<input type="hidden" value="2" id="is_valid_bidder_name">';
			$html .= '<div class="error-msg"><p>'.Mage::helper('core')->__('This biddder name is existed').'</p></div>';
		}else{
			$html .= '<input type="hidden" value="1" id="is_valid_bidder_name">';
			$html .= '<div class="success-msg"><p>'.Mage::helper('core')->__('You can use this bidder name').'</p></div>';			
		}
		$this->getResponse()->setBody($html);
	}
	
	public function savebiddernameAction()
	{
		$bidder_name = $this->getRequest()->getPost('bidder_name');
		$collection = Mage::getResourceModel('customer/customer_collection')
						->addAttributeToFilter('bidder_name',$bidder_name);
		if(!count($collection))
		{		
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			try{
				$customer->setBidderName($bidder_name)
						->save()
						;
				$backUrl = Mage::getSingleton('core/session')->getData('auction_backurl');
				if($backUrl){
					Mage::getSingleton('core/session')->unsetData('auction_backurl');
					$this->getResponse()->setRedirect($backUrl);
					return;
				}	
				
				$this->_redirect('auction/index/customerbid',array());
				return;
			} catch(Exception $e){
				$this->_redirect('auction/index/customerbid',array());
				Mage::getSingleton('core/session')->addError($e->getMessage());
				return;
			}
		}else{
			$backUrl = Mage::getSingleton('core/session')->getData('auction_backurl');
			if($backUrl){
				Mage::getSingleton('core/session')->unsetData('auction_backurl');
				$this->_redirect($backUrl);
				return;
			}	
			$this->_redirect('auction/index/customerbid',array());
			return;
		}
	}
	
	public function updateauctioninfoAction()
	{		
		$auction_id = $this->getRequest()->getParam('id');
		$tmpl = $this->getRequest()->getParam('tmpl');
		$cur_bid_id = $this->getRequest()->getParam('current_bid_id');
		
		Mage::helper('auction')->updateAuctionStatus($auction_id);	
			
		$auction = Mage::getModel('auction/productauction')->load($auction_id);
		$lastBid = $auction->getLastBid();
		$auction->setLastBid($lastBid);
		
		if( (int)$cur_bid_id == (int)$lastBid->getId()) //not updated
			return;
		
		$result = null;
		if($tmpl == 'auctioninfo'){
			$new_endtime = strtotime($auction->getEndTime().' '.$auction->getEndDate());
			$now_time = Mage::getSingleton('core/date')->timestamp(time());
		
			$result .= '<div id="result_auction_id">'.$auction->getId().'</div>';
			$result .= '<div id="result_auction_end_time_'.$auction->getId().'">'.$new_endtime.'</div>';
			$result .= '<div id="result_auction_now_time_'.$auction->getId().'">'.$now_time.'</div>';
			$result .= '<div id="result_auction_info_'.$auction->getId().'">'.$this->_getAuctionInfo($auction,$lastBid).'</div>';
			$result .= '<div id="result_price_condition_'.$auction->getId().'">'.$this->_getPriceAuction($auction,$lastBid).'</div>';	
			$result .= '<div id="result_current_bid_id_'.$auction->getId().'">'.$lastBid->getId().'</div>';
		} else {
			$result .= '<div id="result_product_id">'.$auction->getProductId().'</div>';
			$result .= '<div id="result_auction_info_'.$auction->getProductId().'">'.$this->_getAuctionInfo($auction,$lastBid,$tmpl).'</div>';
		}
		$this->getResponse()->setHeader('Content-type', 'application/x-json');
		$this->getResponse()->setBody($result);			
	}
	
	public function updatepriceconditionAction()
	{
		$auction_id = $this->getRequest()->getParam('id');
		$auction = Mage::getModel('auction/productauction')->load($auction_id);
		$lastBid = $auction->getLastBid();
		$min_next_price = $auction->getMinNextPrice();
		$max_next_price = $auction->getMaxNextPrice();
		$max_condition = $max_next_price ? ' '.$this->__('and').' '. Mage::helper('core')->currency($max_next_price) : '';
	
		if($max_condition)
			$html = '( '.Mage::helper('core')->__('between').' '. Mage::helper('core')->currency($min_next_price) . $max_condition .' )';
		else
			$html = '( '.Mage::helper('core')->__('greater than').' '. Mage::helper('core')->currency($min_next_price) .' )';
					
		$this->getResponse()->setHeader('Content-type', 'application/x-json');
		$this->getResponse()->setBody($html);		
	}
	
	public function customerbidAction()
	{
		if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
		if(!Mage::getSingleton('customer/session')->isLoggedIn())
		{	
			$this->_redirect('customer/account/login',array());
			return;
		}
		
		Mage::helper('auction')->updateAuctionStatus();
		$this->loadLayout(); 
		$this->getLayout()
				->getBlock('head')
				->setTitle(Mage::helper('core')->__('My Bids'));		
				
		$listBidBlock = $this->getLayout()->getBlock('customerbid');
		$pager= $this->getLayout()->createBlock('page/html_pager','auction.bid.pager')
					->setCollection($listBidBlock->getListCustomerbid());
		$listBidBlock->setChild('pager', $pager);
		$this->renderLayout();		
	}
	
	public function watchlistAction()
	{
		if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
		if(!Mage::getSingleton('customer/session')->isLoggedIn()){	
			$this->_redirect('customer/account/login',array());
			return;
		}
		
		Mage::helper('auction')->updateAuctionStatus();
		$this->loadLayout(); 
		$this->getLayout()
				->getBlock('head')
				->setTitle(Mage::helper('core')->__('My Watched Auctions'));
				
		$listAuctionBlock = $this->getLayout()->getBlock('watchlist');
		$pager= $this->getLayout()->createBlock('page/html_pager','watchlist.pager')
					->setCollection($listAuctionBlock->getAuctionCollection());
		$listAuctionBlock->setChild('pager', $pager);
		$this->renderLayout();		
	}	
	
	public function autobidlistAction()
	{
		if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
		if(!Mage::getSingleton('customer/session')->isLoggedIn()){	
			$this->_redirect('customer/account/login',array());
			return;
		}
		
		Mage::helper('auction')->updateAuctionStatus();
		$this->loadLayout(); 
		$this->getLayout()
				->getBlock('head')
				->setTitle(Mage::helper('core')->__('My Auto Bids'));		
				
		$listAutoBidBlock = $this->getLayout()->getBlock('autobidlist');
		$pager= $this->getLayout()->createBlock('page/html_pager','autobidlist.pager')
					->setCollection($listAutoBidBlock->getBidCollection());
		$listAutoBidBlock->setChild('pager', $pager);
		$this->renderLayout();		
	}		
	
	public function checkoutAction()
	{
		if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
		$bid_id = $this->getRequest()->getParam('id');
		
		if(!$bid_id)
		{
			$this->_redirect('*/*/customerbid',array());
			return;		
		}
		
		Mage::getSingleton('core/session')->setData('bid_id',$bid_id);	
		$bid = Mage::getModel('auction/auction')->load($bid_id);
		
			//check authentication
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if(! $customer || ($customer->getId() != $bid->getCustomerId()))
		{
			$this->_redirect('*/*/customerbid',array());
			return;				
		}

		if($bid->getStatus == 6) //complete bid
		{
			$this->_redirect('*/*/customerbid',array());
			return;		
		}
		
		$product = Mage::getModel('catalog/product')->load($bid->getProductId());
			
		$cart = Mage::getSingleton('checkout/cart');
		
		try{
			Mage::getSingleton('core/session')->setData('auction_is_disabled_remove_item',false);	
				//set cart empty
			if($cart->getItemsQty() > 0)
			{
				$items = $cart->getItems();
				foreach($items as $item)
					$cart->removeItem($item->getId());
			}
			Mage::getSingleton('core/session')->setData('auction_is_disabled_remove_item',true);	
			$product->setData('price',$bid->getPrice());
		
			$cart->addProduct($product);
			$cart->save();

			Mage::getSingleton('checkout/session')->setCartWasUpdated(true);		
		
			$this->_redirect('checkout/onepage',array());

		
		} catch(Exception $e) {
			Mage::getSingleton('core/session')->setData('bid_addcart_error',$e->getMessage());
			$this->_redirect('*/*/customerbid',array());
		}
	}
	
	public function viewbidsAction()
	{
		if(!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)){return;}
		$auction_id = $this->getRequest()->getParam('id');
		
		Mage::helper('auction')->updateAuctionStatus($auction_id);	
		
		if(! $auction_id)
		{
			$this->_redirect('');
			return;
		}
		
		$auction = Mage::getModel('auction/productauction')->load($auction_id);		
		
		if( ($auction->getStatus() != 4) && ($auction->getStatus() != 5)) //not processing, not completed
		{
			if($auction->getId())
				$this->_redirect('catalog/product/view',array('id'=>$auction->getProductId()));
			else
				$this->_redirect('');
				
			return;
		}
		Mage::register('productauction_data',$auction);
		Mage::register('product',Mage::getModel('catalog/product')->load($auction->getProductId()));
		
		$this->loadLayout();     
		$this->getLayout()
				->getBlock('head')
				->setTitle(Mage::helper('core')->__('Bid History').' - '.$auction->getProductName());
		
		$listBidBlock = $this->getLayout()->getBlock('auction.history');

		$pager= $this->getLayout()->createBlock('page/html_pager','auction.bid.pager')
					->setCollection($listBidBlock->getListProductBid());
		
		$listBidBlock->setChild('pager', $pager);			
		
		$this->renderLayout();	
	}
	
	public function changewatcherAction(){
		$result = null;
		$_helper = Mage::helper('auction');
		$notice = Mage::getSingleton('auction/notice');
		$this->getResponse()->setHeader('Content-type', 'application/x-json');
		$productId = $this->getRequest()->getParam('product_id');
		$isWatcher = $this->getRequest()->getParam('is_watcher');
		//check login
		if(! Mage::getSingleton('customer/session')->isLoggedIn())
		{
			if(isset($_SERVER['HTTP_REFERER'])){
				$backUrl = $_SERVER['HTTP_REFERER'];
				Mage::getSingleton('core/session')->setData('auction_backurl',$backUrl);			
			}			
			$this->_redirect('customer/account/login');
			return;
		}
		
		$auction = Mage::getModel('auction/productauction')->loadAuctionByProductId($productId);
		$product = Mage::getModel('catalog/product')->load($auction->getProductId());
		
		if($auction->getStatus() == 5) //complete auction
		{
			$this->getResponse()->setRedirect($product->getProductUrl());			
			return;					
		}
		
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$model = Mage::getModel('auction/watcher')->getCollection()
				->addFieldToFilter('productauction_id', $auction->getId())
				->addFieldToFilter('customer_id', $customer->getId())
				->getFirstItem();
			
		$storeId = Mage::app()->getStore()->getId();	
			
		if($model->getId()) {
			$model->setStatus($isWatcher);
		} else {
			$model->setProductauctionId($auction->getId())
					->setCustomerId($customer->getId())
					->setCustomerName($customer->getName())
					->setCustomerEmail($customer->getEmail())
					->setStoreId($storeId)
					->setStatus($isWatcher);
		}
		
		try{
			$model->setCreatedTime(Mage::getSingleton('core/date')->timestamp(time()))
				->save();
			
			$this->getResponse()->setRedirect($product->getProductUrl());
			
			return;
		}catch(Exception $e){
			$this->getResponse()->setRedirect($product->getProductUrl());
		}
	}
	
	public function bidAction()
	{
		$result = null;
		$_helper = Mage::helper('auction');
		$notice = Mage::getSingleton('auction/notice');
		$this->getResponse()->setHeader('Content-type', 'application/x-json');
		
		//check login
		if(! Mage::getSingleton('customer/session')->isLoggedIn())
		{
			if(isset($_SERVER['HTTP_REFERER'])){
				$backUrl = $_SERVER['HTTP_REFERER'];
				Mage::getSingleton('core/session')->setData('auction_backurl',$backUrl);			
			}			
			$result .= $notice->getNoticeError($_helper->__('You did not login yet'));
			$this->getResponse()->setBody($result);	
			return;
		}
		
		$bidType = $this->getRequest()->getParam('bid_type');
		$data['price'] = $this->getRequest()->getParam('bid_price');
		$data['product_id'] = $this->getRequest()->getParam('product_id');
		
		if(!isset($data['price']) || !$data['price']){
			$result .= $notice->getNoticeError($_helper->__('Invalid bid price'));
			$this->getResponse()->setBody($result);				
			return;
		}
		
		$data['price'] = str_replace(',','',$data['price']);
		$data['price'] = str_replace(' ','',$data['price']);
		
		$customerSession = Mage::getSingleton('customer/session');
		
		if(Mage::getStoreConfig('auction/general/bidder_name_type') == '2')
		{
			if(!$customerSession->getCustomer()->getBidderName())
			{
				if(isset($_SERVER['HTTP_REFERER'])){
					$backUrl = $_SERVER['HTTP_REFERER'];
					Mage::getSingleton('core/session')->setData('auction_backurl',$backUrl);			
				}				
				$result .= $notice->getNoticeError($_helper->__('You have not bidder name'));
				$this->getResponse()->setBody($result);						
				return;
			}
		}
		
		$product = Mage::getModel('catalog/product')->load($data['product_id']);
		$auction = Mage::getModel('auction/productauction')->loadAuctionByProductId($data['product_id']);
		
		if($auction->getStatus() == 5) //complete auction
		{
			$result .= $notice->getNoticeError($_helper->__('Completed Auction'));
			$this->getResponse()->setBody($result);					
			return;					
		}		
		
		$timestamp = Mage::getModel('core/date')->timestamp(time());
		
		$lastBid = $auction->getLastBid();


		if(! Mage::helper('auction')->checkValidBidPrice($data['price'],$auction, $bidType))
		{
			$result .= $notice->getNoticeError($_helper->__('Invalid bid price'));
			$this->getResponse()->setBody($result);				
			return;		
		}
		$customer = $customerSession->getCustomer();
		$data['productauction_id'] = $auction->getId();
		$data['customer_id'] = $customer->getId();
		$data['customer_name'] = $customer->getName();
		$data['customer_email'] = $customer->getEmail();
		$store_id = Mage::app()->getStore()->getId();
		
		//prepare bidder name
		if(Mage::getStoreConfig('auction/general/bidder_name_type') == '1')
		{
			$data['bidder_name'] = $_helper->encodeBidderName($auction,$customer);
		}else{
			$data['bidder_name'] = $customer->getBidderName();
		}
		//end bidder name
		
		if($bidType){
			$auctionbid = Mage::getModel('auction/auction');			
			$lastAuctionBid = $auction->getLastBid();
			if($customer->getId() == $lastAuctionBid->getCustomerId()) {
		
				$result .= $notice->getNoticeError($_helper->__('Your bid is highest'));
				$this->getResponse()->setBody($result);	
				return;
			}
		
			$data['product_name'] = $product->getName();
			$data['created_date'] = date('Y-m-d',$timestamp);
			$data['created_time'] = date('H:i:s',$timestamp);
			$data['status'] = 3;//waiting		
			$auctionbid->setData($data)
						->setStoreId($store_id);
						
			//get autobids greater  current price (before save)
			$autobids =  Mage::getModel('auction/autobid')->getCollection()
					->addFieldToFilter('productauction_id', $auction->getProductauctionId())
					->addFieldToFilter('price', array('gteq'=>$auction->getMinNextPrice()));
					
			$autobidIds = array();				
			foreach($autobids as $autobid){
				$autobidIds[] = $autobid->getId();
			}
		
			try{
				$auctionbid->save();
				$auction->setLastBid($auctionbid);
				$auctionbid->setAuction($auction);
				$auctionbid->emailToWatcher();
				$auctionbid->emailToBidder();
				$auctionbid->emailToAdmin();
				
				//$auctionbid->noticeOverbid();
				$auctionbid->sendNoticToAllBider($auctionbid->getCustomerId(), $auctionbid->getProductauctionId());
				//get autobids over
				$overAutobids = Mage::getModel('auction/autobid')->getCollection()
						->addFieldToFilter('productauction_id', $auction->getProductauctionId())
						->addFieldToFilter('price', array('lt'=>$auction->getMinNextPrice()))
						->addFieldToFilter('autobid_id', array('in'=>$autobidIds))
						;
						
				if(count($overAutobids))		
					$auctionbid->noticeOverautobid($overAutobids);
				
				if(strtotime($auction->getEndDate().' '. $auction->getEndTime()) - $timestamp <= $auction->getLimitTime()) {
					$newTime = $timestamp + (int) $auction->getLimitTime();
					$new_endDate = date('Y-m-d', $newTime);
					$new_endTime = date('H:i:s', $newTime);
					$auction->setEndDate($new_endDate)
							->setEndTime($new_endTime);	
					$auction->save();
				}
			
				if(isset($newTime)){
					$result .= '<div id="result_auction_end_time_'.$auction->getId().'">'.$newTime.'</div>';
					$result .= '<div id="result_auction_now_time_'.$auction->getId().'">'.$timestamp.'</div>';
				}
				$result .= '<div id="result_auction_id">'.$auction->getId().'</div>';
				$result .= '<div id="result_auction_info_'.$auction->getId().'">'.$this->_getAuctionInfo($auction,$auctionbid).'</div>';
				$result .= '<div id="result_price_condition_'.$auction->getId().'">'.$this->_getPriceAuction($auction,$auctionbid).'</div>';
				$result .= '<div id="result_current_bid_id_'.$auction->getId().'">'.$auctionbid->getId().'</div>';
				$result .= $notice->getNoticeSuccess();
				$this->getResponse()->setBody($result);
				
				$store = Mage::app()->getStore();
				$baseCurrency = $store->getBaseCurrency();
				$currCurrency = $store->getCurrentCurrency();
				if ($baseCurrency->getCode() != $currCurrency->getCode()){
					$store->setCurrentCurrencyCode($baseCurrency->getCode());
					$store->setData('current_currency',$baseCurrency);
				}
				$lastBid = $auction->getLastBid();
				$new_endtime = strtotime($auction->getEndTime().' '.$auction->getEndDate());
				$now_time = Mage::getSingleton('core/date')->timestamp(time());
				$result  = '<div id="result_auction_id">'.$auction->getId().'</div>';
				$result .= '<div id="result_auction_end_time_'.$auction->getId().'">'.$new_endtime.'</div>';
				$result .= '<div id="result_auction_now_time_'.$auction->getId().'">'.$now_time.'</div>';
				$result .= '<div id="result_auction_info_'.$auction->getId().'">'.$this->_getAuctionInfo($auction,$lastBid).'</div>';
				$result .= '<div id="result_price_condition_'.$auction->getId().'">'.$this->_getPriceAuction($auction,$lastBid).'</div>';
				$result .= '<div id="result_current_bid_id_'.$auction->getId().'">'.$lastBid->getId().'</div>';
				
				$auctionbid->setAuctioninfo($result);
				
				$result  = '<div id="result_product_id">'.$auction->getProductId().'</div>';
				$result .= '<div id="result_auction_info_'.$auction->getProductId().'">'.$this->_getAuctionInfo($auction,$lastBid,'auctionlistinfo').'</div>';
				
				$auctionbid->setAuctionlistinfo($result)->save();
				if ($baseCurrency->getCode() != $currCurrency->getCode()){
					$store->setCurrentCurrencyCode($currCurrency->getCode());
					$store->setData('current_currency',$currCurrency);
				}
			} catch(Exception $e) {
				$result .=  $notice->getNoticeError($e->getMessage());
				$this->getResponse()->setBody($result);					
			}
				
		}  else {
			$autobid = Mage::getModel('auction/autobid')->getCollection()
					->addFieldToFilter('customer_id', $customer->getId())
					->addFieldToFilter('productauction_id', $auction->getId())
					->addFieldToFilter('price', array('gt'=>$auction->getMinNextPrice()))
					->getFirstItem();
			$check_autobid = Mage::getStoreConfig('auction/general/auto_bid');                      
			if($check_autobid == 1){
				$data['created_time'] = date('Y-m-d H:i:s',Mage::getSingleton('core/date')->timestamp(time()));
				$autobid->setData($data)
						->setStoreId($store_id);
						
				try{
					$autobid->save();
					$autobid->emailToBidder();
					$result .= $notice->getNoticeSuccess($_helper->__('Autobid successful'));
					$this->getResponse()->setBody($result);
				}catch(Exception $e){
					$result .=  $notice->getNoticeError($e->getMessage());
					$this->getResponse()->setBody($result);		
				} 
				
			}elseif ($check_autobid == 2 && !($autobid->getId())){
				$data['created_time'] = date('Y-m-d H:i:s',Mage::getSingleton('core/date')->timestamp(time()));
				$autobid->setData($data)
						->setStoreId($store_id);
						
				try{
					$autobid->save();
					$autobid->emailToBidder();
					$result .= $notice->getNoticeSuccess($_helper->__('Autobid successful'));
					$this->getResponse()->setBody($result);
				}catch(Exception $e){
					$result .=  $notice->getNoticeError($e->getMessage());
					$this->getResponse()->setBody($result);		
				} 
				
			}
			else{
				$result .= $notice->getNoticeError($_helper->__('You have placed a autobid for this auction'));
				$this->getResponse()->setBody($result);						
				return;
			}
		}									
	}

	public function cancelBidAction()
	{
		$id = $this->getRequest()->getParam('id');
		
		$bid = Mage::getModel('auction/auction')->load($id);
		
		if($bid->getStatus() != 1 && $bid->getStatus() != 3)
		{
			$this->_redirect('auction/index/customerbid');
			return;
		}
		try{
			$bid->setStatus(2)->save();
			Mage::getSingleton('core/session')->addSuccess(Mage::helper('auction')->__('Cancel bid sucessful!'));
			$this->_redirect('auction/index/customerbid');
		} catch(Exception $e){
			Mage::getSingleton('core/session')->addSuccess(Mage::helper('auction')->__('Cancel bid failed!'));
			$this->_redirect('auction/index/customerbid');
		}
	}	
	
	protected function _getAuctionInfo($auction,$lastBid = null,$tmpl= null)
	{
		$lastBid = $lastBid ? $lastBid : $auction->getLastBid();
		$tmpl = $tmpl ? $tmpl : 'auctioninfo';
		$auction->setLastBid($lastBid);
		$block = $this->getLayout()->createBlock('auction/auction');
		$block->setTemplate('auction/'.$tmpl.'.phtml');
		$block->setData('auction',$auction);		
		
		return $block->toHtml();
	}
	
	protected function _getPriceAuction($auction,$lastBid = null)
	{
		$auction->setCurrentPrice(null)
				->setMinNextPrice(null)
				->setMaxNextPrice(null);
				
		$min_next_price = $auction->getMinNextPrice();
		$max_next_price = $auction->getMaxNextPrice();
		$max_condition = $max_next_price ? ' '.Mage::helper('core')->__('and').' '. Mage::helper('core')->currency($max_next_price) : '';
		if($max_condition)
			$html = '( '.Mage::helper('core')->__('between').' '. Mage::helper('core')->currency($min_next_price) . $max_condition .' )';
		else
			$html = '( '.Mage::helper('core')->__('greater than').' '. Mage::helper('core')->currency($min_next_price) .' )';			
		
		return $html;
	}
/*$conditionTime = strtotime($auction->getEndDate().' '. $auction->getEndTime()) - $auction->getLimitTime();
				$limitEndDate = date('Y-m-d', $conditionTime);
				$limitEndTime = date('H:i:s', $conditionTime);
				$bidCollection = Mage::getModel('auction/auction')->getTwentyBidderLastest($auction->getId(), $limitEndTime, $limitEndDate);
				
				if(count($bidCollection) >= 20) {
				
					$newTime = $timestamp + Mage::helper('auction')->getAddditionTime();
	
					$new_endDate = date('Y-m-d', $newTime);
	
					$new_endTime = date('H:i:s', $newTime);
	
					$auction->setEndDate($new_endDate)
						->setEndTime($new_endTime);
						
					$auction->save();
				} */
}