<?php

namespace Zest\ZestMoney\Model;
use Exception;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Store\Model\ScopeInterface;

class Manager extends \Magento\Framework\Model\AbstractModel
{
    protected $helper;

    /**
     * @param \Magento\Framework\Model\Context
     * @param \Magento\Framework\Registry
     * @param \Magento\Sales\Model\Order
     * @param \Magento\Customer\Model\Customer
     * @param \Magento\Quote\Model\Quote
     * @param \Zest\ZestMoney\Helper\Data
     * @param \Zest\ZestMoney\Model\Inventory
     * @param \Zest\ZestMoney\Model\ResourceModel\Inventory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface
     * @param \Magento\Config\Model\ResourceModel\Config
     * @param \Magento\CatalogInventory\Model\StockManagement
     * @param \Magento\CatalogInventory\Api\StockStateInterface
     * @param \Magento\Framework\Mail\Template\TransportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param PaymentHelper
     * @param OrderIdentity
     * @param Renderer
     * @param RequestInterface
     * @param StockRegistryProviderInterface
     * @param StockConfigurationInterface
     * @param array
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order $order,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Quote\Model\Quote $quote,
        \Zest\ZestMoney\Helper\Data $helper,
        \Zest\ZestMoney\Model\Inventory $inventory,
        \Zest\ZestMoney\Model\ResourceModel\Inventory $resourceinventory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\CatalogInventory\Model\StockManagement $stockmanagement,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        PaymentHelper $paymentHelper,
        OrderIdentity $identityContainer,
        Renderer $addressRenderer,
        RequestInterface $request,
        StockRegistryProviderInterface $stockRegistryProvider,
        StockConfigurationInterface $stockConfiguration,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_registry = $registry;
        $this->_appState = $context->getAppState();
        $this->_eventManager = $context->getEventDispatcher();
        $this->_cacheManager = $context->getCacheManager();
        $this->_logger = $context->getLogger();
        $this->_actionValidator = $context->getActionValidator();
        $this->_resourceConfig = $resourceConfig;
        $this->helper = $helper;
        $this->order = $order;
        $this->customer = $customer;
        $this->quote = $quote;
        $this->request = $request;
        $this->zestresourceinventory = $resourceinventory;
        $this->zestinventory = $inventory;
        $this->stockmanagement = $stockmanagement;
        $this->stockRegistryProvider = $stockRegistryProvider;
        $this->stockConfiguration = $stockConfiguration;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->stockState = $stockState;
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->identityContainer = $identityContainer;
        $this->eventManager = $this->getMockForAbstractClass('Magento\Framework\Event\ManagerInterface');

        if (method_exists($this->_resource, 'getIdFieldName')
            || $this->_resource instanceof \Magento\Framework\DataObject
        ) {
            $this->_idFieldName = $this->_getResource()->getIdFieldName();
        }

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/zestmoney.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->mgrlog = $logger;

        parent::__construct($context, $registry);
        $this->_construct();
    }

    /**
     * @return helper
     */
    protected function _gethelper() 
    {
        return $this->helper;
    }

    /**
     * @param  string
     * @return array
     */
    public function _getheader($type = 'sensitive') 
    {
        if ($type != 'sensitive') {
            $tokendata = $this->gettoken($type);
        } else {
            $tokendata = $this->gettoken();
        }

        $response = json_decode($tokendata);
        $header = array();
        $header[] = 'Authorization: ' . $response->token_type . ' ' . $response->access_token;
        return $header;
    }

    /**
     * @return array
     */
    protected function _getRefundReasonCode() 
    {
        return array('DamagedGoods', 'GoodsNotReceived', 'NotAsDescribed', 'GOGW', 'PricingChanged', 'BuyerRemorse');
    }

    /**
     * @return array
     */
    public function _getCancellingreason() 
    {
        return array('Damaged', 'NotReceived', 'NotAsDescribed', 'GestureOfGoodWill', 'PricingChanged', 'BuyerRemorse');
    }

    /**
     * @return array
     */
    protected function _getZestCancelStatus() 
    {
        return array('Declined', 'CancelledTimeout', 'Cancelled');
    }

    /**
     * @param  string
     * @return boolean
     */
    protected function _isZestRefundCode($commenttext) 
    {
        return in_array($commenttext, $this->_getRefundReasonCode());
    }

    /**
     * @param  object
     * @return block
     */
    protected function getPaymentHtml($order) 
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $this->identityContainer->getStore()->getStoreId()
        );
    }

    /**
     * @param  object
     * @return string | null
     */
    protected function getFormattedShippingAddress($order) 
    {
        return $order->getIsVirtual()
        ? null
        : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    /**
     * @param  object
     * @return object
     */
    protected function getFormattedBillingAddress($order) 
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * @return string
     */
    public function getpreservecart() 
    {
        return $this->scopeConfig->getValue('payment/zestemi/preservecart_failure');
    }

    /**
     * @param  string
     * @return string
     */
    public function gettoken($type = 'sensitive') 
    {
        $gettoken = ($type == 'sensitive') ? $this->scopeConfig->getValue('payment/zestemi/token') : $this->scopeConfig->getValue('payment/zestemi/token_public');
        $gettokendata = unserialize($gettoken);
        if (time() > $gettokendata['expirytime'] || empty($gettoken) || empty($gettokendata['token'])) {
            $tokendata = $this->getaccesstoken($type);
            $expirytime = time() + $tokendata->expires_in - 60;
            $encodetoken = json_encode($tokendata);
            $tokenarray = array('token' => $encodetoken, 'expirytime' => $expirytime);
            $storetoken = serialize($tokenarray);
            if ($type != 'sensitive') {
                $this->_resourceConfig->saveConfig('payment/zestemi/token_public', $storetoken, 'default', 0);
            } else {
                $this->_resourceConfig->saveConfig('payment/zestemi/token', $storetoken, 'default', 0);
            }
            return $encodetoken;
        } else {
            return $gettokendata['token'];
        }

    }

    /**
     * @param  string
     * @return array
     */
    public function getaccesstoken($type = 'sensitive') 
    {
        $tokenurl = $this->_gethelper()->tokenurl();
        $merchantid = $this->scopeConfig->getValue(
            'payment/zestemi/clientid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $password = $this->scopeConfig->getValue(
            'payment/zestemi/clientsecret', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $scope = ($type == 'sensitive') ? 'merchant_api_sensitive' : 'merchant_api';
        $fields = array('grant_type' => 'client_credentials', 'scope' => $scope, 'client_id' => $merchantid, 'client_secret' => $password);
        $postdata = http_build_query($fields);
        return $this->_gethelper()->request($tokenurl, $postdata);
    }

    /**
     * @param  object
     * @param  float
     * @param  string
     * @param  string
     * @return object
     */
    public function sendRefundNotify($payment, $amount, $IsPartial, $RefundId) 
    {

        $result = null;
        $postdata = $this->request->getPost();
        if (!empty($postdata['creditmemo']['comment_text'])) {
            $commenttext = $postdata['creditmemo']['comment_text'];
            $commenttext = ($this->_isZestRefundCode($commenttext)) ? $commenttext : 'Other';
        } else {
            $commenttext = 'Other';
        }
        $order = $payment->getOrder();
        $refundurl = $this->_gethelper()->refundurl();
        $header = $this->_getheader();
        $fields = array(
        'OrderId' => $order->getIncrementId(),
        'RefundValue' => $amount,
        'ReasonCode' => $commenttext,
        'RefundDate' => date('Y-m-d', time()),
        'IsPartial' => $IsPartial,
        'RefundId' => $RefundId,
        );
        $postdata = http_build_query($fields);
        $data = $this->_gethelper()->request($refundurl, $postdata, $header, 1);
        return $data;
    }

    /**
     * @param  object
     * @return array
     */
    public function sendCancellationNotify($order) 
    {
        if ($order->getIncrementId()) {
            $cancelurl = $this->_gethelper()->cancelurl($order->getIncrementId());
            $header = $this->_getheader();
            $commentsObject = $order->getStatusHistoryCollection(true);
            $comment = array();
            foreach ($commentsObject as $commentObj) {
                $comment[] = $commentObj->getComment();
            }
            $cancelreason = $this->_getCancellingreason();
            $reasonarray = array_intersect($comment, $cancelreason);
            $reason = (!empty($reasonarray)) ? reset($reasonarray) : 'Other';
            $fields = array('Reason' => $reason);
            $postdata = http_build_query($fields);
            $data = $this->_gethelper()->request($cancelurl, $postdata, $header, 1);
            return $data;
        }
    }

    /**
     * @param  string
     * @param  string
     * @return object
     */
    public function sendDeliverNotify($orderid, $status) 
    {
        $deliverurl = $this->_gethelper()->deliverurl($orderid);
        $header = $this->_getheader();
        $fields = array('DeliveryStatus' => $status);
        $postdata = http_build_query($fields);
        $data = $this->_gethelper()->request($deliverurl, $postdata, $header, 1);
        return $data;
    }

    /**
     * @param  object
     * @return object
     */
    public function getorderstatus($order) 
    {
        if ($order->getIncrementId()) {
            $statusurl = $this->_gethelper()->orderstatusurl($order->getIncrementId());
            $header = $this->_getheader();
            $data = $this->_gethelper()->request($statusurl, null, $header, 1, 'GET');
            return $data;
        }
    }

    /**
     * @param  object
     */
    public function revertinventoryb($order) 
    {
        foreach ($order->getAllItems() as $item) {
            $qty = $item->getQtyOrdered();
            $children = $item->getChildrenItems();
            $productId = $item->getProductId();
            if ($item->getId() && $productId && empty($children) && $qty) {
                $this->stockmanagement->backItemQty($productId, $qty);
            }
        }
    }

    /**
     * @param  object
     */
    public function revertinventory($order) 
    {
        foreach ($order->getAllItems() as $item) {
            $this->_eventManager->dispatch('sales_order_item_cancel', ['item' => $item]);
        }
    }

    /**
     * @param  object
     */
    public function reduceinventoryb($order) 
    {
        $items = array();
        foreach ($order->getAllItems() as $item) {
            $items[$item->getProductId()] = $item->getQtyOrdered();
        }
        $this->stockmanagement->registerProductsSale($items);
    }

    /**
     * @param  object
     */
    public function reduceinventory($order) 
    {
        $quoteId = $order->getQuoteId();
        $quote = $this->quote->load($quoteId);
        $this->_eventManager->dispatch('sales_model_service_quote_submit_before', ['order' => $order, 'quote' => $quote]);
        $this->zestinventory->setOrderId($order->getIncrementId())->setInventoryReduced(1)->save();
    }

    /**
     * @param  array
     * @return array
     */
    public function updateorder($request) 
    {
        try {
            $orderid = '';
            if (!empty($request['orderno']) && !empty($request['status']) && !empty($request['applicationid']) && !empty($request['key'])) {
                $orderid = $request['orderno'];
                $status = $request['status'];
                $applicationid = $request['applicationid'];
                $key = $request['key'];
                $merchantid = $this->scopeConfig->getValue(
                    'payment/zestemi/clientid', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $zestmerchant = $this->scopeConfig->getValue(
                    'payment/zestemi/zest_merchant', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $hashkey = hash('sha512', $orderid . '|' . $zestmerchant . '|' . $status);
                if ($hashkey == $key) {
                    $order = $this->order;
                    $order->loadByIncrementId($orderid);
                    if ($order->getIncrementId() == $orderid) {
                        if ($order->getPayment()->getMethod() == 'zestemi') {
                            if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW && $status == 'Approved') {
                                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
                                $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                $order->addStatusToHistory($order->getStatus(), 'Order approved successfully with reference ' . $applicationid);
                                $order->save();
                                $result = array('status' => 'success', 'message' => 'order updated successfully');
                            } elseif ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW && in_array($status, $this->_getZestCancelStatus())) {
                                $this->cancelmerchantorder($order, $status);
                                $result = array('status' => 'success', 'message' => 'order cancelled successfully');
                            } elseif ($status == 'DepositPaid') {
                                $this->reduceinventory($order);
                                $result = array('status' => 'success', 'message' => 'Reduce Inventory');
                            } else {
                                throw new Exception('order not updated');
                            }
                        } else {
                            throw new Exception('not a zest money order');
                        }
                    } else {
                        throw new Exception('No order found');
                    }
                } else {
                    throw new Exception('Unauthorized');
                }
            } else {
                throw new Exception('Improper details');
            }
        } catch (Exception $e) {
            $result = array('status' => 'failed', 'message' => $e->getMessage());
        }
        return $result;
    }

    /**
     * @param  array
     * @return array
     */
    public function checkinventory($request) 
    {
        try {
            $orderid = '';
            if (!empty($request['orderno']) && !empty($request['key'])) {
                $orderid = $request['orderno'];
                $key = $request['key'];
                $zestmerchant = $this->scopeConfig->getValue(
                    'payment/zestemi/zest_merchant', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $hashkey = hash('sha512', $orderid . '|' . $zestmerchant);
                if ($hashkey == $key) {
                    $order = $this->order;
                    $order->loadByIncrementId($orderid);
                    if ($order->getIncrementId() == $orderid) {
                        if ($order->getPayment()->getMethod() == 'zestemi') {
                            if (!empty($request['payment']) || !empty($request['NumberOfInstallments'])) {
                                if (!empty($request['NumberOfInstallments'])) {
                                    $loanterm = $request['NumberOfInstallments'];
                                    $order->addStatusToHistory($order->getStatus(), 'EMI : ' . $loanterm . ' months');
                                }
                                if (!empty($request['payment'])) {
                                    $paidamount = $request['payment'];
                                    $order->addStatusToHistory($order->getStatus(), 'The amount paid by user : Rs. ' . $paidamount);
                                }
                                $order->save();
                            }
                            $items = $this->_getProductsQty($order->getAllItems());
                            $true = array();
                            $false = array();
                            if (!empty($items)) {
                                $websiteId = $this->stockConfiguration->getDefaultScopeId();
                                foreach ($items as $productid => $qty) {
                                    $stockItem = $this->stockRegistryProvider->getStockItem($productid, $websiteId);
                                    if ($this->stockState->checkQty($productid, $qty, $stockItem->getWebsiteId())) {
                                        $true[] = $productid;
                                    } else {
                                        $false[] = $productid;
                                    }
                                }
                                if (!empty($true) && empty($false)) {
                                    $result = array('IsAvailable' => 'true');
                                } else {
                                    foreach ($false as $id) {
                                        $names[] = addslashes($id);
                                    }
                                    $productnames = implode(",", $names);
                                    $errormessage = 'Ordered products(' . $productnames . ') are not available in requested quanity';
                                    throw new Exception($errormessage);
                                }
                            } else {
                                throw new Exception('No items available in this order');
                            }
                        } else {
                            throw new Exception('not a zest money order');
                        }
                    } else {
                        throw new Exception('No order found');
                    }
                } else {
                    throw new Exception('Unauthorized');
                }
            } else {
                throw new Exception('Improper details');
            }
        } catch (Exception $e) {
            $result = array('IsAvailable' => 'false', 'message' => $e->getMessage());
        }
        return $result;
    }

    /**
     * @param  array
     * @return arrray
     */
    protected function _getProductsQty($relatedItems) 
    {
        $items = array();
        foreach ($relatedItems as $item) {
            $productId = $item->getProductId();
            if (!$productId) {
                continue;
            }
            $children = $item->getChildrenItems();
            if ($item->getParentItem()) {
                continue;
            } elseif ($children) {
                foreach ($children as $childItem) {
                    if (array_key_exists($childItem->getProductId(), $items)) {
                        $items[$childItem->getProductId()] = $items[$childItem->getProductId()] + $childItem->getQtyOrdered();
                    } else {
                        $items[$childItem->getProductId()] = $childItem->getQtyOrdered();
                    }

                }
            } else {
                if (array_key_exists($item->getProductId(), $items)) {
                    $items[$item->getProductId()] = $items[$item->getProductId()] + $item->getQtyOrdered();
                } else {
                    $items[$item->getProductId()] = $item->getQtyOrdered();
                }
            }
        }
        return $items;
    }

    /**
     * @param  object
     * @param  string
     */
    public function cancelmerchantorder($order, $status = 'Cancelled') 
    {
        if ($status == 'CancelledTimeout') {
            $zeststatus = $this->scopeConfig->getValue(
                'payment/zestemi/timeout_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
            $order->setStatus($zeststatus);
        } elseif ($status == 'Declined') {
            $zeststatus = $this->scopeConfig->getValue(
                'payment/zestemi/declined_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
            $order->setStatus($zeststatus);
            $this->senddeclineemail($order);
        } else {
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        }
        $invreduced = $this->zestresourceinventory->getInvreduced($order->getIncrementId());
        if (!empty($invreduced) && $invreduced != 0) {
            $this->revertinventory($order);
        }
        $order->cancel()->save();
    }

    /**
     * @param  object
     * @param  string
     */
    public function cancelmerchantorderb($order, $status = 'Cancelled') 
    {
        $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
        $invreduced = $this->zestresourceinventory->getInvreduced($order->getIncrementId());
        $this->senddeclineemail($order);
        if (!empty($invreduced) && $invreduced != 0) {
            $this->revertinventory($order);
        }
        $order->cancel()->save();
    }

    /**
     * @param  object
     */
    public function senddeclineemail($order) 
    {
        $this->inlineTranslation->suspend();
        $transport = [
        'order' => $order,
        'billing' => $order->getBillingAddress(),
        'payment_html' => $this->getPaymentHtml($order),
        'store' => $order->getStore(),
        'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
        'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
        ];
        try {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($this->scopeConfig->getValue('payment/zestemi/email_template', $storeScope))
                ->setTemplateOptions(
                    [
                    'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars($transport)
                ->setFrom($this->scopeConfig->getValue('contact/email/sender_email_identity', $storeScope))
                ->addTo($order->getCustomerEmail())
                ->setReplyTo($this->scopeConfig->getValue('contact/email/recipient_email', $storeScope))
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->inlineTranslation->resume();
            $this->mgrlog->info('There is an error in sending decline email');
        }
    }

    /**
     * @param  object
     */
    public function canceltrigger($payment) 
    {
        $this->mgrlog->info('entered cancel trigger');
        $this->_eventManager->dispatch('sales_order_payment_cancel', ['payment' => $payment]);
        $this->mgrlog->info('finished cancel trigger');
    }

    /**
     * @param  obejct
     * @return array
     */
    public function zestcustomer($request) 
    {
        try {
            if (!empty($request['firstname']) && !empty($request['email']) && !empty($request['key'])) {
                if (\Zend_Validate::is($request['email'], 'EmailAddress')) {
                    $customer = $this->customer;
                    $firstname = $request['firstname'];
                    $email = $request['email'];
                    $key = $request['key'];
                    $zestmerchant = $this->scopeConfig->getValue(
                        'payment/zestemi/zest_merchant', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    $hashkey = hash('sha512', $email . '|' . $zestmerchant);
                    if ($hashkey == $key) {
                        $passwordLength = 6;
                        $groupid = $this->scopeConfig->getValue(
                            'payment/zestemi/zest_customer_group', \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                        );
                        $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
                        $customer->loadByEmail($email);
                        if (!$customer->getId()) {
                            $customer->setEmail($email);
                            $customer->setFirstname($firstname);
                            if (!empty($request['lastname'])) {
                                $lastname = $request['lastname'];
                            } else {
                                $lastname = $firstname;
                            }
                            $customer->setLastname($lastname);
                            $customer->setGroupId($groupid);
                            $customer->setPassword('123456')->save();
                            $customer->sendNewAccountEmail();
                        } else {
                            $customer->setGroupId($groupid);
                        }
                        try {
                            $customer->save();
                            $customer->setConfirmation(null);
                            $customer->save();
                            $result = array('status' => 'success', 'message' => 'customer updated successfully');
                        } catch (Exception $ex) {
                            throw new Exception($ex->getMessage());
                        }
                    } else {
                        throw new Exception('Unauthorized');
                    }
                } else {
                    throw new Exception('Invalid email');
                }
            } else {
                throw new Exception('Improper details');
            }
        } catch (Exception $e) {
            $this->mgrlog->info('customer creation error');
            $this->mgrlog->info($e->getMessage());
            $result = array('status' => 'failed', 'message' => $e->getMessage());
        }
        return $result;
    }
}