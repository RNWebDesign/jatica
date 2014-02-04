<?php

class Magestore_Auction_Model_Notice extends Varien_Object
{
	
	public function getNoticeSuccess($msg=null)
	{
		$msg = $msg ? $msg : Mage::helper('auction')->__('Bod succesvol') ;
		
		return '<input type="hidden" id="notice_success" value="'.$msg.'"/>';
	}
	
	public function getNoticeError($msg=null)
	{
		$msg = $msg ? $msg : Mage::helper('auction')->__('Error, probeer opnieuw') ;
		
		return '<input type="hidden" id="notice_error" value="'.$msg.'"/>';
	}
}