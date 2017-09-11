<?php
namespace Zest\ZestMoney\Model\Observer;
use Exception;
use Magento\Framework\Event\ObserverInterface;

class OrderCancel implements ObserverInterface
{

    /**
     * @param \Psr\Log\LoggerInterface
     * @param \Zest\ZestMoney\Model\Manager
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Zest\ZestMoney\Model\Manager $manager
    ) {
        $this->_logger = $logger;
        $this->manager = $manager;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/zestmoney.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->zestlog = $logger;
    }

    /**
     * @param  \Magento\Framework\Event\Observer
     * @return class
     */
    public function execute(\Magento\Framework\Event\Observer $observer) 
    {
        try {
            $order = $observer->getEvent()->getPayment()->getOrder();
            if ($order->getPayment()->getMethod() == 'zestemi') {
                $result = $this->manager->sendCancellationNotify($order);
                if ($result['http_code'] != 200) {
                    $this->zestlog->info($order->getIncrementId() . ' cancellation error');
                    $this->zestlog->info(print_r($result['response'], 1));
                }
            }
        } catch (Exception $e) {
            $this->zestlog->info($e->getMessage());
        }
        return $this;

    }
}