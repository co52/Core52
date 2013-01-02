<?php

/********************************************
GetPaymentOptionReceipt.php

 This file is called after the user clicks on a button during
 the Pay process to use PayPal's AdaptivePayments Pay features'. The
 user logs in to their PayPal account.
 Called by GetPaymentOption.php
 Calls  CallerService.php,and APIError.php.
 ********************************************/

require_once '../lib/AdaptivePayments.php';
require_once 'web_constants.php';
require_once '../lib/Stub/AP/AdaptivePaymentsProxy.php';
session_start();

try {

	 
	$serverName = $_SERVER['SERVER_NAME'];
	$serverPort = $_SERVER['SERVER_PORT'];
	$url=dirname('http://'.$serverName.':'.$serverPort.$_SERVER['REQUEST_URI']);


	/* The returnURL is the location where buyers return when a
	 payment has been succesfully authorized.
	 The cancelURL is the location buyers are sent to when they hit the
	 cancel button during authorization of payment during the PayPal flow                 */

	 
	$payKey = $_REQUEST["payKey"];
	 
	$getPaymentOptionsRequest = new GetPaymentOptionsRequest();
	$getPaymentOptionsRequest->payKey = $payKey;
	 
	$getPaymentOptionsRequest->requestEnvelope = new RequestEnvelope();
	$getPaymentOptionsRequest->requestEnvelope->errorLanguage = "en_US";
	$ap = new AdaptivePayments();
	$response=$ap->GetPaymentOptions($getPaymentOptionsRequest);
	 
	if(strtoupper($ap->isSuccess) == 'FAILURE')
	{
		$_SESSION['FAULTMSG']=$ap->getLastError();
		$location = "APIError.php";
		header("Location: $location");
			
	}
	else
	{
		if($response->responseEnvelope->ack == "Success")
		{
			?>

<html>
<head>
<title>PayPal Platform SDK - Get Payment Option Receipt</title>

<link href="sdk.css" rel="stylesheet" type="text/css" />
</head>
<body>


<center><font size=2 color=black face=Verdana><b>AP - Get Payment Option
Receipt</b></font> <br>
<br>
<br>
<table width=500 class="api">

	<tr>
		<td>Email Header Image URL:</td>
		<td><?php echo $response->displayOptions->emailHeaderImageUrl ; ?></td>

	</tr>
	<tr>
		<td>Email Marketing Image URL:</td>
		<td><?php echo $response->displayOptions->emailMarketingImageUrl ; ?></td>
	</tr>
	<tr>
		<td>InstitutionId:</td>
		<td><?php echo $response->initiatingEntitity->institutionCustomer ; ?></td>

	</tr>
	<tr>
		<td>Institution CustomerId:</td>
		<td><?php echo $response->initiatingEntitity ; ?></td>
	</tr>
	<tr>
		<td>Email:</td>
		<td><?php echo $response->initiatingEntitity ; ?></td>

	</tr>

	<tr>
		<td>Country Code:</td>
		<td><?php echo $response->initiatingEntitity ; ?></td>
	</tr>
	<tr>
		<td>Display Name:</td>
		<td><?php echo $response->initiatingEntitity ; ?></td>

	</tr>
	<tr>
		<td>First Name:</td>
		<td><?php echo $response->initiatingEntitity ; ?></td>
	</tr>
	<tr>
		<td>Last Name:</td>

		<td><?php echo $response->initiatingEntitity ; ?></td>
	</tr>
</table>




</center>
<a class="home" href="Calls.html">Home</a>
</body>
</html>

			<?php
			

		}

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
