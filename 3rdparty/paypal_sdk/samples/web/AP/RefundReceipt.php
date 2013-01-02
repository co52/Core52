<?php

/********************************************
RefundReceipt.php
Displays refund status after calling Refund API
Called by Refund.php
Calls  AdaptivePayments.php,and APIError.php.
********************************************/

require_once '../lib/AdaptivePayments.php';
require_once '../lib/Stub/AP/AdaptivePaymentsProxy.php';
require_once 'web_constants.php';

session_start();
		try {

			$currencyCode=$_REQUEST["currencyCode"];
	       	$payKey=$_REQUEST["payKey"];
			$email=$_REQUEST["receiveremail"];
			$amount = $_REQUEST["amount"];
			
	       /* Make the call to PayPal to get the Pay token
	        If the API call succeded, then redirect the buyer to PayPal
	        to begin to authorize payment.  If an error occured, show the
	        resulting errors
	        */
	       	$refundRequest = new RefundRequest();
	       	$refundRequest->currencyCode = $currencyCode;
	       	$refundRequest->payKey = $payKey;
			$refundRequest->requestEnvelope = new RequestEnvelope();
	        $refundRequest->requestEnvelope->errorLanguage = "en_US";
	        
	        $refundRequest->receiverList = new ReceiverList();
	        $receiver1 = new Receiver();
	        $receiver1->email = $email;
	        $receiver1->amount = $amount; 
	        $refundRequest->receiverList->receiver = $receiver1 ;
	        
	        $ap = new AdaptivePayments();
	        $response=$ap->Refund($refundRequest);
	           
	        if(strtoupper($ap->isSuccess) == 'FAILURE')
			{
				$_SESSION['FAULTMSG']=$ap->getLastError();
				$location = "APIError.php";
				header("Location: $location");
			
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
<html>
<head>
    <title>PayPal PHP SDK - AdaptivePayments Refund</title>
    <link href="sdk.css" rel="stylesheet" type="text/css" />
</head>
<body>

    <center>
     <b>Refund details!</b><br><br>
    <table width=400>
        <tr>
            <td>Refund Status:</td>
            <td><?php 
            		if(is_array($response->refundInfoList->refundInfo)) {
            			echo $response->refundInfoList->refundInfo[0]->refundStatus ;
            		}else {
            			echo $response->refundInfoList->refundInfo->refundStatus ;	
            		}
            		 
            
            	?></td>
        </tr>
        <tr>
            <td>Receiver:</td>
            <td><?php  
            		if(is_array($response->refundInfoList->refundInfo)) {
            			echo $response->refundInfoList->refundInfo[0]->receiver->email ;
            		}else {
            			echo $response->refundInfoList->refundInfo->receiver->email ;	
            		}
            	?></td>
        </tr>
        <tr>
            <td>Net Refund Amount:</td>
            <td><?php   
            		if(is_array($response->refundInfoList->refundInfo)) {
            			echo $response->refundInfoList->refundInfo[0]->refundNetAmount ;
            		}else {
            			echo $response->refundInfoList->refundInfo->refundNetAmount ;	
            		}
            	?></td>
        </tr>

    </table>
    </center>
    <a class="home" id="CallsLink" href="Calls.html">Home</a>
</body>
</html>