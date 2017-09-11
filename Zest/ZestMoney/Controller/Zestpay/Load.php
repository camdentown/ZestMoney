<?php

namespace Zest\ZestMoney\Controller\Zestpay;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Load extends \Magento\Framework\App\Action\Action
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
        if (!empty($request)) {
            $this->manager = $this->_objectManager->get('Zest\ZestMoney\Model\Manager');
            $response = $this->manager->updateorder($request);
        } else {
            $response = array('status' => 'failed', 'message' => 'emptydata');
        }
        $resultJson = $this->resultJsonFactory->create();
        if ($response['status'] == 'failed') {
            $resultJson->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
        }
        return $resultJson->setData($response);
    }
}
