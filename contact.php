<html>
<head>
<?php
include_once 'GmailOath.php';
include_once 'configfile.php';
include_once 'connect.php';
session_start();
$debug=0;
$oauth =new GmailOath($consumer_key, $consumer_secret, $debug, $callback);
$getcontact_access=new GmailGetContacts();
$request_token=$oauth->rfc3986_decode($_GET['oauth_token']);
$request_token_secret=$oauth->rfc3986_decode($_SESSION['oauth_token_secret']);
$oauth_verifier= $oauth->rfc3986_decode($_GET['oauth_verifier']);
$contact_access = $getcontact_access->get_access_token($oauth,$request_token, $request_token_secret,$oauth_verifier, false, true, true);
$access_token=$oauth->rfc3986_decode($contact_access['oauth_token']);
$access_token_secret=$oauth->rfc3986_decode($contact_access['oauth_token_secret']);
$contacts= $getcontact_access->GetContacts($oauth, $access_token, $access_token_secret, false, true,$emails_count);

?>
</head>
<body style="background-color:#CCCCCC; font-family:Century Gothic;" >

<?php
echo "<h2>Email Contacts</h2>";
foreach($contacts as $k => $a)
{
	$final = end($contacts[$k]);
	foreach($final as $email)
	{
		echo "<div style='margin-left:15px'>";
		if (isset($email["address"])) 
		{
		echo $email["address"] ."<br />"; 
		$mail=$email["address"];
		mysqli_query($con,"INSERT INTO emails (email) VALUES ('$mail')");}
		//echo $email["name"] ."<br />";
		//echo 'Email: '.$email['gd$email']['0']['address'].'Name: '.$email['title']['$t'].'<BR>';
		echo "</div>";
	}
}

mysqli_close($con);
?>
<br /><br />
<a href="index.php">Back</a>
</body>
</html>