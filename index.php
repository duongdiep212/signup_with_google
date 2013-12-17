<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php 
session_start();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Goole OAuth Gmail contacts import</title>
<meta content=' Goole OAuth Gmail contacts import.' name='description'/> 
<meta content='Google, Oauth, php, Gmail, Contacts,import' name='keywords'/> 
<script type="text/javascript">
  (function() {
    var po = document.createElement('script');
    po.type = 'text/javascript'; po.async = true;
    po.src = 'https://plus.google.com/js/client:plusone.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(po, s);
  })();
	function onSignOut()
	{
		gapi.auth.signOut();
	}
  </script>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js" ></script>
<meta content=' Goole OAuth Gmail contacts import.' name='description'/> 
<meta content='Google, Oauth, php, Gmail, Contacts,import' name='keywords'/> 
</head>
	
<body style="background-color:#CCCCCC; font-family:'Century Gothic';">	
	<div width="800px" align="center">
		<div id="gConnect" style="border:1px solid; margin:200px 500px 0px 450px; padding-bottom:15px;">
		<p style="font-family:'Century Gothic'; font-size:25px">Login with Google+: </p>
			<button class="g-signin"
				data-scope="https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile"
				data-requestvisibleactions="http://schemas.google.com/AddActivity"
				data-clientId="34760705102-aolcts0u7j60maegrc1uc5rqu7tlp149.apps.googleusercontent.com"
				data-accesstype="offline"
				data-callback="onSignInCallback"
				data-theme="dark"
				data-cookiepolicy="single_host_origin">
			</button>
		</div>
	</div>
	<div id="authOps" width="100px" style="display:none; margin-left:450px;">
		
		<h2>To Import Contacts click <?php include('GmailConnect.php'); ?></h2>
		
		<p>User is now signed in to the app using Google+</p>
		<button id="disconnect" onclick="onSignOut()" >Sign Out</button>

		<h2>Profile Information</h2>
		<div width="200px" style="border:1px solid; margin-right:400px; padding:10px;">
			<div id="pimg"></div>
			<div id="pid"></div>
			<div id="name"></div>
			<div id="gender"></div>
			<div id="email"></div>
			<div id="rstatus"></div>
			<div id="purl"></div>
		</div>

	</div>

</body>
<script type="text/javascript">
var helper = (function() {
  var authResult = undefined;
  return {
    onSignInCallback: function(authResult) {
      if (authResult['access_token']) 
	  {
        this.authResult = authResult;
        helper.connectServer();
        gapi.client.load('plus','v1',this.renderProfile);
      } 
	  else if (authResult['error']) 
	  {
        console.log('There was an error: ' + authResult['error']);
        $('#authOps').hide();
        $('#gConnect').show();
      }
      console.log('authResult', authResult);
    },
    
    renderProfile: function() 
	{	
		gapi.client.load('oauth2', 'v2', function() {
		gapi.client.oauth2.userinfo.get().execute(function(resp) {
			// Shows user email
			$('#email').append(
              $('<p>Email : ' + resp.email + '</p>'));
		  })
		});
		
      var request = gapi.client.plus.people.get( {'userId' : 'me'} );
      request.execute( function(profile) {
		  $('#pimg').empty();
		  $('#pid').empty();
		  $('#name').empty();
		  $('#purl').empty();
		  $('#gender').empty();
		  $('#rstatus').empty();
          if (profile.error) 
		  {
            $('#profile').append(profile.error);
            return;
          }
		  
          $('#pimg').append($('<p><img style="margin:10px 10px 10px 10px;" src=\"' + profile.image.url + '\"></p>'));
          $('#name').append($('<p>Name : '+ profile.displayName +'</p>' ));
		  $('#purl').append($('<p> Profile : '+ profile.url +'</p>' ));
		  $('#pid').append($('<p> Id : '+ profile.id +'</p>' ));
		});

		gapi.client.load('plus', 'v1', function() {
		  gapi.client.plus.people.get( {'userId' : 'me'} ).execute(function(resp) {
			// Shows profile information
			$('#gender').append($('<p>Gender : '+ resp.gender +'</p>' ));
			$('#rstatus').append($('<p>Relationship Status: '+ resp.relationshipStatus +'</p>' ));
		  })
		});
      $('#authOps').show();
      $('#gConnect').hide();
    },
    /**
     * Calls the server endpoint to disconnect the app for the user.
     */
    disconnectServer: function() 
	{
      // Revoke the server tokens
      $.ajax({
        type: 'POST',
        url: window.location.href + '/disconnect',
        async: false,
        success: function(result) {
          console.log('revoke response: ' + result);
          $('#authOps').hide();
          $('#profile').empty();
          $('#visiblePeople').empty();
          $('#gConnect').show();
        },
        error: function(e) {
          console.log(e);
        }
      });
    },
    
	connectServer: function() 
	{
      console.log(this.authResult.code);
      $.ajax({
        type: 'POST',
        url: window.location.href + '/connect?state={{ STATE }}',
        contentType: 'application/octet-stream; charset=utf-8',
        success: function(result) {
          console.log(result);
          helper.people();
        },
        processData: false,
        data: this.authResult.code
      });
    },
    
	people: function() 
	{
      $.ajax({
        type: 'GET',
        url: window.location.href + '/people',
        contentType: 'application/octet-stream; charset=utf-8',
        success: function(result) {
          helper.appendCircled(result);
        },
        processData: false
      });
    },
    
	appendCircled: function(people) {
      $('#visiblePeople').empty();

      $('#visiblePeople').append('Number of people visible to this app: ' +
          people.totalItems + '<br/>');
      for (var personIndex in people.items) {
        person = people.items[personIndex];
        $('#visiblePeople').append('<img src="' + person.image.url + '">');
      }
    },
  };
})();

$(document).ready(function() {
  $('#disconnect').click(helper.disconnectServer);
  if ($('[data-clientid="YOUR_CLIENT_ID"]').length > 0) {
    alert('This sample requires your OAuth credentials (client ID) ' +
        'from the Google APIs console:\n' +
        '    https://code.google.com/apis/console/#:access\n\n' +
        'Find and replace YOUR_CLIENT_ID with your client ID and ' +
        'YOUR_CLIENT_SECRET with your client secret in the project sources.'
    );
  }
});

function onSignInCallback(authResult) {
  helper.onSignInCallback(authResult);
}
</script>
</html>
