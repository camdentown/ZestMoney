<?php

namespace Zest\ZestMoney\Model;

class Inventory extends \Magento\Framework\Model\AbstractModel
{

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const CACHE_TAG = 'zestmoney_inventory';
    protected $_cacheTag = 'zestmoney_inventory';
    protected $_eventPrefix = 'zestmoney_inventory';

    /**
     * @return class
     */
    protected function _construct() 
    {
        $this->_init('Zest\ZestMoney\Model\ResourceModel\Inventory');
    }

    /**
     * @return object
     */
    public function getIdentities() 
    {
        return [self::CACHE_TAG . '_' . $this->getId(), self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return object
     */
    public function getAvailableStatuses() 
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

}