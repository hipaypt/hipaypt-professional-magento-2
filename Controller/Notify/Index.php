<?php
namespace Hipay\HipayProfessionalGateway\Controller\Notify;

use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;

class Index extends AppAction
{

	const HIPAY_PRODUCTION_CHECK_TRANSACTION	= "https://ws.hipay.com/soap/transaction-v2?wsdl";
	const HIPAY_SANDBOX_CHECK_TRANSACTION	= "https://test-ws.hipay.com/soap/transaction-v2?wsdl";

    protected $_messageManager;
    protected $_context;
    protected $_order;
    protected $_sandbox;
    protected $_credentials;
    protected $_logger;
 	
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->_messageManager = $context->getMessageManager();

        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof HttpRequest && $request->isPost()) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }
    
    
    public function execute()
    {
		
        $params = $this->getRequest()->getPostValue();

        try {

			if(!isset($params["operation"])) {
				echo "no xml";
				return;
			};
			
			$operation=$params["operation"];
			$status=$params["status"];
			$date=$params["date"];
			$time=$params["time"];
			$transid=$params["transid"];
			$origAmount=$params["origAmount"];
			$origCurrency=$params["origCurrency"];
			$idformerchant=$params["idForMerchant"];

			$this->_order = $this->_objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($idformerchant);
			if ($this->_order->getGrandTotal() <> $origAmount){
					echo "Amount does not match. " . $this->_order->getGrandTotal()  . " and " . $origAmount;
					exit;
			}

			$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
			$this->_sandbox 		= $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/hipay_professional_gateway/sandbox',$storeScope);
			$this->_credentials	= $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/hipay_professional_gateway/api_' . $origCurrency,$storeScope);
			$hipayTransaction = $this->getTransactionStatus($transid,$origCurrency,$origAmount);

			switch($operation){
				
				case "capture":
					if ($status == "ok" && $hipayTransaction->getDetailsResult->code == "0" && $hipayTransaction->getDetailsResult->amount == $origAmount && $hipayTransaction->getDetailsResult->currency == $origCurrency && strtolower($hipayTransaction->getDetailsResult->transactionStatus) == "captured"){
		
						echo "CAPTURE!";
						if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING){
							$this->_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)->setStatus("processing");
							$comment = "Captured, " . date('Y-m-d H:i:s');
							$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(true)->setEntityName('order');
							$this->_order->save();
						}	
						
					} elseif ($status == "nok" && $hipayTransaction->getDetailsResult->code == "0" && $hipayTransaction->getDetailsResult->amount == $origAmount && $hipayTransaction->getDetailsResult->currency == $origCurrency && strtolower($hipayTransaction->getDetailsResult->transactionStatus) != "captured"){

						if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING){
							$this->_order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)->setStatus("canceled");
							$comment = "Capture failed, " . date('Y-m-d H:i:s');
							$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(true)->setEntityName('order');
							$this->_order->save();	
							echo "NO CAPTURE!";
						}
					}
					break;
				case "authorization":
					if ($status == "nok"  && $hipayTransaction->getDetailsResult->code == "0" && $hipayTransaction->getDetailsResult->amount == $origAmount && $hipayTransaction->getDetailsResult->currency == $origCurrency && strtolower($hipayTransaction->getDetailsResult->transactionStatus) != "captured" ){

						if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING){
							$this->_order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)->setStatus("canceled");
							$comment = "Authorization failed, " . date('Y-m-d H:i:s');
							$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(true)->setEntityName('order');
							$this->_order->save();	
							echo "NO AUTHORIZATION!";
						}
					} else{
						echo "Ignore and wait for capture.";
					}					
					break;
				case "cancellation":
					echo "Does not apply. Manual capture not active.";
					break;
				case "refund":
					if ($status == "ok"  && $hipayTransaction->getDetailsResult->code == "0" && $hipayTransaction->getDetailsResult->amount == $origAmount && $hipayTransaction->getDetailsResult->currency == $origCurrency && strtolower($hipayTransaction->getDetailsResult->transactionStatus) != "captured"){
						$comment = "Refunded, " . date('Y-m-d H:i:s');
						$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(false)->setEntityName('order');
						$this->_order->save();
						echo "REFUND!";	
					}
					break;
				case "reject":
					if ($status == "ok"  && $hipayTransaction->getDetailsResult->code == "0" && $hipayTransaction->getDetailsResult->amount == $origAmount && $hipayTransaction->getDetailsResult->currency == $origCurrency && strtolower($hipayTransaction->getDetailsResult->transactionStatus) != "captured"){
						$comment = "Chargeback, " . date('Y-m-d H:i:s');
						$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(true)->setEntityName('order');
						$this->_order->save();
						echo "Chargeback";
					}
					break;
			}

        } catch (\Exception $e) {
			echo "ERROR";
        }
		

    }
	
	protected function getTransactionStatus($transid,$origCurrency,$origAmount){
		
		
		if ($this->_sandbox)
			$ws_url = self::HIPAY_SANDBOX_CHECK_TRANSACTION;
		else
			$ws_url = self::HIPAY_PRODUCTION_CHECK_TRANSACTION;

		$client = new \SoapClient($ws_url);
		$parameters = new \stdClass(); 
		$parameters->parameters = array('wsLogin' => $this->_credentials["merchant_api_login"],	'wsPassword' => $this->_credentials["merchant_api_password"],'transactionPublicId' => $transid	);	
		$result = $client->getDetails($parameters);
		return $result;			
		
	}	
	
	
	public function getRequest()
    {
        return $this->_request;
    }

    public function getResponse()
    {
        return $this->_response;
    }
	
}
	
