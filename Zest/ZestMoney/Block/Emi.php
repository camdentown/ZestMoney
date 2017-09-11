<?php
namespace Zest\ZestMoney\Block;
use Magento\Framework\View\Element\Template;

class Emi extends Template {
	/**
	 * @param Template\Context
	 * @param \Zest\ZestMoney\Model\Manager
	 * @param \Zest\ZestMoney\Helper\Data
	 * @param \Magento\Catalog\Block\Product\View
	 * @param \Magento\Directory\Model\Currency
	 * @param \Magento\Framework\Locale\FormatInterface
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface
	 *
	 * @param array
	 */
	public function __construct(
		Template\Context $context,
		\Zest\ZestMoney\Model\Manager $manager,
		\Zest\ZestMoney\Helper\Data $helper,
		\Magento\Catalog\Block\Product\View $product,
		\Magento\Directory\Model\Currency $currency,
		\Magento\Framework\Locale\FormatInterface $localeFormat,
		\Magento\Checkout\Model\Session $checkoutSession,
		array $data = []
	) {
		$this->_checkoutSession = $checkoutSession;
		$this->zestmanager = $manager;
		$this->zesthelper = $helper;
		$this->product = $product;
		$this->_currency = $currency;
		$this->_localeFormat = $localeFormat;
		parent::__construct($context, $data);
	}

	/**
	 * @return boolean
	 */
	public function isZestActive() {
		return $this->scopeConfig->getValue('payment/zestemi/active');
	}

	/**
	 * @return string
	 */
	public function getClientId() {
		return $this->scopeConfig->getValue('payment/zestemi/clientid');
	}

	/**
	 * @return string
	 */
	public function getEmiurl() {
		return $this->zesthelper->emiurl();
	}

	/**
	 * @return string
	 */
	public function getbasesecureurl() {
		return $this->getStore()->getBaseUrl();
	}

	/**
	 * @return array
	 */
	public function getProductDetail() {
		$product = $this->product->getProduct();
		return $product;
	}

	/**
	 * @return json
	 */
	public function getJsonConfig() {
		$jsonconfig = $this->product->getJsonConfig();
		return json_decode($jsonconfig);
	}

	/**
	 * @return string
	 */
	public function getCurrencySymbol() {
		return $this->_currency->getCurrencySymbol();
	}

	/**
	 * @return string
	 */
	public function getpriceformat() {
		$jsonconfig = $this->product->getJsonConfig();
		$result = json_decode($jsonconfig);
		return $result->priceFormat;
	}

	/**
	 * @return string
	 */
	public function GrandTotal() {
		$quote = $this->getCheckoutSession()->getQuote();
		return $quote->getGrandTotal();
	}

	/**
	 * @return Checkout Session
	 */
	public function getCheckoutSession() {
		return $this->_checkoutSession;
	}

	/**
	 * @return float
	 */
	public function getMintotal() {
		return $this->scopeConfig->getValue('payment/zestemi/min_order_total');
	}

	/**
	 * @return float
	 */
	public function getMaxtotal() {
		return $this->scopeConfig->getValue('payment/zestemi/max_order_total');
	}

	/**
	 * @param  string
	 * @return string
	 */
	public function getZestToken($type = 'sensitive') {
		if ($type != 'sensitive') {
			return $this->zestmanager->gettoken($type);
		} else {
			return $this->zestmanager->gettoken();
		}

	}

	/**
	 * @return string
	 */
	public function getfaqurl() {
		return $this->zesthelper->faqurl();
	}

	/**
	 * @return string
	 */
	public function getQuoteUrl() {
		return $this->zesthelper->QuoteUrl();
	}
}
