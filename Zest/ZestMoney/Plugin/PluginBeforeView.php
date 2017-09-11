<?php
namespace Zest\ZestMoney\Plugin;
use Magento\Framework\App\RequestInterface;

class PluginBeforeView
{
    /**
     * @param \Magento\Sales\Model\Order
     * @param \Psr\Log\LoggerInterface
     * @param RequestInterface
     * @param \Magento\Framework\UrlInterface
     */
    public function __construct(
        \Magento\Sales\Model\Order $order,
        \Psr\Log\LoggerInterface $logger,
        RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->logger = $logger;
        $this->order = $order;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param  \Magento\Sales\Block\Adminhtml\Order\View
     * @return null
     */
    public function beforeGetOrderId(\Magento\Sales\Block\Adminhtml\Order\View $subject) 
    {
        $zestcheck = $this->urlBuilder->getUrl("zestmoney/zest/check");
        $reporturl = $this->urlBuilder->getUrl("zestmoney/zest/delivery");
        $deliveryurl = $reporturl . 'prodstatus/delivered';
        $refuseurl = $reporturl . 'prodstatus/refused';
        $requestdata = $this->request->getParams();
        $orderId = $requestdata['order_id'];
        $order = $this->order->load($orderId);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();
        if ($methodTitle == 'Zestemi') {
            $subject->addButton(
                'zest_status',
                ['label' => __('Zest Status'), 'onclick' => 'setLocation("' . $zestcheck . '")', 'class' => 'reset'],
                -1
            );
            $subject->addButton(
                'zest_deliver',
                ['label' => __('Zest Deliver'), 'onclick' => 'setLocation("' . $deliveryurl . '")', 'class' => 'reset'],
                -1
            );
            $subject->addButton(
                'zest_refuse',
                ['label' => __('Zest Refuse'), 'onclick' => 'setLocation("' . $refuseurl . '")', 'class' => 'reset'],
                -1
            );
        }
        return null;
    }
}
