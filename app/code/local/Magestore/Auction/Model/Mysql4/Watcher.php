<?php

class Magestore_Auction_Model_Mysql4_Watcher extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the auction_id refers to the key field in your database table.
        $this->_init('auction/watcher', 'watcher_id');
    }
	
	
}