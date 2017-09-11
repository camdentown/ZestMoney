<?php

namespace Zest\ZestMoney\Controller\Zestpay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Stockavailability extends \Magento\Framework\App\Action\Action
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
        if (isset($response['message'])) {
            if (in_array($response['message'], $badrequest)) {
                unset($response['IsAvailable']);
                $resultJson->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
            }
        }
        return $resultJson->setData($response);
    }
}