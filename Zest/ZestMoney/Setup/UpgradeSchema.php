<?php

namespace Zest\ZestMoney\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @param  SchemaSetupInterface
     * @param  ModuleContextInterface
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) 
    {
        if (version_compare($context->getVersion(), '2.0.1') < 0) {
            $statuses = array(
            'zest_timeout' => 'Zest Timeout',
            'zest_declined' => 'Zest Declined',
            );
            $setup->startSetup();

            foreach ($statuses as $statusCode => $statusInfo) {
                $data = array('status' => $statusCode, 'label' => $statusInfo);
                if ($setup->getConnection()->fetchOne("select * from {$setup->getTable('sales_order_status')} where `status`='{$statusCode}'")) {
                    $where = array('status' => array('eq' => $statusCode));
                    $setup->getConnection()->update($setup->getTable('sales_order_status'), $data, $where);
                } else {
                    $setup->getConnection()->insert($setup->getTable('sales_order_status'), $data);
                }
                $data = array('status' => $statusCode, 'state' => 'canceled', 'is_default' => 0);
                if ($setup->getConnection()->fetchOne("select * from {$setup->getTable('sales_order_status_state')} where `status`='{$statusCode}'")) {
                    $where = array('status' => array('eq' => $statusCode));
                    $setup->getConnection()->update($setup->getTable('sales_order_status_state'), $data, $where);
                } else {
                    $setup->getConnection()->insert($setup->getTable('sales_order_status_state'), $data);
                }
            }
            $setup->endSetup();
        }
    }
}