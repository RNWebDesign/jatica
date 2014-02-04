<?php

class Magestore_Auction_Model_Event {
	
	public function catalog_product_save_after($observer)
	{
		$product = $observer->getProduct();
		
		$auctions = Mage::getResourceModel('auction/productauction_collection')
								->addFieldToFilter('product_id',$product->getId());
		try{
			if(count($auctions))
			foreach($auctions as $auction)
			{
				if($auction->getProductName() != $product->getName())
					$auction->setProductName($product->getName())
								->save();
			}
			
			$bids = Mage::getResourceModel('auction/auction_collection')
									->addFieldToFilter('product_id',$product->getId());
									
			if(count($bids))
			foreach($bids as $bid)
			{
				if($bid->getProductName() != $product->getName())
					$bid->setProductName($product->getName())
								->save();
			}
		}catch(Exception $e){
		}
	}
	
	public function customer_save_after($observer)
	{
		$customer = $observer->getCustomer();
		
		$bids = Mage::getResourceModel('auction/auction_collection')
								->addFieldToFilter('customer_id',$customer->getId());
		
		$bidderNameType = Mage::getStoreConfig('auction/general/bidder_name_type');
		$changed = false;
		
		try{
			if(count($bids))
			foreach($bids as $bid)
			{
				if($bid->getCustomerName() != $customer->getName())
				{
					$changed = true;
					$bid->setCustomerName($customer->getName());
				}
				if($bidderNameType == '2' && $bid->getBidderName() != $customer->getBidderName())
				{	
					$changed = true;
					$bid->setBidderName($customer->getBidderName());
				}
				if($changed)
					$bid->save();
			}	
		}catch(Exception $e){
		
		}
	}	
	
	public function customer_login($observer)
	{
		$backUrl = Mage::getSingleton('core/session')->getData('auction_backurl');
		if($backUrl){
			Mage::getSingleton('customer/session')->setBeforeAuthUrl($backUrl);
			Mage::getSingleton('core/session')->unsetData('auction_backurl');
		}
	}	
	
	public function customer_logout($observer)
	{
		if(Mage::getSingleton('core/session')->getData('bid_id'))
			Mage::getSingleton('core/session')->unsetData('bid_id');
	}	
	
	public function salesQuoteRemoveItem($observer)
	{
		$bid_id = Mage::getSingleton('core/session')->getData('bid_id');
		if(!$bid_id)
			return;
			
		$item = $observer['quote_item'];
		$quote = Mage::getSingleton('checkout/session')->getQuote();
		if(! $this->_isEnabledRemoveItem())
		{
			$quote->addProduct($item->getProduct());
			Mage::getSingleton('checkout/session')->addNotice(Mage::helper('checkout')->__('Checkout Auction! can not remove Item'));
		}
	}
	
	public function update_cart($observer)
	{
		if(! Mage::getSingleton('core/session')->getData('bid_id'))
			return;
		
		if(! isset($observer['info']) || ! isset($observer['cart']))
			return;	
		
		try{
		
		$bid = Mage::getModel('auction/auction')->load(Mage::getSingleton('core/session')->getData('bid_id')); 
		
		$cart = $observer['cart'];
		$items = $cart->getItems();
		
		if(! count($items))
		{
			return;
		}
		
		$this->_enabledRemoveItem();
		
		foreach($items as $item)
		{
			if($item->getProduct()->getId() == $bid->getProductId())
			{
				$cart->removeItem($item->getId());
				$cart->addProduct($item->getProduct());

			} else {
				$cart->removeItem($item->getId());
			}
		}
		
		$cart->save();
		
		$this->_disabledRemoveItem();
		
		Mage::getSingleton('checkout/session')->addNotice(Mage::helper('adminhtml')->__('Checkout Auction! can not update product quality'));	
		
		 } catch(Exception $e) {
				Mage::getSingleton('core/session')->setData('myerror',$e->getMessage());
				$this->_disabledRemoveItem();
		 }
		
	}
	
	public function add_product($observer)
	{
		if(! Mage::getSingleton('core/session')->getData('bid_id'))
			return;		
		
		if(!isset($observer['product']))
			return;

		try{	
			
			$bid = Mage::getModel('auction/auction')->load(Mage::getSingleton('core/session')->getData('bid_id')); 
			
			$cart = Mage::getSingleton('checkout/cart');
			
			$items = $cart->getItems();
			
			if(! count($items))
				return;
				
			$this->_enabledRemoveItem();
			
			foreach($items as $item)
			{
				if($item->getProduct()->getId() != $bid->getProductId())
				{
					$data[$item->getId] = array('remove'=>true);
					
				}  else {
					$data[$item->getId] = array('qty'=>1);
				}
			}
			
			$cart->updateItems($data);
			
			$this->_disabledRemoveItem();
			
			Mage::getSingleton('checkout/session')->addNotice('Checkout Auction! can not add product to cart');
		
		} catch(Exception $e){
			Mage::getSingleton('core/session')->setData('myerror',$e->getMessage());
			$this->_disabledRemoveItem();
		}
	}
	
	public function getFinalPrice($observer)
	{
		$bid_id = Mage::getSingleton('core/session')->getData('bid_id');
		
		if(! $bid_id)
			return;	
			
		if(!isset($observer['product']))
			return;
			
		$product = $observer['product'];
		
		$bid = Mage::getModel('auction/auction')->load($bid_id); 			
		
		if($product->getId() == $bid->getProductId())
			$product->setData('final_price',$bid->getPrice());
	}
	
	public function afterSaveOrder($observer)
	{
		$bid_id = Mage::getSingleton('core/session')->getData('bid_id');
		
		if(! $bid_id)
			return;		
	
		try{
			$observer_data = $observer->getData();		
			$order =  $observer_data['order'];
			$order_id = $order->getId();
			
			$bid = Mage::getModel('auction/auction')->load($bid_id);
			$bid->setStatus(6);
			$bid->setOrderId($order_id);
			$bid->save();
			
			$transactionModel = Mage::getModel('auction/transaction');
			$transactionModel->setOrderId($order_id)
					->setProductauctionId($bid->getProductauctionId())
					->save();
					
			
			Mage::getSingleton('core/session')->setData('bid_id',null);

			Mage::getSingleton('core/session')->setData('myerror',null);
		}
		
        catch (Exception $e) {
            Mage::getSingleton('core/session')->setData('myerror',$e->getMessage());
			Mage::getSingleton('core/session')->unsetData('bid_id',null);
        }		
	}
	
	public function update()
	{	
		Mage::helper('auction')->updateAuctionStatus();	
	}
	
	protected function _enabledRemoveItem()
	{
		Mage::getSingleton('core/session')->setData('auction_is_disabled_remove_item',false);
	}
	
	protected function _disabledRemoveItem()
	{
		Mage::getSingleton('core/session')->setData('auction_is_disabled_remove_item',true);
	}	
	
	protected function _isEnabledRemoveItem()
	{
		if(Mage::getSingleton('core/session')->getData('auction_is_disabled_remove_item'))
			return false;
		
		return true;
	}
	
	public function autobid()
	{	
		//auto update auction
		Mage::helper('auction')->updateAuctionStatus();	
		//end auto update auction
			
		$auctions = Mage::getModel('auction/productauction')->getCollection()
					->addFieldToFilter('status', 4);
					
		foreach($auctions as $auction){
			$product = Mage::getModel('catalog/product')->load($auction->getProductId());
			$price = $auction->getMinNextPrice();
			$autobids = Mage::getModel('auction/autobid')->getCollection()
						->addFieldToFilter('productauction_id', $auction->getId())
						->setOrder('created_time', 'ASC');
						
			$lastautobid = $auction->getLastautobid();

			if(!$lastautobid->getId()){
				$autobid = $autobids->getFirstItem();//no autobid run
			}else{
				foreach ($autobids as $currentAutobid){
					if($lastautobid->getAutobidId() == $currentAutobid->getId()){
						$autobid = Mage::helper('auction')->getNextAutobidToRun($auction, $currentAutobid);
						break;
					}
				}
			}
			
			if(!$autobid || !$autobid->getId()){
				continue;
			} else {
				$model = Mage::getModel('auction/lastautobid');
				$model->setProductauctionId($auction->getId())
						->setAutobidId($autobid->getId())
						->save();
			}
			
			$lastAuctionBid = $auction->getLastBid();
			
			if($lastAuctionBid->getCustomerId() != $autobid->getCustomerId()){
				//die('xxxx');
				$timestamp = Mage::getModel('core/date')->timestamp(time());
				$auctionbid = Mage::getModel('auction/auction');
				$customer = Mage::getModel('customer/customer')->load($autobid->getCustomerId());
				$data['price'] = $price;
				$data['product_id'] = $auction->getProductId();
				$data['productauction_id'] = $auction->getId();
				$data['customer_id'] = $customer->getId();
				$data['customer_name'] = $customer->getName();
				$data['customer_email'] = $customer->getEmail();
				$data['product_name'] = $product->getName();
				$data['created_date'] = date('Y-m-d',$timestamp);
				$data['created_time'] = date('H:i:s',$timestamp);
				$data['status'] = 3;//waiting	
				$store_id = $autobid->getStoreId();
				
				//prepare bidder name
				if(Mage::getStoreConfig('auction/general/bidder_name_type') == '1')
				{
					$data['bidder_name'] = Mage::helper('auction')->encodeBidderName($auction,$customer);
				}else{
					$data['bidder_name'] = $customer->getBidderName();
				}
				//end bidder name
				
				$auctionbid->setData($data)
						->setStoreId($store_id);
						
				//get autobids greater  current price (before save)
				$activeAutobids =  Mage::getModel('auction/autobid')->getCollection()
							->addFieldToFilter('productauction_id', $auction->getProductauctionId())
							->addFieldToFilter('price', array('gteq'=>$auction->getMinNextPrice()));
						
				$autobidIds = array();				
				foreach($activeAutobids as $autobid){
					$autobidIds[] = $autobid->getId();
				}

				$auctionbid->save();
				$auction->setLastBid($auctionbid);
				
				// fix not reset extend time when autobid
				if(strtotime($auction->getEndDate().' '. $auction->getEndTime()) - $timestamp <= $auction->getLimitTime()) {
					$newTime = $timestamp + (int) $auction->getLimitTime();
					$new_endDate = date('Y-m-d', $newTime);
					$new_endTime = date('H:i:s', $newTime);
					$auction->setEndDate($new_endDate)
							->setEndTime($new_endTime);	
					$auction->save();
				}
				
				$auctionbid->emailToBidder();
				$auctionbid->emailToAdmin();
				$auctionbid->emailToWatcher();
				// $auctionbid->noticeOverbid();
                $auctionbid->sendNoticToAllBider($auctionbid->getCustomerId(), $auctionbid->getProductauctionId(),$lastAuctionBid->getCustomerId());
				
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
				
				//get autobids over
				$overAutobids = Mage::getModel('auction/autobid')->getCollection()
						->addFieldToFilter('productauction_id', $auction->getProductauctionId())
						->addFieldToFilter('price', array('lt'=>$auction->getMinNextPrice()))
						->addFieldToFilter('autobid_id', array('in'=>$autobidIds))
						;
						
			
				if(count($overAutobids))		
					$auctionbid->noticeOverautobid($overAutobids);
			}
		}

	}
	
	protected function _getAuctionInfo($auction,$lastBid = null,$tmpl= null){
		$lastBid = $lastBid ? $lastBid : $auction->getLastBid();
		$tmpl = $tmpl ? $tmpl : 'auctioninfo';
		$auction->setLastBid($lastBid);
		$block = Mage::getModel('core/layout')->createBlock('auction/auction');
		$block->setTemplate('auction/'.$tmpl.'.phtml');
		$block->setData('auction',$auction);
		
		return $block->toHtml();
	}
	
	protected function _getPriceAuction($auction,$lastBid = null){
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
}