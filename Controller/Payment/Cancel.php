<?php
/**
 * Copyright © 2019 HiPay Portugal. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Cancel extends \Magento\Framework\App\Action\Action
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
    
		if ($this->_order->getId() && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED) {
			$this->_order->registerCancellation('')->save();		
		}
    	if ($session->restoreQuote()) {
			$this->_redirect('checkout', ['_fragment' => 'payment']);        
		} else {
			$this->_redirect('homepage');			
		}

    }
}
