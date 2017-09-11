<?php

namespace Zest\ZestMoney\Controller\Adminhtml\Zest;

use Magento\Backend\App\Action;

class Delivery extends \Magento\Backend\App\Action
{

    /**
     * @return array
     */
    public function execute() 
    {
        $params = $this->getRequest()->getParams();
        $orderId = $params['order_id'];
        $order = $this->_objectManager->get('Magento\Sales\Model\Order')->load($orderId);

        $orderincid = $order->getIncrementId();
        $prodstatus = $params['prodstatus'];
        if ($prodstatus == 'delivered') {
            $status = 'Delivered';
        } else {
            $status = 'Refused';
        }
        $result = $this->_objectManager->get('Zest\ZestMoney\Model\Manager')->sendDeliverNotify($orderincid, $status);
        $code = $result['http_code'];
        switch ($code) {
        case "200":
            $message = 'Delivery report processed successfully';
            break;
        case "400":
            $message = 'Error in request parameter';
            break;
        case "404":
            $message = 'Loan for this order not found';
            break;
        case "500":
            $message = 'Internal server error';
            break;
        default:
            $message = 'Error in processing the request';
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
        return $this->_authorization->isAllowed('Zest_ZestMoney::actions_delivery');
    }
}
