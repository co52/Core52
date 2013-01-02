<?php

function send_raw_email($to, $from, $subject, $body, $attachments = array()) {

	//create a boundary string. It must be unique 
	//so we use the MD5 algorithm to generate a random hash 
	$random_hash = md5(date('r', time())); 
	
	//define the headers we want passed. Note that they are separated with \r\n 
	$headers = "From: $from\r\nReply-To: $from\r\nSubject: $subject"; 
	
	//recipients
	#$headers .= (is_array($to))? "\r\nTo: ".implode(', ', $to) : $headers = "\r\nTo: $to";
	$to = (is_array($to))? implode(', ', $to) : $to;
	
	//add boundary string and mime type specification 
	$headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\""; 
	
	//read the atachment file contents into a string,
	//encode it with MIME base64,
	//and split it into smaller chunks
	foreach($attachments as $file => &$a) {
		$a = chunk_split(base64_encode($a));
	} 
	
	//define the body of the message. 
	ob_start(); //Turn on output buffering
?> 
--PHP-mixed-<?php echo $random_hash; ?>  
Content-Type: multipart/alternative; boundary="PHP-alt-<?php echo $random_hash; ?>" 

<? if(isset($body['text'])): ?>
--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/plain; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

<?=$body['text'];?>
<? endif; ?><? if(isset($body['html'])): ?>
--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/html; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

<?=$body['html'];?> 
<? endif; ?>
--PHP-alt-<?php echo $random_hash; ?>-- 

<? foreach($attachments as $file => $attachment): ?><?php $type = (stripos($file, '.htm') !== FALSE)? 'html' : 'plain'; ?>
--PHP-mixed-<?php echo $random_hash; ?>  
Content-Type: text/<?=$type;?>; name="<?=$file;?>"  
Content-Transfer-Encoding: base64  
Content-Disposition: attachment  

<?php echo $attachment; ?>
<? endforeach; ?>
--PHP-mixed-<?php echo $random_hash; ?>-- 

<?php
	
	//copy current buffer contents into $message variable and delete current output buffer 
	$message = ob_get_clean();
	
	//send message
	return mail($to, $subject, $message, $headers);	

}


function send_bulk_email($to, $from, $subject, $body, $attachments = array()) {

	//create a boundary string. It must be unique 
	//so we use the MD5 algorithm to generate a random hash 
	$random_hash = md5(date('r', time())); 
	
	//define the headers we want passed. Note that they are separated with \r\n 
	$headers = "From: $from\r\nReply-To: $from\r\nSubject: $subject"; 
	
	//recipients
	#$headers .= (is_array($to))? "\r\nTo: ".implode(', ', $to) : $headers = "\r\nTo: $to";
	if(is_array($to) && count($to) > 1) {
		$to = '';
		$headers .= "\r\nBcc: ".implode(', ', $to);
	}
	
	//add boundary string and mime type specification 
	$headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\""; 
	
	//read the atachment file contents into a string,
	//encode it with MIME base64,
	//and split it into smaller chunks
	foreach($attachments as $file => &$a) {
		$a = chunk_split(base64_encode($a));
	} 
	
	//define the body of the message. 
	ob_start(); //Turn on output buffering
?> 
--PHP-mixed-<?php echo $random_hash; ?>  
Content-Type: multipart/alternative; boundary="PHP-alt-<?php echo $random_hash; ?>" 

<? if(isset($body['text'])): ?>
--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/plain; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

<?=$body['text'];?>
<? endif; ?><? if(isset($body['html'])): ?>
--PHP-alt-<?php echo $random_hash; ?>  
Content-Type: text/html; charset="iso-8859-1" 
Content-Transfer-Encoding: 7bit

<?=$body['html'];?> 
<? endif; ?>
--PHP-alt-<?php echo $random_hash; ?>-- 

<? foreach($attachments as $file => $attachment): ?><?php $type = (stripos($file, '.htm') !== FALSE)? 'html' : 'plain'; ?>
--PHP-mixed-<?php echo $random_hash; ?>  
Content-Type: text/<?=$type;?>; name="<?=$file;?>"  
Content-Transfer-Encoding: base64  
Content-Disposition: attachment  

<?php echo $attachment; ?>
<? endforeach; ?>
--PHP-mixed-<?php echo $random_hash; ?>-- 

<?php
	
	//copy current buffer contents into $message variable and delete current output buffer 
	$message = ob_get_clean();
	
	//send message
	return mail($to, $subject, $message, $headers);	

}


