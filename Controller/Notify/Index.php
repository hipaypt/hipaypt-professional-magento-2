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
 	
    public function __construct(
		\Magento\Framework\App\Action\Context $context
    ) {
		
        parent::__construct($context);
        $this->_messageManager = $context->getMessageManager();
    }
    
    public function execute()
    {
		
		if(!isset($_POST["xml"])) {
			return;
		};
		$xml = $_POST['xml'];
		$obj = new \SimpleXMLElement(trim($xml));

		if (isset($obj->result[0])) 
		{
			
			$ispayment =  true;
			if (isset($obj->result[0]->operation))
				$operation=$obj->result[0]->operation;
			else
				$ispayment =  false;

			if (isset($obj->result[0]->status))
				$status=$obj->result[0]->status;
			else 
				$ispayment =  false;

			if (isset($obj->result[0]->date))
				$date=$obj->result[0]->date;
			else 
				$ispayment =  false;

			if (isset($obj->result[0]->time))
				$time=$obj->result[0]->time;
			else 
				$ispayment =  false;

			if (isset($obj->result[0]->transid))
				$transid=$obj->result[0]->transid;
			else 
				$ispayment =  false;

			if (isset($obj->result[0]->origAmount))
				$origAmount=$obj->result[0]->origAmount;
			else 
				$ispayment =  false;

			if (isset($obj->result[0]->origCurrency))
				$origCurrency=$obj->result[0]->origCurrency;
			else 
				$ispayment = false;

			if (isset($obj->result[0]->idForMerchant))
				$idformerchant=$obj->result[0]->idForMerchant;
			else 
				$ispayment =  false;


			if ($ispayment===true) {

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
			}	 	
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
	
	
}
	
