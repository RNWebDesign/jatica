<?php
class Pook_CollectInStore_Model_Address
{
	function format()
	{
		return Mage::getStoreConfig('carriers/collectinstore/name');
	}
}