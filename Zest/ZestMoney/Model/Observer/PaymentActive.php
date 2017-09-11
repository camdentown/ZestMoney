<?php
namespace Zest\ZestMoney\Model\Observer;
use Magento\Framework\Event\ObserverInterface;

class PaymentActive implements ObserverInterface
{

    /**
     * @param \Psr\Log\LoggerInterface
     * @param \Zest\ZestMoney\Model\Manager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Zest\ZestMoney\Model\Manager $manager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_logger = $logger;
        $this->manager = $manager;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/zestmoney.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->zestlog = $logger;
    }

    /**
     * @param  \Magento\Framework\Event\Observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer) 
    {
        $event = $observer->getEvent();
        $method = $event->getMethodInstance();
        $result = $event->getResult();
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        if (($method->getCode() == 'paypal_express' || $method->getCode() == 'checkmo' || $method->getCode() == 'paypal_express_bml' || $method->getCode() == 'payflow_express' || $method->getCode() == 'payflow_express_bml') && $this->scopeConfig->getValue(
            'payment/zestemi/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )
        ) {
            if ($currencyCode == 'INR') {
                $result->setData('is_available', true);
            } else {
                $result->setData('is_available', false);
            }
        }
    }
}