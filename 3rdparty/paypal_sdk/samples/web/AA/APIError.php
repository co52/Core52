<?php
/*************************************************
APIError.php

Displays error parameters.

*************************************************/
require_once '../lib/AdaptiveAccounts.php';

session_start();
$aa=$_SESSION['result'];
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title>PayPal Platform PHP API Response</title>
  <link href="sdk.css" rel="stylesheet" type="text/css">
</head>

<body alink="#0000FF" vlink="#0000FF">
  <center>
  	<table width="700">
      <tr>
        <td>
        <center><b>The PayPal API has returned
        an error!</b>
        </center>
        </td>
      </tr>
   </table>
   
    <table width="700">
      <?php  //it will print if any URL errors
    if(isset($_SESSION['curl_error_no'])) {
            $errorCode= $_SESSION['curl_error_no'] ;
            $errorMessage=$_SESSION['curl_error_msg'] ;
	
    

?>


      <tr>
        <td>Error Number:</td>

        <td><?php echo $errorCode ?></td>
      </tr>

      <tr>
        <td>Error Message:</td>

        <td><?php echo $errorMessage ?></td>
      </tr>
    </table><?php } else { if(isset($_SESSION['FAULTMSG'])) {
    	
    	$fault = $_SESSION['FAULTMSG'];
    }

/* If there is no URL Errors, Construct the HTML page with
   Response Error parameters.
   */
?>
	

    <table>
      <tr>
      	<td><?php  
		        if(is_array($fault->error))
		        {
		        	echo '<table>';
		        	foreach($fault->error as $err) {
		        		echo '<tr>';
		        		echo '<td>';
		        			echo 'Error ID: ' . $err->errorId . '</br>';
		        			echo 'Domain: ' . $err->domain . '</br>';
		        			echo 'Severity: ' . $err->severity . '</br>';
		        			echo 'Category: ' . $err->category . '</br>';
		        			echo 'Message: ' . $err->message . '</br>';
						if(empty($err->parameter)) {
		        				echo '</br>';
		        			}
		        			else {
		        				echo 'Parameter: ' . $err->parameter . '</br></br>';
		        			}
		        			
		        		echo '</td>';
		        		echo '</tr>';
		        	}
		        	echo '</table>';
		        }
		        else
		        {
		        	echo 'Error ID: ' . $fault->error->errorId . '</br>';
        			echo 'Domain: ' . $fault->error->domain . '</br>';
        			echo 'Severity: ' . $fault->error->severity . '</br>';
        			echo 'Category: ' . $fault->error->category . '</br>';
        			echo 'Message: ' . $fault->error->message . '</br>';
				if(empty($fault->error->parameter)) {
        				echo '</br>';
        			}
        			else {
        				echo 'Parameter: ' . $fault->error->parameter . '</br></br>';
        			}
		        }
        		 
        		
        	?></td>
      </tr>
      <?php } //end else ?>

    </table><br>
    <a class="home" id="CallsLink" href="Calls.html" name=
    "CallsLink"><font color="blue"><b>Home</b></font></a>
  </center>
</body>
</html>