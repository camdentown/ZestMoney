<?php
namespace Zest\ZestMoney\Controller\Zestpay;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Failure extends \Magento\Framework\App\Action\Action
{

    protected $pageFactory;
    protected $urlBuilder;

    /**
     * @param Context
     * @param PageFactory
     */
    public function __construct(Context $context, PageFactory $pageFactory) 
    {
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
    public function execute() 
    {
        $session = $this->_objectManager->get('Magento\Checkout\Model\Type\Onepage')->getCheckout();
        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastRealOrderId();
        $this->zestmanager = $this->_objectManager->get('Zest\ZestMoney\Model\Manager');
        $this->zestresourceinventory = $this->_objectManager->get('Zest\ZestMoney\Model\ResourceModel\Inventory');
        $order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($lastOrderId);
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($order->canCancel() && $session->getQuote()) {
            if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW && $order->getPayment()->getMethod() == 'zestemi') {
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                $invreduced = $this->zestresourceinventory->getInvreduced($order->getIncrementId());
                if ($invreduced == '' || $invreduced != 0) {
                    $this->zestmanager->revertinventory($order);
                }

                $order->cancel()->save();
            }
        }
        if ($this->zestmanager->getpreservecart() && $session->getQuote()) {
            if ($lastQuoteId && $lastOrderId) {
                $cart = $this->_objectManager->get('Magento\Checkout\Model\Cart');
                $items = $order->getItemsCollection();
                foreach ($items as $item) {
                    try {
                        $cart->addOrderItem($item);
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        if ($this->_objectManager->get('Magento\Checkout\Model\Session')->getUseNotice(true)) {
                            $this->messageManager->addNotice($e->getMessage());
                        } else {
                            $this->messageManager->addError($e->getMessage());
                        }
                        return $resultRedirect->setPath('*/*/history');
                    } catch (\Exception $e) {
                        $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
                        return $resultRedirect->setPath('checkout/cart');
                    }
                }
                $cart->save();
                $this->messageManager->addError('order_failed in ZestMoney please try with other payment method in checkout');
                return $resultRedirect->setPath('checkout/cart');
            }
            if (!$lastQuoteId || !$lastOrderId) {
                return $resultRedirect->setPath('checkout/cart');
            }
        } else {
            return $resultRedirect->setPath('checkout/onepage/failure');
        }

    }
}