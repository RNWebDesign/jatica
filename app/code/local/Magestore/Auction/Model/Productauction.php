<?php

class Magestore_Auction_Model_Productauction extends Mage_Core_Model_Abstract
{
	const XML_PATH_SALES_EMAIL_IDENTITY = "trans_email/ident_sales";
	const XML_PATH_ADMIN_EMAIL_IDENTITY = "trans_email/ident_general";
	const XML_PATH_NOTICE_AUCTION_COMPLETED = "auction/emails/notice_auction_completed";
	const XML_PATH_NOTICE_AUCTION_COMPLETED_TO_WATCHER = "auction/emails/notice_auction_completed_towatcher";    	
    
	public function _construct()
    {
        parent::_construct();
        $this->_init('auction/productauction');
    }
	
	public function loadAuctionByProductId($product_id)
	{
		$collection = $this->getCollection()
					->addFieldToFilter('product_id',$product_id)
					->setOrder('productauction_id','DESC');

		if(! count($collection))
			return null;
			
		foreach($collection as $item)
		{
			$status = Mage::helper('auction')->getAuctionStatus($item->getId());
			if($status == 4 || $status == 5) //auction processing
				return $item;
		}
		
		return null;		
	}
	
	public function getLastBid()
	{
		if(!$this->getData('last_bid'))
		{
			$last_bid = Mage::getModel('auction/auction')->getLastAuctionBid($this->getId());
			$this->setData('last_bid',$last_bid);
		}
		return $this->getData('last_bid');
	}
	
	public function getLastautobid(){
		$lastautobid = Mage::getModel('auction/lastautobid')->getCollection()
				->addFieldToFilter('productauction_id', $this->getId())
				->setOrder('last_run_autobid_id', 'DESC')
				->getFirstItem();
				
		
		return $lastautobid;
	}
	
	public function getCurrentPrice()
	{
		$lastBid = $this->getLastBid();
		$current_price = $lastBid->getPrice() ? $lastBid->getPrice() : $this->getInitPrice();		
		
		return $current_price;
	}
	
	public function getListBid()
	{
		return Mage::getModel('auction/auction')->getCollection()
				->addFieldToFilter('productauction_id',$this->getId())
				//->addFieldToFilter('status',array('nin'=>2))
				->setOrder('auctionbid_id','DESC');
	}
	
	public function getTotalBid()
	{
		$collection = $this->getListBid();
		return $collection->getSize();
	}
	
	public function getTotalBidder()
	{
		return Mage::getResourceModel('auction/auction')->getTotalBidder($this->getId());
	}
	
	public function getTimeLeft()
	{
		$endtime = strtotime($this->getEndTime() .' '. $this->getEndDate());
		
		$timestamp = Mage::getModel('core/date')->timestamp(time());
		
		$timeleft = $endtime - $timestamp;
		
		if($timeleft <=0)
		{
			return '0 dagen 0 uur 0 minuten';
		}
		
		$days = intval($timeleft / (3600 *24));
		$time = $timeleft - $days * 3600 *24;
		
		return $days .' dagen '. date('H',$time) .' uur '. date('i',$time) .' minuten';
	}
	
	public function getMinNextPrice()
	{
		$currentPrice = $this->getCurrentPrice();
		$min_next_price = $currentPrice + $this->getMinIntervalPrice();
		return $min_next_price;
	}
	
	public function getMaxNextPrice()
	{
		$currentPrice = $this->getCurrentPrice();
		$max_interval_price = $this->getMaxIntervalPrice();
		$max_interval_price = ($max_interval_price >0 ) ? $max_interval_price : 0;
		$max_next_price = $max_interval_price ? ($currentPrice + $max_interval_price)  : 0;	
			
		return $max_next_price;
	}
	
	public function loadByStore()
	{
		$storeId = $this->getStoreId();
		$this->load($this->getId());
		if($storeId)
		{
			$valueModel = Mage::getModel('auction/value')->loadByAuctionStore($this->getId(),$storeId);
			$this->setData('is_applied',$valueModel->getData('is_applied'));
		}
		
		return $this;
	}
	
	public function getFormatedEndTime($type='medium')
	{
		$end_time = new Zend_Date($this->getEndDate().' '.$this->getEndTime(),null,'en_GB');
		return Mage::helper('core')->formatDate($end_time,$type,true);
	}
	
	public function getFormatedStartTime($type='medium')
	{
		$start_time = new Zend_Date($this->getStartDate().' '.$this->getStartTime(),null,'en_GB');
		return Mage::helper('core')->formatDate($start_time,$type,true);
	}	
	
	public function getFormatedStartPrice()
	{
		return Mage::helper('core')->currency($this->getInitPrice());
	}
	
	public function getFormatedClosePrice()
	{
		return Mage::helper('core')->currency($this->getLastBid()->getPrice());
	}

    public function getTotalClosePriceIncludingTax()
    {
        return  Mage::helper('core')->currency(($this->getLastBid()->getPrice() * 1.17) * 1.21) ;
    }

	public function getWinnerBids()
	{
		if(!$this->getData('winnerbids'))
		{
			$winnerBids = Mage::helper('auction')->getWinnerBids($this->getId());
			$this->setData('winnerbids',$winnerBids);
		}
		
		return $this->getData('winnerbids');
	}
	
	public function getWinnerEmailList(){
		$emailList = '';
		$winnerBids = Mage::helper('auction')->getWinnerBids($this->getId());
		if(count($winnerBids)){
			$i = 0;
			foreach($winnerBids as $winnerBid){
				$i++;
				$emailList .= $winnerBid->getCustomerEmail();
				if($i != count($winnerBids))
					$emailList .= ', ';
			}
		}else{
			$emailList = 'No winner';
		}
		return $emailList;
	}
	
	public function emailNoticeCompleted()
	{
		$storeID = $this->getStoreId();
		if($this->getLastBid()){
			$storeID = $this->getLastBid()->getStoreId();
		}
		
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
		
		$template = Mage::getStoreConfig(self::XML_PATH_NOTICE_AUCTION_COMPLETED,$storeID);
		
        $sendTo = array(
			Mage::getStoreConfig(self::XML_PATH_ADMIN_EMAIL_IDENTITY, $storeID)
        );
		
		$mailTemplate = Mage::getModel('core/email_template');
		 
        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$storeID))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig(self::XML_PATH_SALES_EMAIL_IDENTITY, $storeID),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'auction' => $this->setAdminName($recipient['name'])
                    )
                );
		}		

		$translate->setTranslateInline(true);			
		
		return $this;			
	}
	
	public function emailNoticeCompletedToWatcher()
	{
		$storeID = $this->getStoreId();
		if($this->getLastBid()){
			$storeID = $this->getLastBid()->getStoreId();
		}
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
		
		$bids = Mage::getModel('auction/auction')->getCollection()
				->addFieldToFilter('productauction_id', $this->getId());
		$customerIds = array();
		foreach($bids as $bid){
			$storeID = $bid->getStoreId();
			$customerIds[] = $bid->getCustomerId();
		}
		
        $watchers = Mage::getModel('auction/watcher')->getCollection()
					->addFieldToFilter('productauction_id', $this->getProductauctionId())
					->addFieldToFilter('status', 1)
					->addFieldToFilter('customer_id', array('nin'=>$customerIds));

		if(!count($watchers))
			return $this;
			
		$sendTo = array();
		$i = 0;
		foreach($watchers as $watcher){
			$customer = Mage::getModel('customer/customer')->load($watcher->getCustomerId());
			$sendTo[$i]['name'] = $customer->getName();
			$sendTo[$i]['email'] = $customer->getEmail();
			$i++;
		}
		
		$template = Mage::getStoreConfig(self::XML_PATH_NOTICE_AUCTION_COMPLETED_TO_WATCHER,$storeID);
		$mailTemplate = Mage::getModel('core/email_template');
		 
		if(count($sendTo)){
			foreach ($sendTo as $recipient) {
				$mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$storeID))
					->sendTransactional(
						$template,
						Mage::getStoreConfig(self::XML_PATH_SALES_EMAIL_IDENTITY, $storeID),
						$recipient['email'],
						$recipient['name'],
						array(
							'auction'         => $this->setCustomerName($recipient['name'])
						)
					);
			}			
		}
		$translate->setTranslateInline(true);			
		
		return $this;			
	}
	
	public function getMultiWinnerBid() {
	
		if(!$this->getData('multiwinnerbid'))
		{
			$multiwinnerbid = Mage::helper('auction')->getMultiWinnerBid($this->getId());
			$this->setData('multiwinnerbid', $winnerbid);
		}
		
		return $this->getData('multiwinnerbid');
	}
	
	public function getAuctionUrl()
	{
		$idPath = 'product/'.$this->getProductId();
		$rewrite =  Mage::getModel('core/url_rewrite')
						->setStoreId($this->getStoreId());
		$rewrite->loadByIdPath($idPath);
		if($rewrite->getId()){
			$url = Mage::getModel('core/url')->getDirectUrl($rewrite->getRequestPath());
		} else {
			$url = Mage::getModel('core/url')->getUrl('catalog/product/view',array('id'=>$this->getProductId()));
		}
		return $url;
	}	
	
	public function import($data)
	{
		$this->setData($data);
		if(!$this->getProductSku())
			return false;
		$product = Mage::getModel('catalog/product')
						->getCollection()
						->addAttributeToSelect('name')
						->addFieldToFilter('sku',trim($this->getProductSku()))
						->getFirstItem();
						
		if(!$product->getId())
			return false;
		$auction = $this->getCollection()
						->addFieldToFilter('product_id',$product->getId())
						->addFieldToFilter('status',array('in'=>array(2,4)))
						->getFirstItem();
		if($auction->getId())
			return false;
		
		$timestamp = Mage::getSingleton('core/date')->timestamp(time());
		$now = date('Y-m-d H:i:s',$timestamp);
		
		$this->setProductId($product->getId())
			->setProductName($product->getName())
			->setIsApply(1)
			->setCreatedTime($now)
			->setUpdateTime($now)
			;
		
		$this->convetStartTime();
		$this->convetEndTime();
		$this->convertStatus();
		$this->save();
		
		$valueModel = Mage::getModel('auction/value');		
		$stores = Mage::app()->getStores();
		foreach($stores as $store){
			$valueModel->loadByAuctionStore($this->getId(),$store->getId());
			$valueId = $valueModel->getId();
			$valueModel->setData($this->getData())
						->setId($valueId)
						->setStoreId($store->getId())
						->setIsApplied(1)
						->save();
						
			$valueModel->setData(null);
		}
		$this->setData(null);
		return true;
	}
	
	public function convetStartTime()
	{
		if(!$this->getStartTime())
			return;
			
		$times = $this->convertTime($this->getStartTime());
		$this->setStartTime($times['time'])
				->setStartDate($times['date']);
	}
	
	public function convetEndTime()
	{
		if(!$this->getEndTime())
			return;
			
		$times = $this->convertTime($this->getEndTime());
		$this->setEndTime($times['time'])
				->setEndDate($times['date']);
	}	
	
	public function convertTime($time_str)
	{
		$times = explode(' ',$time_str);
		$rtime = null;
		$rdate = null;
		if(isset($times[0]) && isset($times[1]))
		{
			$rtime = trim($times[0]).':00';
			$date = trim($times[1]);
		}elseif(isset($times[0])){
			$date = trim($times[0]);
		}
		if(isset($date)){
			$dates = explode('/',$date);
			if(isset($dates[0]) && isset($dates[1]) && isset($dates[2]))
				$rdate = trim($dates[2]).'-'.trim($dates[1]).'-'.trim($dates[0]);
		}
		return array('date'=>$rdate,'time'=>$rtime);		
	}
	
	public function convertStatus()
	{
		if(!$this->getStatus())
			return;
		if(strtolower(trim($this->getStatus())) == 'enabled')
			$this->setStatus(1);
		else
			$this->setStatus(2);
		return;
	}		
}