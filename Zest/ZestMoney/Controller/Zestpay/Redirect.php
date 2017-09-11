<?php
namespace Zest\ZestMoney\Controller\Zestpay;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Redirect extends \Magento\Framework\App\Action\Action {
	protected $pageFactory;
	protected $urlBuilder;

	/**
	 * @param Context
	 * @param PageFactory
	 */
	public function __construct(Context $context, PageFactory $pageFactory) {
		$this->pageFactory = $pageFactory;
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/zestmoney.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$this->log = $logger;
		return parent::__construct($context);
	}

	/**
	 * @return array
	 */
	public function execute() {
		$checkout = $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage')->getCheckout();
		if (!empty($checkout->getLastRealOrderId())) {
			$incrementid = $checkout->getLastRealOrderId();
			$order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($incrementid);
			$this->helper = $this->_objectManager->get('Zest\ZestMoney\Helper\Data');
			$this->manager = $this->_objectManager->get('Zest\ZestMoney\Model\Manager');
			$this->zestinventory = $this->_objectManager->get('Zest\ZestMoney\Model\Inventory');
			if ($order->getIsVirtual()) {
				$shipping = $order->getBillingAddress();
			} else {
				$shipping = $order->getShippingAddress();
			}

			$orderItems = $order->getItemsCollection();
			foreach ($orderItems as $_item) {
				if ($_item->getParentItem()) {
					continue;
				} else {
					$productInfo['Id'] = $_item->getProductId();
					$productInfo['Description'] = $_item->getName();
					$productInfo['Quantity'] = (int) $_item->getQtyOrdered();

					if ($_item->getProductType() == 'bundle') {
						$price = 0;
						foreach ($_item->getChildrenItems() as $item) {
							$price += $item->getRowTotal() - $item->getDiscountAmount() + $item->getTaxAmount() + $item->getHiddenTaxAmount() + $item->getWeeeTaxAppliedRowAmount();
						}
						$productInfo['TotalPrice'] = $price;
					} else {
						$productInfo['TotalPrice'] = $_item->getRowTotal() + $_item->getTaxAmount() + $_item->getHiddenTaxAmount() + $_item->getWeeeTaxAppliedRowAmount() - $_item->getDiscountAmount();
					}

					$productInfo2[] = $productInfo;
				}
			}

			/* To add shipping price in product */
			if ($order->getShippingInclTax() > 0) {
				$shipinfo['Id'] = 'Shipid';
				$shipinfo['Description'] = 'Shipping price';
				$shipinfo['Quantity'] = 1;
				$shipinfo['TotalPrice'] = $order->getShippingInclTax();
				$productInfo2[] = $shipinfo;
			}
			$coFields['merchantid'] = '23432sdsd';
			$coFields['OrderId'] = $order->getIncrementId();
			$coFields['EmailAddress'] = $order->getCustomerEmail();
			if ($order->getCustomerIsGuest()) {
				$coFields['MerchantCustomerId'] = 'Guest';
			} else {
				$customerid = $order->getCustomerId();
				$coFields['MerchantCustomerId'] = $customerid;
			}

			$coFields['BasketAmount'] = $order->getGrandTotal();
			$discountamount = $order->getDiscountAmount();
			$coFields['Basket'] = $productInfo2;

			$coFields['FullName'] = $shipping->getFirstname() . " " . $shipping->getLastname();
			$shipaddress = $shipping->getStreet();
			$addr = 1;
			foreach ($shipaddress as $shipad) {
				$coFields['AddressLine' . $addr] = $shipad;
				$addr++;
			}
			$coFields['DeliveryPostCode'] = $shipping->getPostcode();
			$coFields['City'] = $shipping->getCity();
			$coFields['MobileNumber'] = $shipping->getTelephone();
			$coFields['website'] = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();
			$coFields['ApprovedUrl'] = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getUrl('zestmoney/zestpay/success/');
			$failureurl = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getUrl('zestmoney/zestpay/failure/');

			$coFields['ReturnUrl'] = $failureurl;

			$postdata = http_build_query($coFields);
			$loanurl = $this->helper->loanurl();
			$header = $this->manager->_getheader();
			$response = $this->helper->request($loanurl, $postdata, $header);
			if (isset($response->LogonUrl)) {
				$this->manager->revertinventory($order);
				$order->addStatusToHistory($order->getStatus(), 'Customer was redirected to zestmoney.');
				$order->save();
				$this->zestinventory->setOrderId($order->getIncrementId())->setInventoryReduced(0)->save();
				return $this->resultRedirectFactory->create()->setUrl($response->LogonUrl);
			} else {
				$this->log->info($order->getIncrementId() . ' - Error message');
				$this->log->info(print_r($response, 1));
				unset($order);
				return $this->resultRedirectFactory->create()->setPath('zestmoney/zestpay/failure', ['_current' => true]);
			}
		} else {
			$this->log->info('Error no real order id');
			return $this->resultRedirectFactory->create()->setPath('checkout/cart', ['_current' => true]);
		}
	}
}