<?php
/**
 * Copyright © 2019 HiPay Portugal. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hipay\HipayProfessionalGateway\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Redirect extends \Magento\Framework\App\Action\Action
{
	
    protected $_messageManager;
    protected $_pageFactory;
    protected $_context;
    protected $_order;

    public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
		
        parent::__construct($context);
        $this->_pageFactory	= $pageFactory;
        $this->_messageManager = $context->getMessageManager();
    }
    	

    public function execute()
    {
		$session = $this->_objectManager->get('Magento\Checkout\Model\Session');
		$this->_order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load($session->getLastOrderId());
		$payment = $this->_order->getPayment();
		$transaction_id = $payment->getLastTransId();
		$url = $payment->getAdditionalInformation('redirectUrl');
				
		$this->_order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)->setStatus("pending_payment");
		$this->_order->save();		
		
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($url);
        return $resultRedirect;
    }
}
