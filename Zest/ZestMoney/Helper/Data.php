<?php

namespace Zest\ZestMoney\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

	protected $apiurl;

	/**
	 * @param \Magento\Framework\App\Helper\Context
	 */
	public function __construct(\Magento\Framework\App\Helper\Context $context
	) {
		$sandbox_mode = $context->getScopeConfig()->getValue(
			'payment/zestemi/sandbox_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);
		if ($sandbox_mode) {
			$this->apiurl = $context->getScopeConfig()->getValue(
				'payment/zestemi/sandbox_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
			);
		} else {
			$this->apiurl = $context->getScopeConfig()->getValue(
				'payment/zestemi/live_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
			);
		}
		$this->scopeConfig = $context->getScopeConfig();
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/zestmoney.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		parent::__construct($context);
	}

	/**
	 * @return string
	 */
	public function tokenurl() {
		$url = $this->apiurl . "Authentication/connect/token";
		return $url;
	}

	/**
	 * @return string
	 */
	public function QuoteUrl() {
		if ($this->scopeConfig->getValue('payment/zestmoney_zestpay/sandbox_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
			$url = $this->scopeConfig->getValue('payment/zestemi/live_url');
		} else {
			$url = $this->scopeConfig->getValue('payment/zestemi/sandbox_url');
		}
		return $url;
	}

	/**
	 * @return string
	 */
	public function ClientId() {
		return $this->scopeConfig->getValue('payment/zestemi/clientid');
	}

	/**
	 * @return string
	 */
	public function loanurl() {
		$url = $this->apiurl . "ApplicationFlow/LoanApplications";
		return $url;
	}

	/**
	 * @return string
	 */
	public function refundurl() {
		$url = $this->apiurl . "Loan/Refunds";
		return $url;
	}

	/**
	 * @param  string
	 * @return string
	 */
	public function cancelurl($orderid) {
		$url = $this->apiurl . "ApplicationFlow/LoanApplications/orders/" . $orderid . "/cancellation";
		return $url;
	}

	/**
	 * @param  string
	 * @return string
	 */
	public function orderstatusurl($orderid) {
		$url = $this->apiurl . "ApplicationFlow/LoanApplications/orders/" . $orderid;
		return $url;
	}

	/**
	 * @param  string
	 * @return string
	 */
	public function deliverurl($orderid) {
		$url = $this->apiurl . "Loan/DeliveryReport/" . $orderid;
		return $url;
	}

	/**
	 * @return string
	 */
	public function emiurl() {
		$url = $this->apiurl . "Pricing/quote";
		return $url;
	}

	/**
	 * @param  string
	 * @param  float
	 * @return string
	 */
	public function PricingUrl($loanamount) {
		$clientid = $this->ClientId();
		$url = $this->apiurl . "Pricing/quote?MerchantId=" . $clientid . "&LoanAmount=" . $loanamount;
		return $url;
	}

	/**
	 * @param  string
	 * @param  float
	 * @return string
	 */
	public function emifullurl($clientid, $loanamount) {
		$url = $this->apiurl . "Pricing/quote?MerchantId=" . $clientid . "&LoanAmount=" . $loanamount;
		return $url;
	}

	/**
	 * @return string
	 */
	public function loanagreementurl() {
		$url = $this->apiurl . "loanagreement";
		return $url;
	}

	/**
	 * @return string
	 */
	public function faqurl() {
		if ($this->scopeConfig->getValue('payment/zestmoney_zestpay/sandbox_mode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
			$url = 'http://stagingsite.zestmoney.in/faq/';
		} else {
			$url = 'http://zestmoney.in/faq/';
		}
		return $url;
	}

	/**
	 * @param  string
	 * @param  null
	 * @param  null
	 * @param  null
	 * @param  string
	 * @return array
	 */
	public function request($url, $postvars = null, $header = null, $getcode = null, $method = 'POST') {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (count($header)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if ($response === false) {
			$logger->info(curl_error($ch));
		}
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($getcode) {
			$result = array('response' => json_decode($response), 'http_code' => $info['http_code']);
			return $result;
		} else {
			return json_decode($response);
		}
	}

}
