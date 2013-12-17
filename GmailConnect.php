<html>
<head>
<?php
include_once 'GmailOath.php';
include_once 'configfile.php';
$debug=0;
$oauth =new GmailOath($consumer_key, $consumer_secret, $debug, $callback);
$getcontact=new GmailGetContacts();
$access_token=$getcontact->get_request_token($oauth, false, true, true);
$_SESSION['oauth_token']=$access_token['oauth_token'];
$_SESSION['oauth_token_secret']=$access_token['oauth_token_secret'];
?>

</head>
<body>
	
		<a href="https://www.google.com/accounts/OAuthAuthorizeToken?oauth_token=<?php echo $oauth->rfc3986_decode($access_token['oauth_token']) ?>">
		here
		</a>
	
</body>
</html>