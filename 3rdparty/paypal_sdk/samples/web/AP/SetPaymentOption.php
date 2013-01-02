
<html>
<head>
	<title>PayPal Platform SDK - Set Payment OPtion</title>
	<link href="sdk.css" rel="stylesheet" type="text/css">
</head>
<body>
<form method="POST" action="SetPaymentOptionReceipt.php">
<center>
<b><u>Pay - Create, Set, Execute API Flow</u></b>
<br/>
<font size=2 color=black face=Verdana><b>Adaptive Payments - Set Payment Options</b></font>

<br/><br/>
<table class="api">
	<tr>
	    <td class="thinfield">Pay Key:</td>
		<td><input type="text" size="30" maxlength="32" name="payKey"
			value="<?php echo $_GET["payKey"];?>" /></td>
	</tr>
	
	<tr>
	   <td class="thinfield" height="14" colspan="4">

	      <P align="center">Financial Partner Detail:(Optional)</P>
	   </td>
	</tr>
	<tr/>
	<tr>
		<td class="thinfield" colspan="1">Country Code:</td>
		<td><input type="text" size="30" maxlength="32" name="countryCode"
			value="" /></td>
	</tr>

	<tr>
		<td class="thinfield" colspan="1">Name:</td>
		<td><input type="text" size="30" maxlength="32" name="displayName"
			value="" /></td>
	</tr>
	<tr>
		<td class="thinfield" colspan="1">Email:</td>
		<td><input type="text" size="30" maxlength="32" name="email"
			value="" /></td>
	</tr>

	
	<tr>
		<td class="thinfield" colspan="1">FirstName:</td>
		<td><input type="text" size="30" maxlength="32" name="firstName"
			value="" /></td>
	</tr>
	<tr>
		<td class="thinfield" colspan="1">LastName:</td>
		<td><input type="text" size="30" maxlength="32" name="lastName"
			value="" /></td>
	</tr>

	<tr>
		<td class="thinfield" colspan="1">CustomerId:</td>
		<td><input type="text" size="30" maxlength="32" name="institutionCustomerId"
			value="" /></td>
	</tr>
	<tr>
		<td class="thinfield" colspan="1">InstitutionId:</td>
		<td><input type="text" size="30" maxlength="32" name="institutionId"
			value="" /></td>
	</tr>

	<tr/><tr/>
	<tr>
	   <td class="thinfield" height="14" colspan="4">
	      <P align="center">Display Option:(Optional)</P>
	   </td>
	</tr>
	<tr/>
	<tr>

		<td class="thinfield" colspan="1">Email Header Image:</td>
		<td><input type="text" size="60" maxlength="32" name="emailHeaderImageUrl"
			value="http://bankone.com/images/emailHeaderImage.jpg" /></td>
	</tr>
	<tr>
		<td class="thinfield" colspan="1">Email Marketing Image:</td>
		<td><input type="text" size="60" maxlength="32" name="emailMarketingImageUrl"
			value="http://bankone.com/images/emailMarketingImage.jpg" /></td>
	</tr>
	<tr/><tr/><tr/>

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
