<?php
namespace Zest\ZestMoney\Block\Zestemi;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Zest\ZestMoney\Helper\Data;

class Form extends \Magento\Payment\Block\Form
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = 'zestemi';

    /**
     * Paypal data
     *
     * @var Data
     */
    protected $_paypalData;

    /**
     * @var ConfigFactory
     */
    protected $_paypalConfigFactory;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var null
     */
    protected $_config;

    /**
     * @var bool
     */
    protected $_isScopePrivate;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @param Context           $context
     * @param ConfigFactory     $paypalConfigFactory
     * @param ResolverInterface $localeResolver
     * @param Data              $paypalData
     * @param CurrentCustomer   $currentCustomer
     * @param array             $data
     */
    public function __construct(
        Context $context,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {

        $this->_localeResolver = $localeResolver;
        $this->_config = null;
        $this->_isScopePrivate = true;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context, $data);
    }

    /**
     * @return null | string
     */
    public function getBillingAgreementCode() 
    {
        $customerId = $this->currentCustomer->getCustomerId();
        return $this->_paypalData->shouldAskToCreateBillingAgreement($this->_config, $customerId)
        ? Checkout::PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT
        : null;
    }

    /**
     * @return string
     */
    public function getfaqurl() 
    {
        return "http://zestmoney.in/faq/";
    }
}
