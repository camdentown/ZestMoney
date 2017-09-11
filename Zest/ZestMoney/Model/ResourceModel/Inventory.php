<?php

namespace Zest\ZestMoney\Model\ResourceModel;

class Inventory extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context
     * @param \Zest\ZestMoney\Model\ResourceModel\Inventory\CollectionFactory
     * @param null
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Zest\ZestMoney\Model\ResourceModel\Inventory\CollectionFactory $collectionfactory,
        $resourcePrefix = null
    ) {
        $this->collectionfactory = $collectionfactory;
        parent::__construct($context, $resourcePrefix);
    }

    /**
     * @return destructor
     */
    protected function _construct() 
    {
        $this->_init('zestmoney_inventory', 'inventory_id');
    }

    /**
     * @param  \Magento\Framework\Model\AbstractModel
     * @return object
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object) 
    {
        $model = $this->_createZestInventoryCollection()->addFieldToFilter('order_id', $object->getOrderId());
        if ($model->getSize()) {
            $object->setInventoryId($model->getFirstItem()->getId());
            return parent::_beforeSave($object);
        } else {
            return parent::_beforeSave($object);
        }
    }

    /**
     * @return boolean
     */
    protected function _createZestInventoryCollection() 
    {
        return $this->collectionfactory->create();
    }

    /**
     * @param  string
     * @return string
     */
    public function getInvreduced($orderid = '') 
    {
        $inventory = $this->_createZestInventoryCollection()->addFieldToFilter('order_id', $orderid)->getFirstItem();
        return $inventory['inventory_reduced'];
    }

}