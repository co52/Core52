<?php

/******************************************************
ConvertCurrencyReceipt.php
Displays the Currency conversion details after calling the
ConvertCurrency API.
Called by SetConvertCurrency.php 
Calls  AdaptivePayments.php,and APIError.php.
******************************************************/

require_once '../lib/AdaptivePayments.php';
require_once '../lib/Stub/AP/AdaptivePaymentsProxy.php';

session_start();

	try {
		$fromCurrencyCode = $_REQUEST['fromcode'];
		$toCurrencyCode = $_REQUEST['tocode'];
		$amountItems = $_REQUEST['baseamount'];
		
		$CCRequest = new ConvertCurrencyRequest();
		$list = array();
		
		for($i = 0; $i<count($_POST['baseamount']);$i++)
		{	
			$ccType = new currency();
			$ccType->amount = $_REQUEST['baseamount'][$i];
			$ccType->code = $_REQUEST['fromcode'][$i];
			$list[$i] = $ccType;
			
		}
		
		$clist = array();
		for($i = 0; $i<count($_POST['tocode']);$i++)
		{
			$clist[$i] = array('currencyCode' => $_REQUEST['tocode'][$i]);
		}
		
		$CCRequest->baseAmountList = $list;
		$CCRequest->convertToCurrencyList = $clist;
		
		$CCRequest->requestEnvelope = new RequestEnvelope();
		$CCRequest->requestEnvelope->errorLanguage = "en_US";
		
		$ap = new AdaptivePayments();
		$response = $ap->ConvertCurrency($CCRequest);
		
		
		/* Display the API response back to the browser.
		   If the response from PayPal was a success, display the response parameters'
		   If the response was an error, display the errors received using APIError.php.
		*/
		if(empty($response->estimatedAmountTable))
		{
			echo "<center><br><br><b>Invalid response from server</b><br></center>";
			echo"<center><a  href='Calls.html'>Home</a></center>";
			exit();
		}
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
    <font size="2" color="black" face="Verdana"><b>Currency Conversion
    Details</b></font><br>
    <br>

    <table width="400">
	
		<?php 
			foreach($response->estimatedAmountTable->currencyConversionList as $CC) {
				
				echo "<tr><td>";

				foreach($CC->currencyList->currency as $C) {
					echo $C->code . ' ' . $C->amount . ' ' ;
				}
				
				echo "</td></tr>";
			}
			
		?>
	</table>
  </center><a class="home" id="CallsLink" href="Calls.html" name=
  "CallsLink">Home</a>
</body>
</html>