

<!--
GetPaymentDetails.html

This is the main page for GetPaymentDetails sample.
This page displays a text box where the user enters a
PayKey and a Submit button. Upon clicking submit button
PaymentDetails.php is called.  Called by index.html.

Calls PaymentDetails.php.

-->

<html>
<head>
    <title>PayPal Platform SDK - AdaptivePayments API</title>
    <link href="sdk.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <center>
    <span id=apiheader>GetPaymentDetails</span>
     <br><br>
    <form action="PaymentDetails.php?cs=s" method="post">
        <table class="api">
            <tr>
                <td class="field">
                    PayKey:
                </td>
                <td>
                    <input type="text" name="payKey" value="<?php echo $_GET['payKey'];?>" />
                    (Required)</td>
            </tr>
            <tr>
                <td colspan="2">
                    <center>
                    <input type="Submit" value="Submit" /></center>
                </td>
            </tr>
        </table>
    </form>
    </center>
    <br />
    <a class="home" id="CallsLink" href="Calls.html">Home</a>
</body>
</html>
