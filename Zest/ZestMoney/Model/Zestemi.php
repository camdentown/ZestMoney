<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Zest\ZestMoney\Model;
use Exception;

//use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Pay In Store payment method model
 */
class Zestemi extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    const PAYMENT_METHOD_ZESTEMI_CODE = 'zestemi';
    protected $_code = 'zestemi';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = false;
    protected $_isInitializeNeeded = true;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @param \Magento\Framework\Model\Context
     * @param \Magento\Framework\Registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory
     * @param \Magento\Framework\Api\AttributeValueFactory
     * @param \Magento\Payment\Helper\Data
     * @param \Magento\Framework\App\Config\ScopeConfigInterface
     * @param \Magento\Payment\Model\Method\Logger
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param \Magento\Framework\UrlInterface
     * @param \Magento\Checkout\Model\Session
     * @param \Magento\Framework\Exception\LocalizedExceptionFactory
     * @param \Magento\Sales\Api\TransactionRepositoryInterface
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     * @param \Zest\ZestMoney\Model\Manager
     * @param \Magento\Framework\Message\ManagerInterface
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null
     * @param \Magento\Framework\Data\Collection\AbstractDb|null
     * @param array
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Zest\ZestMoney\Model\Manager $manager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_storeManager = $storeManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->_exception = $exception;
        $this->transactionRepository = $transactionRepository;
        $this->transactionBuilder = $transactionBuilder;
        $this->manager = $manager;
        $this->messageManager = $messageManager;

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/zestmoney.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->mgrlog = $logger;

    }

    public function initialize($paymentAction, $stateObject) 
    {
        if ($status = $this->scopeConfig->getValue('payment/zestemi/order_status')) {
            $stateObject->setStatus($status);
            $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
            $stateObject->setIsNotified(true);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderPlaceRedirectUrl() 
    {
        return $this->_urlBuilder->getUrl('zestmoney/zestpay/redirect');
    }

    /**
     * @return string
     */
    public function getCheckoutRedirectUrl() 
    {
        return $this->_urlBuilder->getUrl('zestmoney/zestpay/redirect');
    }

    /**
     * @param  \Magento\Payment\Model\InfoInterface
     * @param  float
     * @return array
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) 
    {
        try {
            if (!$this->canRefund()) {
                throw new Exception('Refund action is not available.');
            }
            if ($payment->getOrder()->getTotalPaid() != $amount) {
                $IsPartial = "true";
            }
            $RefundId = 'PR_' . $payment->getOrder()->getIncrementId() . $amount;
            $result = $this->manager->sendRefundNotify($payment, $amount, $IsPartial, $RefundId);
            if ($result['http_code'] != 201) {
                $this->mgrlog->info($payment->getOrder()->getIncrementId() . '- order refund error');
                $this->mgrlog->info(print_r($result, 1));
                throw new Exception('There was a error in this order to refund on zestmoney');

            }
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
        return $this;
    }

    /**
     * @param  \Magento\Payment\Model\InfoInterface
     * @param  float
     * @return array
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) 
    {
        $this->mgrlog->info('authorize function entered');
        if ($this->canAuthorize()) {
            $payment->setTransactionId(time());
            $payment->setIsTransactionClosed(0);
        }
        return $this;
    }

    /**
     * @param  \Magento\Payment\Model\InfoInterface
     * @param  float
     * @return array
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) 
    {
        $payment->setTransactionId(time())->setIsTransactionClosed(1);
        return $this;
    }

    /**
     * @param  \Magento\Quote\Api\Data\CartInterface|null
     * @return boolean
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) 
    {
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrencyCode();
        if ($currencyCode == 'INR') {
            return true;
        } else {
            return false;
        }
    }
}
