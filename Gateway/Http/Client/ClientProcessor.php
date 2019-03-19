<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayProfessionalGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class ClientProcessor implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

	const HIPAY_PRODUCTION_ENVIRONMENT = "https://ws.hipay.com/soap/payment-v2/generate?wsdl";
	const HIPAY_SANDBOX_ENVIRONMENT = "https://test-ws.hipay.com/soap/payment-v2/generate?wsdl";
	const HIPAY_PRODUCTION_CHECK_TRANSACTION	= "https://ws.hipay.com/soap/transaction-v2?wsdl";
	const HIPAY_SANDBOX_CHECK_TRANSACTION	= "https://test-ws.hipay.com/soap/transaction-v2?wsdl";


    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /**
     * @var Logger
     */
    private $logger;
	private $soapClientFactory;

    /**
     * @param Logger $logger
     */
    public function __construct( Logger $logger, \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\UrlInterface $urlBuilder ) {
        $this->logger 				= $logger;
        $this->soapClientFactory 	= $soapClientFactory;
        $this->storeManager 		= $storeManager;
        $this->urlBuilder			= $urlBuilder;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {

		$obj = $transferObject->getBody();
		
		$isSandbox 	= $obj["SANDBOX"];
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$store = $objectManager->get('Magento\Framework\Locale\Resolver'); 

		$order_params = array();
		$order_params['currency'] 			= $obj["CURRENCY"];
		$order_params['wsLogin'] 			= $obj["MERCHANT_CREDENTIALS"]["merchant_api_login"];
		$order_params['wsPassword'] 		= $obj["MERCHANT_CREDENTIALS"]["merchant_api_password"];
		$order_params['websiteId'] 			= $obj["MERCHANT_CREDENTIALS"]["merchant_api_website"];
		$order_params['categoryId'] 		= $obj["MERCHANT_CREDENTIALS"]["merchant_api_category"];
		$order_params['locale']				= $store->getLocale();
		$order_params['emailCallback'] 		= $obj["TECH_EMAIL"];
		$order_params['urlAccept'] 			= $this->urlBuilder->getUrl('hipay_professional_gateway/payment/success', ['_secure' => true]);
		$order_params['urlDecline'] 		= $this->urlBuilder->getUrl('hipay_professional_gateway/payment/cancel', ['_secure' => true]);
		$order_params['urlCancel'] 			= $this->urlBuilder->getUrl('hipay_professional_gateway/payment/cancel', ['_secure' => true]);
		$order_params['urlCallback'] 		= $this->urlBuilder->getUrl('hipay_professional_gateway/notify/index', ['_secure' => true]);			
		$order_params['description']		= $obj["INVOICE"];		//$this->storeManager->getStore()->getName();
		$order_params['urlLogo']			= $obj["WEBSITE_LOGO"];
		$order_params['customerEmail']		= $obj["EMAIL"];
		$order_params['merchantReference']	= $obj["INVOICE"];
		$order_params['merchantComment']	= $obj["INVOICE"];
		$order_params['amount']				= number_format($obj["AMOUNT"],2,".","");
		$order_params['rating']				= $obj["WEBSITE_RATING"];
		
		if (!filter_var($obj["WEBSITE_LOGO"], FILTER_VALIDATE_URL)) {
			$order_params['urlLogo'] = "";
		}			
		$result = $this->_generatePaymentUrl($isSandbox,$order_params);

		if ($result->generateResult->code != "0") {		
			if ($obj["DEBUG"])
				$this->logger->debug(
				[
					'result'	 	=> $result,
					'order_params' 	=> $order_params
				]
				);		
			throw new \Exception($result->generateResult->description);
		}

		$platform = $this->getPlatform($isSandbox);
        $response = [
                'RESULT_CODE' 	=> $result,
                'REDIRECT_URL' 	=> $result->generateResult->redirectUrl,
                'ACCOUNT_TYPE' 	=> $platform,
                'TRANSACTION_ID'=> $this->generateTxnId($result->generateResult->redirectUrl)
                
            ];

		if ($obj["DEBUG"])
			$this->logger->debug(
            [
				'order_params' 	=> $order_params,
                'request' 		=> $transferObject->getBody(),
                'response' 		=> $response
            ]
			);

        return $response;
    }

    /**
     * Generates payment url
     *
     * @return array
     */
	private function _generatePaymentUrl($isSandbox,$order_params) {
 
		if ($isSandbox)
			$this->ws_url = self::HIPAY_SANDBOX_ENVIRONMENT;
		else
			$this->ws_url = self::HIPAY_PRODUCTION_ENVIRONMENT;

		try {

			$client = $this->soapClientFactory->create($this->ws_url);
			$order_params["customerIpAddress"]	= $_SERVER['REMOTE_ADDR'];
			$order_params["executionDate"]		= date('Y-m-d H:i:s');
			$order_params["manualCapture"]		= 0;
			$parameters = new \stdClass(); 
			$parameters->parameters = $order_params;
			$result = $client->generate($parameters);
			return $result;
		        
		} catch (Exception $e) {
			return $e->getMessage();
		}

	}
	     
     
    /**
     * @return string
     */
    protected function generateTxnId($source)
    {
        return md5($source);
    }

    /**
     * @return string
     */
    protected function getPlatform($isSandbox)
    {
        if (!$isSandbox)
			return "PRODUCTION";
		else
			return "SANDBOX";
    }

}
