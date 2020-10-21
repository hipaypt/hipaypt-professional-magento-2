<?php
/**
 * Copyright © 2020 HiPay Portugal. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Hipay\HipayProfessionalGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\Page;

class Redirect extends Action
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
	$iframe = $payment->getAdditionalInformation('iframe');
			
	$this->_order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)->setStatus("pending")->setCanSendNewEmailFlag(true);
	$this->_order->save();
			
	if ($iframe){	
		$page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
		$block = $page->getLayout()->getBlock('hipay_professional_gateway_redirect');
		$block->setData('url', $url);
		$page->getConfig()->getTitle()->set(__('Credit card Payment'));        
		$page->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
	      
		return $page;
        } else {		
        	$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        	$resultRedirect->setUrl($url);
        	return $resultRedirect;
        }
    }
}
