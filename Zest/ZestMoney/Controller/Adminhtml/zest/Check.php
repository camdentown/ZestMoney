<?php

namespace Zest\ZestMoney\Controller\Adminhtml\Zest;

use Exception;
use Magento\Backend\App\Action;

class Check extends \Magento\Backend\App\Action
{

    /**
     * @return string
     */
    public function execute() 
    {
        $params = $this->getRequest()->getParams();
        $orderId = $params['order_id'];
        $order = $this->_objectManager->get('Magento\Sales\Model\Order')->load($orderId);
        try {
            $result = $this->_objectManager->get('Zest\ZestMoney\Model\Manager')->getorderstatus($order);
            if ($result['http_code'] == 200) {
                $message = "Zestmoney order status - " . $result['response']->OrderStatus;
            } elseif ($result['http_code'] == 404) {
                $message = "Order not found in Zestmoney";
            } else {
                throw new Exception('Error in processing this order');
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $this->messageManager->addNotice($message);
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->_redirect->getRefererUrl());
        return $resultRedirect;

    }

    /**
     * @return bool
     */
    protected function _isAllowed() 
    {
        return $this->_authorization->isAllowed('Zest_ZestMoney::actions_check');
    }
}
