<?php

/********************************************
CreateAccountReceipt.php
Calls CreateAccount API of CreateAccounts webservices.

Called by SetPay.php
Calls  AdaptiveAccounts.php,and APIError.php.
********************************************/

require_once '../lib/AdaptiveAccounts.php';
require_once '../lib/Stub/AA/AdaptiveAccountsProxy.php' ;
require_once 'web_constants.php';
session_start();

		try {
		
		       $currencyCode=$_REQUEST["currencyCode"];
		       $accountType=$_REQUEST["accountType"];
		       $namefirstName=$_REQUEST["name_firstName"];
		       $namemiddleName=$_REQUEST["name_middleName"];
		       $namelastName=$_REQUEST["name_lastName"];
		       $dateOfBirth=$_REQUEST["dateOfBirth"];
		       $addressline1=$_REQUEST["address_line1"];
		       $addressline2=$_REQUEST["address_line2"];
		       $addresscity=$_REQUEST["address_city"];
		       $addressstate=$_REQUEST["address_state"];
		       $addresspostalCode=$_REQUEST["address_postalCode"];
		       $name_salutation=$_REQUEST["name_salutation"];
		       $addresscountryCode=$_REQUEST["address_countryCode"];
		       $contactPhoneNumber=$_REQUEST["contactPhoneNumber"];
		       $citizenshipCountryCode=$_REQUEST["citizenshipCountryCode"];
		       $notificationURL=$_REQUEST["notificationURL"];
		       $partnerField1=$_REQUEST["partnerField1"];
		       $partnerField2=$_REQUEST["partnerField2"];
		       $partnerField3=$_REQUEST["partnerField3"];
		       $partnerField4=$_REQUEST["partnerField4"];
		       $partnerField5=$_REQUEST["partnerField5"];
		       $sandboxEmail = $_REQUEST["sandboxEmailAddress"];
		       $email = $_REQUEST["emailAddress"];
		       
		       /* Make the call to PayPal to create Account on behalf of the caller
		        If an error occured, show the resulting errors
		        */
		       	$CARequest = new CreateAccountRequest();
		       	$CARequest->accountType = $accountType;
		       	       	
		       	$address = new AddressType();
		       	$address->city = $addresscity;
		       	$address->countryCode = $addresscountryCode;
		       	$address->line1 = $addressline1;
		       	$address->line2 = $addressline2;
		       	$address->postalCode = $addresspostalCode;
		       	$address->state = $addressstate ;
		       	$CARequest->address = $address;
		
		       	$CARequest->citizenshipCountryCode = $citizenshipCountryCode;
		       	$CARequest->clientDetails = new ClientDetailsType();
		       	$CARequest->clientDetails->applicationId ="APP-80W284485P519543T";
		        $CARequest->clientDetails->deviceId = DEVICE_ID;
		        $CARequest->clientDetails->ipAddress = "127.0.0.1";
		           	
		       	$CARequest->contactPhoneNumber = $contactPhoneNumber;
		       	$CARequest->currencyCode = $currencyCode;
		       	$CARequest->dateOfBirth = $dateOfBirth;
		       	       	
		       	$name = new NameType();
		       	$name->firstName = $namefirstName;
		       	$name->middleName = $namemiddleName;
		       	$name->lastName = $namelastName;
		       	$name->salutation = $name_salutation;
		       	$CARequest->name = $name;
		       	       	
		       	$CARequest->notificationURL = $notificationURL;
		       	$CARequest->partnerField1 = $partnerField1;
		       	$CARequest->partnerField2 = $partnerField2;
		       	$CARequest->partnerField3 = $partnerField3;
		       	$CARequest->partnerField4 = $partnerField4;
		       	$CARequest->partnerField5 = $partnerField5;
		       	$CARequest->preferredLanguageCode = "en_US";
		       	
		       	$rEnvelope = new RequestEnvelope();
				$rEnvelope->errorLanguage = "en_US";
		       	$CARequest->requestEnvelope = $rEnvelope ;
		       	
		       	$CARequest->createAccountWebOptions = new CreateAccountWebOptionsType();
		       	$serverName = $_SERVER['SERVER_NAME'];
		        $serverPort = $_SERVER['SERVER_PORT'];
		        $url=dirname('http://'.$serverName.':'.$serverPort.$_SERVER['REQUEST_URI']);
		        $returnURL = $url."/CreateAccountDetails.php";
		
		       	$CARequest->createAccountWebOptions->returnUrl = $returnURL;
				$CARequest->registrationType = "WEB";
		       	
				$CARequest->sandboxEmailAddress = $sandboxEmail;
		       	$CARequest->emailAddress = $email;
		       	
		       	$aa = new AdaptiveAccounts();
		       	$aa->sandBoxEmailAddress = $sandboxEmail;
				$response=$aa->CreateAccount($CARequest);
				
				if(strtoupper($aa->isSuccess) == 'FAILURE')
				{
					$_SESSION['FAULTMSG']=$aa->getLastError();
					$location = "APIError.php";
					header("Location: $location");
				
				}
				else {
					
										
					$location = $response->redirectURL;
					if(!empty($location)) {
						$_SESSION['createdAccount'] = $response;
						header("Location: $location");	
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
<html>
<body>
<center><font size=2 color=black face=Verdana><b>Account Creation Confirmation</b></font> <br>
<br>
<b>Account Created!</b><br>
<br>
<table width=400>
    <tr>
        <td>CorrelationId:</td>
        <td><?php echo $response->responseEnvelope->correlationId ?></td>
    </tr>
    <tr>
        <td>CreatedAccountKey:</td>
        <td><?php echo $response->createAccountKey ?></td>
    </tr>
    <tr>
        <td>Status:</td>
        <td><?php echo $response->execStatus ?></td>
    </tr>
</table>

</center>
<a id="CallsLink" href="Calls.html">Home</a>
</body>
</html>