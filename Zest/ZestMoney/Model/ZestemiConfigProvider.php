<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Zest\ZestMoney\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Escaper;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Zest\ZestMoney\Block\Emi as Emi;
use Zest\ZestMoney\Helper\Data as ZestHelper;

class ZestemiConfigProvider implements ConfigProviderInterface {
	/**
	 * @var ResolverInterface
	 */
	protected $localeResolver;

	protected $methodCode = 'zestemi';

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * @var \Magento\Customer\Helper\Session\CurrentCustomer
	 */
	protected $currentCustomer;

	/**
	 * @var PaymentHelper
	 */
	protected $paymentHelper;

	public function __construct(
		ResolverInterface $localeResolver,
		CurrentCustomer $currentCustomer,
		PaymentHelper $paymentHelper,
		ZestHelper $zesthelper,
		Emi $Emi,
		Escaper $escaper
	) {
		$this->localeResolver = $localeResolver;
		$this->currentCustomer = $currentCustomer;
		$this->paymentHelper = $paymentHelper;
		$this->zesthelper = $zesthelper;
		$this->Emi = $Emi;
		$this->escaper = $escaper;
		$this->method = $paymentHelper->getMethodInstance($this->methodCode);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConfig() {
		$config = [
			'payment' => [
				'zestemi' => [
					'QuoteUrl' => $this->zesthelper->QuoteUrl(),
					'ClientId' => $this->zesthelper->ClientId(),
					'GrandTotal' => $this->Emi->GrandTotal(),
					'faqurl' => $this->zesthelper->faqurl(),
					'desktopimage' => $this->Emi->getViewFileUrl('Zest_ZestMoney::images/01PNG.png'),
					'mblimage' => $this->Emi->getViewFileUrl('Zest_ZestMoney::images/02PNG.png'),
					'mailingAddress' => $this->getMailingAddress(),
				],
			],
		];
		return $config;
	}

	/**
	 * Get mailing address from config
	 *
	 * @return string
	 */
	protected function getMailingAddress() {
		return nl2br($this->escaper->escapeHtml($this->method->getMailingAddress()));
	}

}
