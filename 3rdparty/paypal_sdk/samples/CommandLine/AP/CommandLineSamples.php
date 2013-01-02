<?php

/**
 * Copyright (C) 2009 PayPal Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


// chdir("web/lib");
// Include all the required files
require_once('../../web/lib/AdaptivePayments.php');
require_once('../../web/lib/Stub/AP/AdaptivePaymentsProxy.php');

RunAdaptivePaymentSamples();


function RunAdaptivePaymentSamples() {
	echo  "Running AdaptivePayment Samples...\n";

	try {
		//Pay - create,set,excecute
		$token = CreatePay();
		SetPaymentOption($token);
		ExcecutePay($token);
		//GetPaymentOption
		GetPaymentOption($token);

		//Pay
		$token = Pay();
			
		//Payment Details
		PaymentDetails($token);
			
		//Refund
		Refund($token);
			
		//Preapproval
		$token = Preapproval();

		//Preapproval Details
		PreapprovalDetails($token);
			
		//Cancel Preapproval
		CancelPreapproval($token);
			
		//Convert Currency
		ConvertCurrency();
			
			
	}
	catch(Exception $ex) {
		echo $ex->getMessage();
}

	echo  "\n\n *****  Done. *****\n\n";
	//Reads enter key
	fread(STDIN, 1);
}

function add_date($orgDate,$mn){
	$cd = strtotime($orgDate);
	$retDAY = date('Y-m-d', mktime(0,0,0,date('m',$cd)+$mn,date('d',$cd),date('Y',$cd)));
	return $retDAY;
}

function Pay() {

	$token = '';

	// Pay
	$payRequest = new PayRequest();
	$payRequest->actionType = "PAY";
	$returnURL = 'http://www.hawaii.com';
	$cancelURL = 'http://www.hawaii.com';
	$payRequest->cancelUrl = $cancelURL ;
	$payRequest->returnUrl = $returnURL;
	$payRequest->clientDetails = new ClientDetailsType();
	$payRequest->clientDetails->applicationId ='APP-80W284485P519543T';
	$payRequest->clientDetails->deviceId = 'PayPal_PHP_SDK';
	$payRequest->clientDetails->ipAddress = '127.0.0.1';
	$payRequest->currencyCode = 'USD';
	$payRequest->senderEmail = 'platfo_1255077030_biz@gmail.com';
	$payRequest->requestEnvelope = new RequestEnvelope();
	$payRequest->requestEnvelope->errorLanguage = 'en_US';

	$receiver1 = new receiver();
	$receiver1->email = 'platfo_1255612361_per@gmail.com';
	$receiver1->amount = '1.00';

	$receiver2 = new receiver();
	$receiver2->email = 'platfo_1255611349_biz@gmail.com';
	$receiver2->amount = '1.0';

	$payRequest->receiverList = array($receiver1,$receiver2);

	/* Make the call to PayPal to get the Pay token
	 If the API call succeded, then redirect the buyer to PayPal
	 to begin to authorize payment.  If an error occured, show the
	 resulting errors
	 */
	$ap = new AdaptivePayments();
	$response=$ap->Pay($payRequest);

	if(strtoupper($ap->isSuccess) == 'FAILURE')
	{
		$FaultMsg = $ap->getLastError();
		echo "Transaction Pay Failed: error Id: ";
		if(is_array($FaultMsg->error))
		{
			echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
		}
		else
		{
			echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
		}
			
	}
	else
	{
		$token = $response->payKey;
		echo "Transaction Successful! PayKey is $token \n";
	}

	return $token;
}

function CreatePay() {

	$token = '';

	// CreatePay
	$payRequest = new PayRequest();
	$payRequest->actionType = "CREATE";
	$returnURL = 'http://www.hawaii.com';
	$cancelURL = 'http://www.hawaii.com';
	$payRequest->cancelUrl = $cancelURL ;
	$payRequest->returnUrl = $returnURL;
	$payRequest->clientDetails = new ClientDetailsType();
	$payRequest->clientDetails->applicationId ='APP-80W284485P519543T';
	$payRequest->clientDetails->deviceId = 'PayPal_PHP_SDK';
	$payRequest->clientDetails->ipAddress = '127.0.0.1';
	$payRequest->currencyCode = 'USD';
	$payRequest->senderEmail = 'platfo_1255077030_biz@gmail.com';
	$payRequest->requestEnvelope = new RequestEnvelope();
	$payRequest->requestEnvelope->errorLanguage = 'en_US';

	$receiver1 = new receiver();
	$receiver1->email = 'platfo_1255612361_per@gmail.com';
	$receiver1->amount = '1.00';

	$receiver2 = new receiver();
	$receiver2->email = 'platfo_1255611349_biz@gmail.com';
	$receiver2->amount = '1.0';

	$payRequest->receiverList = array($receiver1,$receiver2);

	/* Make the call to PayPal to get the Pay token
	 If the API call succeded, then redirect the buyer to PayPal
	 to begin to authorize payment.  If an error occured, show the
	 resulting errors
	 */
	$ap = new AdaptivePayments();
	$response=$ap->Pay($payRequest);

	if(strtoupper($ap->isSuccess) == 'FAILURE')
	{
		$FaultMsg = $ap->getLastError();
		echo "Transaction CreatePay Failed: error Id: ";
		if(is_array($FaultMsg->error))
		{
			echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
		}
		else
		{
			echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
		}
			
	}
	else
	{
		$token = $response->payKey;
		echo "Transaction CreatePay Successful! PayKey is $token \n";
	}

	return $token;
}

function SetPaymentOption($token){
	$SetPaymentOptionsRequest = new SetPaymentOptionsRequest();
	$SetPaymentOptionsRequest->requestEnvelope = new RequestEnvelope();
	$SetPaymentOptionsRequest->requestEnvelope->errorLanguage = "en_US";
	$SetPaymentOptionsRequest->payKey = $token;


	/*$SetPaymentOptionsRequest->initiatingEntity = new InitiatingEntity();
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer = new InstitutionCustomer();
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->institutionId = '';

	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->firstName = '';
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->lastName = '';
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->displayName = '';
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->institutionCustomerId = '';
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->countryCode = '';
	 $SetPaymentOptionsRequest->initiatingEntity->institutionCustomer->email = '';
	 }
	 $SetPaymentOptionsRequest->displayOptions = new DisplayOptions();
	 $SetPaymentOptionsRequest->displayOptions->emailHeaderImageUrl = '';
	 $SetPaymentOptionsRequest->displayOptions->emailMarketingImageUrl = '';
	 */
	/* Make the call to PayPal to get the Pay token
	 If the API call succeded, then redirect the buyer to PayPal
	 to begin to authorize payment.  If an error occured, show the
	 resulting errors
	 */
	$ap = new AdaptivePayments();
	$response=$ap->SetPaymentOptions($SetPaymentOptionsRequest);

	if(strtoupper($ap->isSuccess) == 'FAILURE')
	{
		$FaultMsg = $ap->getLastError();
		echo "Transaction SetPaymentOption Failed: error Id: ";
		if(is_array($FaultMsg->error))
		{
			echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
		}
		else
		{
			echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
		}
			
	}
	else
	{
		
		echo "Transaction SetPaymentOption Successful! \n";
	}

}

function ExcecutePay($token){
	$executePaymentRequest = new ExecutePaymentRequest();
	$executePaymentRequest->payKey = $token;

	$executePaymentRequest->requestEnvelope = new RequestEnvelope();
	$executePaymentRequest->requestEnvelope->errorLanguage = "en_US";
	$ap = new AdaptivePayments();
	$response=$ap->ExecutePayment($executePaymentRequest);

	if(strtoupper($ap->isSuccess) == 'FAILURE')
	{
		$FaultMsg = $ap->getLastError();
		echo "Transaction ExcecutePayment Failed: error Id: ";
		if(is_array($FaultMsg->error))
		{
			echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
		}
		else
		{
			echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
		}
			
	}
	else
	{
		if($response->paymentExecStatus == "COMPLETED")
			
		{
			if($response->responseEnvelope->ack == "Success")
			{
				echo "Transaction ExcecutePay Successful! \n";
			}
		}
	}
}
	function GetPaymentOption($token){
		$getPaymentOptionsRequest = new GetPaymentOptionsRequest();
		$getPaymentOptionsRequest->payKey = $token;
			
		$getPaymentOptionsRequest->requestEnvelope = new RequestEnvelope();
		$getPaymentOptionsRequest->requestEnvelope->errorLanguage = "en_US";
		$ap = new AdaptivePayments();
		$response=$ap->GetPaymentOptions($getPaymentOptionsRequest);
		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "GetPaymentDetails API call Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}

		}
		else
		{
			if($response->responseEnvelope->ack == "Success")
			{
				echo "GetPaymentDetails API call was Successful! \n";
			}
		}
	}
	function PaymentDetails($token) {

		$pdRequest = new PaymentDetailsRequest();
		$pdRequest->payKey = $token;
		$rEnvelope = new RequestEnvelope();
		$rEnvelope->errorLanguage = "en_US";
		$pdRequest->requestEnvelope = $rEnvelope;

		$ap = new AdaptivePayments();
		$response=$ap->PaymentDetails($pdRequest);
		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "Transaction PaymentDetails Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}
				
		}
		else
		{
			$token = $response->payKey;
			echo "Transaction PaymentDetails Successful! \n";
		}

	}

	function Refund($token) {

		$refundRequest = new RefundRequest();
		$refundRequest->currencyCode = "USD";
		$refundRequest->payKey = $token;
		$refundRequest->requestEnvelope = new RequestEnvelope();
		$refundRequest->requestEnvelope->errorLanguage = "en_US";

		$refundRequest->receiverList = new ReceiverList();
		$receiver1 = new Receiver();
		$receiver1->email = "platfo_1255611349_biz@gmail.com" ;
		$receiver1->amount = "1.00";
		$refundRequest->receiverList->receiver = $receiver1 ;

		$ap = new AdaptivePayments();
		$response=$ap->Refund($refundRequest);
		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "Transaction Refund Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}
				
		}
		else
		{
			echo "Refund Transaction Successful! \n";
		}
	}

	function Preapproval() {

		$token = '';

		$currDate = getdate();
		$startDate = $currDate['year'].'-'.$currDate['mon'].'-'.$currDate['mday'];
		$startDate = strtotime($startDate);
		$startDate = date('Y-m-d', mktime(0,0,0,date('m',$startDate),date('d',$startDate),date('Y',$startDate)));
		$endDate = add_date($startDate, 1);

		$returnURL = 'http://www.hawaii.com';
		$cancelURL = 'http://www.hawaii.com';

		$preapprovalRequest = new PreapprovalRequest();
		$preapprovalRequest->cancelUrl = $cancelURL;
		$preapprovalRequest->returnUrl = $returnURL;
		$preapprovalRequest->clientDetails = new ClientDetailsType();
		$preapprovalRequest->clientDetails->applicationId ="APP-80W284485P519543T";
		$preapprovalRequest->clientDetails->deviceId = "PayPal_PHP_SDK";
		$preapprovalRequest->clientDetails->ipAddress = "127.0.0.1";
		$preapprovalRequest->currencyCode = "USD";
		$preapprovalRequest->startingDate = $startDate;
		$preapprovalRequest->endingDate = $endDate;
		$preapprovalRequest->maxNumberOfPayments = "10" ;
		$preapprovalRequest->maxTotalAmountOfAllPayments = "50.00";
		$preapprovalRequest->requestEnvelope = new RequestEnvelope();
		$preapprovalRequest->requestEnvelope->errorLanguage = "en_US";
		$preapprovalRequest->senderEmail = "platfo_1255076101_per@gmail.com";

		$ap = new AdaptivePayments();
		$response=$ap->Preapproval($preapprovalRequest);
		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "Transaction Preapproval Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}
				
		}
		else
		{
			$token = $response->preapprovalKey;
			echo "Preapproval Transaction Successful! Key is $token \n";
		}

		return $token;
	}

	function PreapprovalDetails($token) {

		$PDRequest = new PreapprovalDetailsRequest();
		$PDRequest->requestEnvelope = new RequestEnvelope();
		$PDRequest->requestEnvelope->errorLanguage = "en_US";
		$PDRequest->preapprovalKey = $token;

		$ap = new AdaptivePayments();
		$response = $ap->PreapprovalDetails($PDRequest);

		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "Transaction PreapprovalDetails Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}
				
		}
		else
		{
			echo "PreapprovalDetails Transaction Successful! \n";
		}

	}

	function CancelPreapproval($token) {

		$CPRequest = new CancelPreapprovalRequest();

		$CPRequest->requestEnvelope = new RequestEnvelope();
		$CPRequest->requestEnvelope->errorLanguage = "en_US";
		$CPRequest->preapprovalKey = $token;

		$ap = new AdaptivePayments();
		$response = $ap->CancelPreapproval($CPRequest);
		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "Transaction CancelPreapproval Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}
				
		}
		else
		{
			echo "CancelPreapproval Transaction Successful! \n";
		}


	}

	function ConvertCurrency() {

		$CCRequest = new ConvertCurrencyRequest();
		$list = array();
		$baseamount = array('1.00','100.00');
		$fromcode = array('GBP','EUR');
		$tocode = array('USD','CAD','JPY');
		for($i = 0; $i<count($baseamount);$i++)
		{
			$ccType = new currency();
			$ccType->amount = $baseamount[$i];
			$ccType->code = $fromcode[$i];
			$list[$i] = $ccType;
				
		}

		$clist = array();
		for($i = 0; $i<count($tocode);$i++)
		{
			$clist[$i] = array('currencyCode' => $tocode[$i]);
		}

		$CCRequest->baseAmountList = $list;
		$CCRequest->convertToCurrencyList = $clist;

		$CCRequest->requestEnvelope = new RequestEnvelope();
		$CCRequest->requestEnvelope->errorLanguage = "en_US";

		$ap = new AdaptivePayments();
		$response = $ap->ConvertCurrency($CCRequest);

		if(strtoupper($ap->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $ap->getLastError();
			echo "Transaction ConvertCurrency Failed: error Id: ";
			if(is_array($FaultMsg->error))
			{
				echo $FaultMsg->error[0]->errorId . ", error message: " . $FaultMsg->error[0]->message ;
			}
			else
			{
				echo $FaultMsg->error->errorId . ", error message: " . $FaultMsg->error->message ;
			}
				
		}
		else
		{
			echo "ConvertCurrency Transaction Successful! \n";
		}

	}

	?>
