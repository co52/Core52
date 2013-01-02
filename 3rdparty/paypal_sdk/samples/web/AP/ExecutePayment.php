<html>
<head>
<title>PayPal Platform SDK - ExcecutePayment</title>

<link href="sdk.css" rel="stylesheet" type="text/css">
</head>
<body alink=#0000FF vlink=#0000FF>
<form method="POST" action="ExcecutePaymentReceipt.php">
<center>
  <b><u>Pay - Create, Set, Execute API Flow</u></b>
  <br/>
  <font size=2 color=black face=Verdana><b>Adaptive Payments - Execute Payment</b></font>
  <br/><br/>

  <table class="api">
	<tr>
	    <td class="thinfield">Pay Key:</td>
		<td><input type="text" size="30" maxlength="32" name="payKey"
			value="<?php echo $_GET[payKey];?>" /></td>
	</tr>

	<tr>
		<td class="thinfield" width="52"></td>
		<td colspan="5"><input type="submit" value="submit"></td>
	</tr>
  </table>	
</center>
<a class="home" href="Calls.html">Home</a>
</form>
</body>
</html>

