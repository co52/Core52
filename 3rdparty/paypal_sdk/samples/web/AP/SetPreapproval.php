<?php
/***********************************************************
SetPreapproval.php

Begining of the Preapproval web flow

Called by index.html.

***********************************************************/
// clearing the session before starting new API Call
require_once 'web_constants.php';
session_unset();

$currDate = getdate();
$startDate = $currDate['year'].'-'.$currDate['mon'].'-'.$currDate['mday'];
$startDate = strtotime($startDate);
$startDate = date('Y-m-d', mktime(0,0,0,date('m',$startDate),date('d',$startDate),date('Y',$startDate)));
$endDate = add_date($startDate, 1);

function add_date($orgDate,$yr){
	  $cd = strtotime($orgDate);
	  $retDAY = date('Y-m-d', mktime(0,0,0,date('m',$cd),date('d',$cd),date('Y',$cd)+$yr));
	  return $retDAY;
	}

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
    <form action="PreapprovalReceipt.php" method="post">
      <table class="api">
        <tr>
          <td colspan="2" class="header">Adaptive Payments -
          Preapproval</td>
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
        <td class="field">Sender's Email:</td>
        <td><input type="text" size="50" maxlength="64" name="senderEmail"
            value="platfo_1255076101_per@gmail.com" /></td>
    </tr>
   <tr>
        <td class="field">Starting date:</td>
        <td><input type="text" size="50" maxlength="32" name="startingDate"
            value="<?php echo $startDate; ?>" /></td>
    </tr>
    <tr>
        <td class="field">Ending date:</td>
        <td><input type="text" size="50" maxlength="32" name="endingDate"
            value="<?php echo $endDate ;?>" /></td>
    </tr>
    <tr>
        <td class="field">Maximum Number of Payments:</td>
        <td><input type="text" size="50" maxlength="32" name="maxNumberOfPayments"
            value="10" /></td>
    </tr>
    <tr>
        <td class="field">Maximum Total Amount:</td>
        <td><input type="text" size="50" maxlength="32" name="maxTotalAmountOfAllPayments"
            value="50.00" /></td>
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