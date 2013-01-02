<?php

/********************************************
CreatePayReceipt.php

This file is called after the user clicks on a button during
the Pay process to use PayPal's AdaptivePayments Pay features'. The
user logs in to their PayPal account.
Called by CreatePay.php
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
		
		           $returnURL = $url."/CreatePayReceipt.php";
		           $cancelURL = $url. "/CreatePay.php" ;
		           $currencyCode=$_REQUEST['currencyCode'];
		           $email=$_REQUEST["email"];
				   $preapprovalKey = $_REQUEST["preapprovalKey"];	
				   $actionType=$_REQUEST["actionType"];
		           $requested='';
		           $receiverEmail='';
		           $amount='';
		           $count= count($_POST['receiveremail']);
		                                 
		            $payRequest = new PayRequest();
		            $payRequest->actionType = $actionType;
					$payRequest->cancelUrl = $cancelURL ;
					$payRequest->returnUrl = $returnURL;
					$payRequest->clientDetails = new ClientDetailsType();
					$payRequest->clientDetails->applicationId ="APP-1JE4291016473214C";
		           	$payRequest->clientDetails->deviceId = DEVICE_ID;
		           	$payRequest->clientDetails->ipAddress = "127.0.0.1";
		           	$payRequest->currencyCode = $currencyCode;
		           	$payRequest->senderEmail = $email;
		           	$payRequest->requestEnvelope = new RequestEnvelope();
		           	$payRequest->requestEnvelope->errorLanguage = "en_US";
		           	if($preapprovalKey != "")
		           	{
		           		$payRequest->preapprovalKey = $preapprovalKey ;
		           	}          	
		           	$receiver1 = new receiver();
		           	$receiver1->email = $_POST['receiveremail'][0];
		           	$receiver1->amount = $_REQUEST['amount'][0];
		           	
		           	$receiver2 = new receiver();
		           	$receiver2->email = $_POST['receiveremail'][1];
		           	$receiver2->amount = $_REQUEST['amount'][1];
		           	
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
						$_SESSION['FAULTMSG']=$ap->getLastError();
						$location = "APIError.php";
						header("Location: $location");
					
					}
					else
					{
						$_SESSION['payKey'] = $response->payKey;
						if($response->paymentExecStatus == "CREATED")
						{
							


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
  <title>PayPal PHP SDK -CreatePay Details</title>
  <link href="sdk.css" rel="stylesheet" type="text/css">
</head>

<body>
  <center>
   
          <center><b><u>Pay - Create, Set, Execute API Flow</u></b><br></br></center> 
    <font size="2" color="black" face="Verdana"><b>CreatePay
    Details</b></font><br>
    <br>

    <table width="500">
    	<tr>
			<td>Transaction Status:</td>
			<td><?php echo $response->status ; ?></td>
		</tr>
		<tr>
			<td>Pay Key:</td>
			<td><?php echo $response->payKey ; ?></td>
		</tr>
      
    </table>
  

 <br/>
 <table>
    <tr>
      <td>*</td>
      <td><a href="SetPaymentOption.php?payKey=<?php echo "$payKey" ; ?>">(Optional)Set Payment Options</a></td>
    </tr>
    <tr>
      <td>*</td>	
      <td><a href="ExecutePayment.php?payKey=<?php echo "$payKey" ; ?>">Execute Payment</a></td>

    </tr>
 </table>
    
  </center><a class="home" id="CallsLink" href="Calls.html" name=
  "CallsLink">Home</a>
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