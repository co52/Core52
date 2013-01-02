
<!--
SetConvertCurrency.php

This is the main page for ConvertCurrency sample.
This page displays a text box where the user enters currency conversion details
and a Submit button. Upon clicking submit button
ConvertCurrencyReceipt.php is called.  Called by index.html.


-->
<html>
<head>
    <title>PayPal Platform SDK - AdaptivePayments API</title>
    <link href="sdk.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <center>
   <form method="POST" action="ConvertCurrencyReceipt.php">
	
	<table class="api">
		<tr>
			<td colspan="6" class="header">Adaptive Payments - ConvertCurrency</td>
		</tr>	
		<tr>
			<td width="52">ConversionDetails</td>
			<td>Amount(Required):</td>
			<td>FromCurrencyCode(Required):</td>		
		</tr>
		<tr>
			<td width="52">
			<P align="right">1</P>
			</td>
			<td><input type="text" name="baseamount[]" 
				value="1.00"></td>
			<td><input type="text" name="fromcode[]"  
				value="GBP"></td>
				
		</tr>		
		<tr>
			<td width="52">
			<P align="right">2</P>
			</td>				
			<td><input type="text" name="baseamount[]" 
				value="100.00"></td>
			<td><input type="text" name="fromcode[]"  
				value="EUR"></td>				
		    </tr>
		<tr>
			<td width="52">convertToCurrencyList</td>		
			<td>ToCurrencyCode(Required):</td>		
		</tr>
		<tr>	
		<td width="52">
			<P align="right">1</P>
			</td>	
			<td><input type="text" name="tocode[]"  
				value="USD"></td>			
		</tr>
		<tr>	
		   <td width="52">
			<P align="right">2</P>
			</td>	
			<td><input type="text" name="tocode[]"  
				value="CAD"></td>			
		</tr>		
		<tr>	
		   <td width="52">
			<P align="right">3</P>
			</td>	
			<td><input type="text" name="tocode[]"  
				value="JPY"></td>			
		</tr>		
		
		
		<tr>
			<td class="thinfield" width="52"></td>
			<td colspan="5"><input type="submit" value="Submit"></td>
		</tr>
	</table>
	
	<a class="home" href="Calls.html">Home</a></form>
   </center>
</body>
</html>