<?php
/**
 * Copyright © 2019 HiPay Portugal. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Success extends \Magento\Framework\App\Action\Action
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
		$this->_redirect('checkout/onepage/success');        
    }
}
