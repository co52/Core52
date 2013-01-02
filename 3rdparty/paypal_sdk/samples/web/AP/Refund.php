<?php
/***********************************************************
Refund.php

This page demonstrates Refund Operation/API.
Called by index.html.       

***********************************************************/
// clearing the session before starting new API Call
session_unset();    
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
 
  <title>PayPal Platform - Pay Center- Samples</title>
  <link href="sdk.css" rel="stylesheet" type="text/css">
</head>

<body>
  <center>
    <form action="RefundReceipt.php" method="post">
      <table class="api">
        <tr>
          <td colspan="2" class="header">Adaptive Payments - Refund</td>
        </tr>

       <tr>
        <td class="field">Pay Key:</td>
        <td><input type="text" size="50" maxlength="32" name="payKey"
            value="" /></td>
    </tr>

    <tr>
        <td class="field" width="52">currencyCode</td>
        <td><select name="currencyCode">
            <option value="USD" selected>USD</option>
            <option value="GBP">GBP</option>
            <option value="EUR">EUR</option>
            <option value="JPY">JPY</option>
            <option value="CAD">CAD</option>
            <option value="AUD">AUD</option>
        </select></td>
    </tr>
    <TR>
        <TD class="field" height="14" colSpan="3">
        <P align="center">Refund Details</P>
        </TD>
    </TR>
    <tr>
        <td width="52">Receivers</td>
        <td>ReceiverEmail (Required):</td>
        <td>Amount(Required):</td>

    </tr>
    <tr>
        <td width="52">
        <P align="right">1</P>
        </td>
        <td><input type="text" name="receiveremail" size="50"
            value="platfo_1255611349_biz@gmail.com"></td>
        <td><input type="text" name="amount" size="5" maxlength="7"
            value="1.00"></td>   
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