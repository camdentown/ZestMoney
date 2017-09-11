<?php

namespace Zest\ZestMoney\Model\ResourceModel;

class Debug extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return class
     */
    protected function _construct() 
    {
        $this->_init('zestmoney_debug', 'debug_id');
    }

}