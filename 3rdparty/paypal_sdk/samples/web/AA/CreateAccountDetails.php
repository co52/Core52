<?php

/********************************************
CreateAccountReceipt.php
Calls CreateAccount API of CreateAccounts webservices.

Called by SetPay.php
Calls  AdaptiveAccounts.php,and APIError.php.
********************************************/

require_once '../lib/AdaptiveAccounts.php';
require_once '../lib/Stub/AA/AdaptiveAccountsProxy.php' ;

session_start();
$response = $_SESSION['createdAccount'];       
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