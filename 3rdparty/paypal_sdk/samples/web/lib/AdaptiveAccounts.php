<?php
/****************************************************
AdaptiveAccounts.php
This file contains client business methods to call
PayPals AdaptiveAccounts Webservice APIs.
****************************************************/
#require_once 'Config/paypal_sdk_clientproperties.php' ;
define('PAYPALSDK_BASE_DIR', dirname(__FILE__));
require_once PAYPALSDK_BASE_DIR.'/CallerServices.php';
require_once PAYPALSDK_BASE_DIR.'/Stub/AA/AdaptiveAccountsProxy.php';
require_once PAYPALSDK_BASE_DIR.'/SOAPEncoder/SOAPEncoder.php';
require_once PAYPALSDK_BASE_DIR.'/XMLEncoder/XMLEncoder.php';
require_once PAYPALSDK_BASE_DIR.'/JSONEncoder/JSONEncoder.php';
require_once PAYPALSDK_BASE_DIR.'/Exceptions/FatalException.php';

class AdaptiveAccounts extends CallerServices {

   function CreateAccount($createAccountRequest, $isRequestString = false) {
   		try {
   			if($isRequestString) {
   				return parent::callWebService($createAccountRequest, 'AdaptivePayments/CreateAccount');
   			}
   			else {
   				return $this->callAPI($createAccountRequest, 'AdaptiveAccounts/CreateAccount');
   			}
   		}
   		catch(Exception $ex) {
				  			
   			throw $ex;#new FatalException('Error occurred in CreateAccount method');
   		}
   }
   
     
   /*
    * Calls the call method of CallerServices class and returns the response.
    */
   private function callAPI($request, $URL)
   {
   $response = null;
		$isError = false;
		$reqObject = $request;
   		try {
			
   			switch(X_PAYPAL_REQUEST_DATA_FORMAT) {
   				case "JSON" :
   						$request = JSONEncoder::Encode($request);
   						$response = parent::callWebService($request, $URL);
   					break;
   				case "SOAP11" :
   						$request = SoapEncoder::Encode($request);
   						$response = parent::call($request, $URL);
   					break;
   				case "XML" :
   						$request = XMLEncoder::Encode($request);
   						$response = parent::callWebService($request, $URL);
   						
   					break;
   				
   			}
   			switch(X_PAYPAL_RESPONSE_DATA_FORMAT) {
   				case "JSON" :
   						$strObjName = get_class($reqObject);
        				$strObjName = str_replace('Request', 'Response', $strObjName);
        				$response = JSONEncoder::Decode($response,$isError, $strObjName);
   					break;
   				case "XML" :
   						$response = XMLEncoder::Decode($response, $isError);
   					break;
   				case "NV" :
   						$response = NVPEncoder::Decode($response);
   					break;
   			}
			
   			$this->result='Success';
	   		$this->isSuccess = 'Success' ;
	        if($isError)
	        {
	        	$this->isSuccess = 'Failure' ;
	        	$this->setLastError($response) ;
	        	$response = null ;
	        }
   		}
   		catch(Exception $ex) {
   			throw $ex;#new FatalException('Error occurred in callAPI method');
   		}
        return $response;
   }
                              
}
?>