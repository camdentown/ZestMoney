<?php

namespace Zest\ZestMoney\Controller\Zestpay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Reduceinventory extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;

    /**
     * @param Context
     * @param JsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;

    }

    /**
     * @return array
     */
    public function execute() 
    {
        $request = $this->getRequest()->getPost();
        if (!empty($request)) {
            $this->manager = $this->_objectManager->get('Zest\ZestMoney\Model\Manager');
            $response = $this->manager->checkinventory($request);
        } else {
            $response = array('IsAvailable' => 'false', 'message' => 'emptydata');
        }
        $resultJson = $this->resultJsonFactory->create();
        $badrequest = array('Improper details', 'Unauthorized', 'No order found', 'not a zest money order', 'emptydata');
        if ($response['IsAvailable'] == 'true' && empty($response['message'])) {
            $orderid = $request['orderno'];
            $order = $this->_objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($orderid);
            $this->manager->reduceinventory($order);
            if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW && $order->getPayment()->getMethod() == 'zestemi' && $order->getCanSendNewEmailFlag()) {
                $this->_objectManager->get('Magento\Sales\Model\Order\Email\Sender\OrderSender')->send($order);
            }
        } else {
            unset($response['IsAvailable']);
            $resultJson->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
            return $resultJson->setData($response);
        }
    }
}