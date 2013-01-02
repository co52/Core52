<?php

/******************************************************
PaymentDetails.php
This page is specified as the ReturnURL for the Pay Operation.
When returned from PayPal this page is called.
Page get the payment details for the payKey either stored
in the session or passed in the Request.
******************************************************/

require_once '../lib/AdaptivePayments.php';
require_once '../lib/Stub/AP/AdaptivePaymentsProxy.php';

session_start();
	if(isset($_GET['cs'])) {
		$_SESSION['payKey'] = '';
	}
	try {
		if(isset($_REQUEST["payKey"])){
			$payKey = $_REQUEST["payKey"];}
			if(empty($payKey))
			{
				$payKey = $_SESSION['payKey'];
			}
			
			$pdRequest = new PaymentDetailsRequest();
			$pdRequest->payKey = $payKey;
			$rEnvelope = new RequestEnvelope();
			$rEnvelope->errorLanguage = "en_US";
			$pdRequest->requestEnvelope = $rEnvelope;
			
			$ap = new AdaptivePayments();
			$response=$ap->PaymentDetails($pdRequest);
			
			/* Display the API response back to the browser.
			   If the response from PayPal was a success, display the response parameters'
			   If the response was an error, display the errors received using APIError.php.
			 */
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

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <title>PayPal PHP SDK -Payment Details</title>
  <link href="sdk.css" rel="stylesheet" type="text/css">
</head>

<body>
  <center>
    <font size="2" color="black" face="Verdana"><b>Payment
    Details</b></font><br>
    <br>

    <table width="500">
    <tr>
			<td>Transaction ID:</td>
			<td><?php echo $response->paymentInfoList->paymentInfo[0]->transactionId ; ?></td>
		</tr>	
    	<tr>
			<td>Transaction Status:</td>
			<td><?php echo $response->status ; ?></td>
		</tr>	
		<tr>
			<td>Pay Key:</td>
			<td><?php echo $response->payKey ; ?></td>
		</tr>
      	<tr>
			<td>Sender Email:</td>
			<td><?php echo $response->senderEmail ; ?></td>
		</tr>
		<tr>
			<td>Action Type:</td>
			<td><?php echo $response->actionType ; ?></td>
		</tr>
		<tr>
			<td>Fees Payer:</td>
			<td><?php echo $response->feesPayer ; ?></td>
		</tr>
		<tr>
			<td>Currency Code:</td>
			<td><?php echo $response->currencyCode ; ?></td>
		</tr>
		<tr>
			<td>Preapproval Key:</td>
			<td><?php 
					if(isset($response->preapprovalKey))
					{
						echo $response->preapprovalKey;
					}
					else
					{
						echo "Not Applicable" ;
					
					}
					
					 
				?>
			</td>
		</tr>
    </table>
  </center><a class="home" id="CallsLink" href="Calls.html" name=
  "CallsLink">Home</a>
</body>
</html>