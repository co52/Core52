<?php

/********************************************
 ExcecutePaymentReceipt.php

 This file is called after the user clicks on a button during
 the Pay process to use PayPal's AdaptivePayments Pay features'. The
 user logs in to their PayPal account.
 Called by ExcecutePayment.php
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

	$executePaymentRequest = new ExecutePaymentRequest();
	$executePaymentRequest->payKey = $payKey;

	$executePaymentRequest->requestEnvelope = new RequestEnvelope();
	$executePaymentRequest->requestEnvelope->errorLanguage = "en_US";
	$ap = new AdaptivePayments();
	$response=$ap->ExecutePayment($executePaymentRequest);

	if(strtoupper($ap->isSuccess) == 'FAILURE')
	{
		$_SESSION['FAULTMSG']=$ap->getLastError();
		$location = "APIError.php";
		header("Location: $location");
			
	}
	else
	{
		if($response->paymentExecStatus == "COMPLETED")
		{
			if($response->responseEnvelope->ack == "Success")
			{
				?>
<html>
<head>

<title>PayPal Platform SDK - Excecute Payment Options</title>
<link href="sdk.css" rel="stylesheet" type="text/css">
</head>
<body alink=#0000FF vlink=#0000FF>


<center><b><u>Pay - Create, Set, Execute API Flow</u></b> <br />
<font size=2 color=black face=Verdana><b>Excecute Payment Options -
Response</b></font> <br />
<br />

<table>
	<tr>
		<td>paymentExecStatus:    </td>
		<td><?php echo $response->paymentExecStatus ; ?></td>
	</tr>
	<tr>
		<td>Ack:</td>
		<td><?php echo $response->responseEnvelope->ack ; ?></td>
	</tr>
	<tr>
		<td>CorrelationId:</td>

		<td><?php echo $response->responseEnvelope->correlationId ; ?></td>
	</tr>
	<tr>
		<td>TimeStamp:</td>
		<td><?php echo $response->responseEnvelope->timestamp ; ?></td>
	</tr>
	<tr>

		<td>Build:</td>
		<td><?php echo $response->responseEnvelope->build ; ?></td>
	</tr>
</table>
 <table>
   <tr>
      <td>*</td>
      <td><a href="GetPaymentDetails.php?payKey=<?php echo $payKey; ?>">Get Payment Details</a></td>
    </tr>
 </table>

<br />

</center>

<a class="home" href="Calls.html">Home</a>
</body>
</html>

				<?php

			}

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
