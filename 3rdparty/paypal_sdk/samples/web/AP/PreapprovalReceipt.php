<?php

/********************************************
PayReceipt.php

This file is called after the user clicks on a button during
the Pay process to use PayPal's AdaptivePayments Pay features'. The
user logs in to their PayPal account.

Called by SetPay.php

Calls  CallerService.php,and APIError.php.

********************************************/

require_once '../lib/AdaptivePayments.php';
require_once 'web_constants.php';

session_start();
			try {
			
		        /* The servername and serverport tells PayPal where the buyer
		           should be directed back to after authorizing payment.
		           In this case, its the local webserver that is running this script
		           Using the servername and serverport, the return URL is the first
		           portion of the URL that buyers will return to after authorizing payment                */
		
		           $serverName = $_SERVER['SERVER_NAME'];
		           $serverPort = $_SERVER['SERVER_PORT'];
		           $url=dirname('http://'.$serverName.':'.$serverPort.$_SERVER['REQUEST_URI']);
		           /* The returnURL is the location where buyers return when a
		            payment has been succesfully authorized.
		            The cancelURL is the location buyers are sent to when they hit the
		            cancel button during authorization of payment during the PayPal flow                 */
		
		           $returnURL = $url."/PreapprovalDetails.php";
		           $cancelURL = "$url/SetPreapproval.php" ;
		           $senderEmail=$_POST["senderEmail"];
				   $startingDate=$_POST["startingDate"];
				   $endingDate=$_POST["endingDate"];
				   $maxNumberOfPayments=$_POST["maxNumberOfPayments"];
		           $maxTotalAmountOfAllPayments=$_POST["maxTotalAmountOfAllPayments"];
		           $currencyCode=$_POST["currencyCode"];
		
		           
		           /* Make the call to PayPal to get the Pay token
		            If the API call succeded, then redirect the buyer to PayPal
		            to begin to authorize payment.  If an error occured, show the
		            resulting errors
		            */
		           $preapprovalRequest = new PreapprovalRequest();
		           $preapprovalRequest->cancelUrl = $cancelURL;
		           $preapprovalRequest->returnUrl = $returnURL;
		           $preapprovalRequest->clientDetails = new ClientDetailsType();
		           $preapprovalRequest->clientDetails->applicationId =APPLICATION_ID;
		           $preapprovalRequest->clientDetails->deviceId = DEVICE_ID;
		           $preapprovalRequest->clientDetails->ipAddress = "127.0.0.1";
		           $preapprovalRequest->currencyCode = $currencyCode;
		           $preapprovalRequest->startingDate = $startingDate;
		           $preapprovalRequest->endingDate = $endingDate;
		           $preapprovalRequest->maxNumberOfPayments = $maxNumberOfPayments;
		           $preapprovalRequest->maxTotalAmountOfAllPayments = $maxTotalAmountOfAllPayments;
		           $preapprovalRequest->requestEnvelope = new RequestEnvelope();
		           $preapprovalRequest->requestEnvelope->errorLanguage = "en_US";
		           $preapprovalRequest->senderEmail = $senderEmail;           
		           
		           $ap = new AdaptivePayments();
		           $response=$ap->Preapproval($preapprovalRequest);
		           
		           if(strtoupper($ap->isSuccess) == 'FAILURE')
					{
						//Redirecting to APIError.php to display errors.
						$_SESSION['FAULTMSG']=$ap->getLastError();
						$location = "APIError.php";
						header("Location: $location");
					
					}
					else
					{	
						// Redirect to paypal.com here
						$_SESSION['preapprovalKey'] = $response->preapprovalKey;
						$token = $response->preapprovalKey;
						$payPalURL = PAYPAL_REDIRECT_URL.'_ap-preapproval&preapprovalkey='.$token;
		                header("Location: ".$payPalURL);
						
					}
			}
			catch(Exception $ex) {
				
				$fault = new FaultMessage();
				$errorData = new ErrorData();
				$errorData->errorId = $ex->getFile() ;
  				$errorData->message = $ex->getMessage();
		  		$fault->error = $errorData;
				$_SESSION['FAULTMSG']=$fault;
				$location = "APIError.php";
				header("Location: $location");
			}
			

?>