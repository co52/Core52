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
  require_once('../../web/lib/AdaptiveAccounts.php');
  require_once('../../web/lib/Stub/AA/AdaptiveAccountsProxy.php');

  RunAdaptivePaymentSamples();


  function RunAdaptivePaymentSamples() {
				
		try {
					
			echo  "Running AdaptiveAccounts Samples...\n";
					
			//Create Account
			CreateAccount();
						
		}
		catch(Exception $ex) {
			echo $ex->getMessage();
		}
		
		echo  "\n\n *****  Done. *****\n\n";
		
		//Reads enter key
		fread(STDIN, 1);

  }

	function CreateAccount() {
		
		$aa = new AdaptiveAccounts();
		
		$CARequest = new CreateAccountRequest();
       	$CARequest->accountType = 'PERSONAL';
       	       	
       	$address = new AddressType();
       	$address->city = 'Austin';
       	$address->countryCode = 'US';
       	$address->line1 = '1968 Ape Way';
       	$address->line2 = 'Apt 123';
       	$address->postalCode = '78750';
       	$address->state = 'TX' ;
       	$CARequest->address = $address;

       	$CARequest->citizenshipCountryCode = 'US';
       	$CARequest->clientDetails = new ClientDetailsType();
       	$CARequest->clientDetails->applicationId ="APP-80W284485P519543T";
        $CARequest->clientDetails->deviceId = "PayPal_PHP_SDK";
        $CARequest->clientDetails->ipAddress = "127.0.0.1";
           	
       	$CARequest->contactPhoneNumber = '512-691-4160';
       	$CARequest->currencyCode = 'USD';
       	$CARequest->dateOfBirth = '1968-01-01';
       	       	
       	$name = new NameType();
       	$name->firstName = 'Bonzop' ;
       	$name->middleName = 'Simore';
       	$name->lastName = 'Zaius';
       	$name->salutation = 'Dr.';
       	$CARequest->name = $name;
       	       	
       	$CARequest->notificationURL = 'http://stranger.paypal.com/cgi-bin/ipntest.cgi';
       	$CARequest->partnerField1 = 'p1';
       	$CARequest->partnerField2 = 'p2';
       	$CARequest->partnerField3 = 'p3';
       	$CARequest->partnerField4 = 'p4';
       	$CARequest->partnerField5 = 'p5';
       	$CARequest->preferredLanguageCode = "en_US";
       	
       	$rEnvelope = new RequestEnvelope();
		$rEnvelope->errorLanguage = "en_US";
       	$CARequest->requestEnvelope = $rEnvelope ;
       	
       	$CARequest->sandboxEmailAddress = 'Platform.sdk.seller@gmail.com';
       	$datetime = gettimeofday();
		$CARequest->emailAddress = 'testaccount' . $datetime['usec'] . '@paypal.com';
       	
       	$aa = new AdaptiveAccounts();
       	$aa->sandBoxEmailAddress = 'Platform.sdk.seller@gmail.com';
		$response=$aa->CreateAccount($CARequest);
		
  		if(strtoupper($aa->isSuccess) == 'FAILURE')
		{
			$FaultMsg = $aa->getLastError();
			echo "Transaction CreateAccount Failed: error Id: ";
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
			echo "CreateAccount Transaction Successful! \n";
		}
		
	}
	
?>
