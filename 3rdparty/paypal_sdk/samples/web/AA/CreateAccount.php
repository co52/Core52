<?php

/********************************************
CreateAccount.php

Called by index.html
Calls  CreateAccountReceipt.php,and APIError.php.
********************************************/
require_once 'web_constants.php';
?>
<html>
<head>
<title>PayPal Platform SDK - Adaptive Accounts</title>
<link href="sdk.css" rel="stylesheet" type="text/css">
</head>
<body>
<form method="POST" action="CreateAccountReceipt.php">
<center>
<table class="api">
    <tr>
        <td colspan="6" class="header">Adaptive Accounts - Create Account</td>
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
    
    <tr>
        <td class="field" >AccountType</td>
        <td><select name="accountType">
            <option value="PERSONAL" selected>PERSONAL</option>
            <option value="PREMIER">PREMIER</option>
        </select></td>
    </tr>
    <tr>
        <td class="field" >Salutation</td>
        <td><select name="name_salutation">
            <option value="Dr." selected>Dr.</option>
            <option value="Mr.">Mr.</option>
            <option value="Mrs.">Mrs.</option>
        </select></td>
    </tr>
    <tr>
        <td class="field">First Name:</td>
        <td><input type="text" size="30" maxlength="32"
            name="name_firstName" value="Bonzop" /></td>
    </tr>
    <tr>
        <td class="field">Middle Name:</td>
        <td><input type="text" size="30" maxlength="32"
            name="name_middleName" value="Simore" /></td>
    </tr>
    <tr>
        <td class="field">Last Name:</td>
        <td><input type="text" size="30" maxlength="32"
            name="name_lastName" value="Zaius" /></td>
    </tr>
    <tr>
        <td class="field">DOB:</td>
        <td><input type="text" size="30" maxlength="32"
            name="dateOfBirth" value="1968-01-01Z" /></td>
    </tr>




    <tr>
        <td class="field">Address 1:</td>
        <td><input type="text" size="25" maxlength="100" name="address_line1"
            value="1968 Ape Way" /></td>
    </tr>
    <tr>
        <td class="field">Address 2:</td>
        <td><input type="text" size="25" maxlength="100" name="address_line2" value="Apt 123"/></td>
    </tr>
    <tr>
        <td class="field">City:</td>
        <td><input type="text" size="25" maxlength="40" name="address_city"
            value="Austin" /></td>
    </tr>
    <tr>
        <td class="field">State:</td>
        <td><select name="address_state">
            <option></option>
            <option value="AK">AK</option>
            <option value="AL">AL</option>
            <option value="AR">AR</option>
            <option value="AZ">AZ</option>
            <option value="CA">CA</option>
            <option value="CO">CO</option>
            <option value="CT">CT</option>
            <option value="DC">DC</option>
            <option value="DE">DE</option>
            <option value="FL">FL</option>
            <option value="GA">GA</option>
            <option value="HI">HI</option>
            <option value="IA">IA</option>
            <option value="ID">ID</option>
            <option value="IL">IL</option>
            <option value="IN">IN</option>
            <option value="KS">KS</option>
            <option value="KY">KY</option>
            <option value="LA">LA</option>
            <option value="MA">MA</option>
            <option value="MD">MD</option>
            <option value="ME">ME</option>
            <option value="MI">MI</option>
            <option value="MN">MN</option>
            <option value="MO">MO</option>
            <option value="MS">MS</option>
            <option value="MT">MT</option>
            <option value="NC">NC</option>
            <option value="ND">ND</option>
            <option value="NE">NE</option>
            <option value="NH">NH</option>
            <option value="NJ">NJ</option>
            <option value="NM">NM</option>
            <option value="NV">NV</option>
            <option value="NY">NY</option>
            <option value="OH">OH</option>
            <option value="OK">OK</option>
            <option value="OR">OR</option>
            <option value="PA">PA</option>
            <option value="RI">RI</option>
            <option value="SC">SC</option>
            <option value="SD">SD</option>
            <option value="TN">TN</option>
            <option value="TX" selected>TX</option>
            <option value="UT">UT</option>
            <option value="VA">VA</option>
            <option value="VT">VT</option>
            <option value="WA">WA</option>
            <option value="WI">WI</option>
            <option value="WV">WV</option>
            <option value="WY">WY</option>
            <option value="AA">AA</option>
            <option value="AE">AE</option>
            <option value="AP">AP</option>
            <option value="AS">AS</option>
            <option value="FM">FM</option>
            <option value="GU">GU</option>
            <option value="MH">MH</option>
            <option value="MP">MP</option>
            <option value="PR">PR</option>
            <option value="PW">PW</option>
            <option value="VI">VI</option>
        </select></td>
    </tr>
    <tr>
        <td class="field">ZIP Code:</td>
        <td><input type="text" size="30" maxlength="10" name="address_postalCode"
            value="78750" />(5 or 9 digits)</td>
    </tr>
   <tr>
        <td class="field">Country:</td>
        <td><input type="text" size="30" maxlength="10" name="address_countryCode"
            value="US" /></td>
    </tr>
     <tr>
        <td class="field">CitizenshipCountryCode:</td>
        <td><input type="text" size="30" maxlength="10" name="citizenshipCountryCode"
            value="US" /></td>
    </tr>
  <tr>
        <td class="field">Contact Phone Number:</td>
        <td><input type="text" size="30" maxlength="10" name="contactPhoneNumber"
            value="5126914160" /></td>
    </tr>
    <tr>
        <td class="field">Notification URL:</td>
        <td><input type="text" size="30" maxlength="10" name="notificationURL"
            value="http://stranger.paypal.com/cgi-bin/ipntest.cgi" /></td>
    </tr>
    <tr>
        <td class="field">partnerField1:</td>
        <td><input type="text" size="30" maxlength="10" name="partnerField1"
            value="p1" /></td>
    </tr>
    <tr>
        <td class="field">partnerField2:</td>
        <td><input type="text" size="30" maxlength="10" name="partnerField2"
            value="p2" /></td>
    </tr>
    <tr>
        <td class="field">partnerField3:</td>
        <td><input type="text" size="30" maxlength="10" name="partnerField3"
            value="p3" /></td>
    </tr>
    <tr>
        <td class="field">partnerField4:</td>
        <td><input type="text" size="30" maxlength="10" name="partnerField4"
            value="p4" /></td>
    </tr>

    <tr>
        <td class="field">partnerField1:</td>
        <td><input type="text" size="30" maxlength="10" name="partnerField5"
            value="p5" /></td>
    </tr>

    <tr>
        <td class="field" >currencyCode</td>
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
        <td class="field">Sandbox Developer Central Email(Applicable only for Sandbox):</td>
        <td><input type="text" size="30" maxlength="50" name="sandboxEmailAddress"
            value="Platform.sdk.seller@gmail.com" /></td>
    </tr>
    <tr>
        <td class="field">Email:</td>
        <td><input type="text" size="30" maxlength="50" name="emailAddress"
            value="platfo_1255076101_per@gmail.com" /></td>
    </tr>
    <tr>
        <td class="thinfield" width="52"></td>
        <td colspan="5"><input type="submit" value="Submit"></td>
    </tr>
</table>
</center>
<a class="home" href="Calls.html">Home</a></form>
</body>
</html>