<?php

/******************************************************
PreapprovalDetails.php

This page is specified as the ReturnURL for the Preapproval Operation.
When returned from PayPal this page is called.
Page get the payment details for the preapprovalKey either stored
in the session or passed in the Request.

******************************************************/

require_once '../lib/AdaptivePayments.php';
require_once '../lib/Stub/AP/AdaptivePaymentsProxy.php';

session_start();
	
	if(isset($_GET['cs'])) {
		$_SESSION['preapprovalKey'] = '';
	}

	try {
			if(isset($_REQUEST["preapprovalKey"])){
			$preapprovalKey = $_REQUEST["preapprovalKey"];
			}
			if(empty($preapprovalKey))
			{
				$preapprovalKey = $_SESSION['preapprovalKey'];
			}
			
			$PDRequest = new PreapprovalDetailsRequest();
			
			$PDRequest->requestEnvelope = new RequestEnvelope();
			$PDRequest->requestEnvelope->errorLanguage = "en_US";
			$PDRequest->preapprovalKey = $preapprovalKey; 
			
			$ap = new AdaptivePayments();
			$response = $ap->PreapprovalDetails($PDRequest);
			
			
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
  <meta name="generator" content=
  "HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">

  <title>PayPal PHP SDK -Payment Details</title>
  <link href="sdk.css" rel="stylesheet" type="text/css">
</head>

<body>
  <center>
    <font size="2" color="black" face="Verdana"><b>Preapproval
    Details</b></font><br>
    <br>

    <table width="400">
		
		<tr>
        <td>Preapproval Key:</td>
        <td><?php echo $preapprovalKey ?></td>
    </tr> 	
      <tr>
        <td>CurPaymentsAmount:</td>
        <td><?php echo $response->curPaymentsAmount ?></td>
    </tr>
    <tr>
        <td>Status:</td>
        <td><?php echo $response->status ?></td>
    </tr>
    <tr>
        <td>curPeriodAttempts:</td>
        <td><?php echo $response->curPeriodAttempts ?></td>
    </tr>
    <tr>
        <td>Approved status:</td>
        <td><?php echo $response->approved ?></td>
    </tr>
    </table>
  </center><a class="home" id="CallsLink" href="Calls.html" name=
  "CallsLink">Home</a>
</body>
</html>