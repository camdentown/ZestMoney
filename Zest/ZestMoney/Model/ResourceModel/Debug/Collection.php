<?php
namespace Zest\ZestMoney\Model\ResourceModel\Debug;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'debug_id';
    protected $storeManager;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface
     * @param \Psr\Log\LoggerInterface
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface
     * @param \Magento\Framework\Event\ManagerInterface
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->storeManager = $storeManager;
    }

    /**
     * @return [type]
     */
    protected function _construct() 
    {
        $this->_init('Zest\ZestMoney\Model\Debug', 'Zest\ZestMoney\Model\ResourceModel\Debug');
    }
}