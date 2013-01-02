<?php
/***********************************************************
SetPay.php

This is the main web page for the Pay Checkout sample.
The page allows the user to enter amount and currency type
and receiver email and other data needed for AdaptivePayments Pay API.
When the user clicks the Submit button, PayReceipt.php is
called.
Called by index.html.

***********************************************************/
// clearing the session before starting new API Call
require_once 'web_constants.php';
session_unset();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
  <meta name="generator" content=
  "HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">

  <title>PayPal Platform - Pay Center- Samples</title>
  <link href="sdk.css" rel="stylesheet" type="text/css">
</head>

<body>
  <center>
    <form action="PayParallelReceipt.php" method="post">
      <table class="api">
        <tr>
          <td colspan="2" class="header">Adaptive Payments -
          Pay</td>
        </tr>

        <tr>
          <td colspan="2">
            <center>
              <br>
              You must be logged into <a href=
              "<?php echo DEVELOPER_PORTAL ?>" id=
              "PayPalDeveloperCentralLink" target="_blank" name=
              "PayPalDeveloperCentralLink">Developer
              Central<br></a><br>
            </center>
          </td>
        </tr>
      </table>

      <table>
      	<tr>
          <td class="thinfield">Sender's Email:</td>

          <td><input type="text" size="50" maxlength="64" name=
          "email" value="platfo_1255077030_biz@gmail.com"></td>
        </tr>
        <tr>
          <td class="thinfield">Memo:</td>
          <td><input type="text" size="50" maxlength="32" name=
          "memo" value="parallel"></td>
        </tr>

        <tr>
		        <td class="thinfield">Fees Payer:</td>
		        <td><input type="text" size="50" maxlength="32" name=
		          "feesPayer" value="SENDER"></td>
        </tr>

        <tr>
          <td class="thinfield" width="52">currencyCode</td>

          <td><select name="currencyCode">
            <option value="USD" selected>
              USD
            </option>

            <option value="GBP">
              GBP
            </option>

            <option value="EUR">
              EUR
            </option>

            <option value="JPY">
              JPY
            </option>

            <option value="CAD">
              CAD
            </option>

            <option value="AUD">
              AUD
            </option>
          </select></td>
        </tr>

        <tr>
          <td class="thinfield" height="14" colspan="3">
            <p align="center">Pay Details</p>
          </td>
        </tr>

        <tr>
          <td width="52">Payee</td>
          <td>ReceiverEmail (Required):</td>
          <td>Amount(Required):</td>
          <td>Primay Receiver(Required):</td>
        </tr>

       <tr>
	   		<td width="52">
	   		<P align="right">1</P>
	   		</td>
	   		<td><input type="text" name="receiveremail[]" size="64"
	   			value="platfo_1255612361_per@gmail.com"></td>
	   		<td><input type="text" name="amount[]"
	   			value="1.0"></td>
	   		<td><input type="text" name="primary[]"
	   			value="false"></td>
	   	</tr>
	   	<tr>
	   		<td width="52">
	   		<P align="right">2</P>
	   		</td>
	   		<td><input type="text" name="receiveremail[]" size="64"
	   			value="platfo_1255611349_biz@gmail.com"></td>
	   		<td><input type="text" name="amount[]"
	   			value="2.0"></td>
	   		<td><input type="text" name="primary[]"
	   			value="false"></td>
	    </tr>

        <tr>
          <td class="thinfield" width="52"></td>

          <td colspan="5"><input type="submit" value="Submit"></td>
        </tr>
      </table>
    </form>
  </center><a class="home" id="CallsLink" href="Calls.html" name=
  "CallsLink">Home</a>
</body>
</html>