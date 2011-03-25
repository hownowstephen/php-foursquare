<?php 
	require_once("../src/FoursquareAPI.class.php");
	$name = array_key_exists("name",$_GET) ? $_GET['name'] : "Foursquare";
?>
<!doctype html>
<html>
<head>
	<title>PHP-Foursquare :: Authenticated Request Example</title>
</head>
<body>
<h1>Authenticated Request Example</h1>
<p>
	Search for users by name...
	<form action="" method="GET">
		<input type="text" name="name" />
		<input type="submit" value="Search!" />
	</form>
<p>Searching for users with name similar to <?php echo $name; ?></p>
<hr />
<?php 
	// Set your client key and secret
	$client_key = "GEGMN5Y0JKHM3HA5B0DCTPCK0NPQJJ4JGE1WVBWDIOF0XX3A";
	$client_secret = "UI0LCMZZYSQGFDVG15LG4D0UYDOZAMF3HO2DIEJMIJS4CVFG";
	// Set your auth token, loaded using the workflow described in tokenrequest.php
	$auth_token = "JHGM4YCJFHJCQ5A0TDTOYQVU2SL055WTFK0FL5IME1ULLGMX";
	// Load the Foursquare API library
	$foursquare = new FoursquareAPI($client_key,$client_secret);
	$foursquare->SetAccessToken($auth_token);
	
	// Prepare parameters
	$params = array("name"=>$name);
	
	// Perform a request to a authenticated-only resource
	$response = $foursquare->GetPrivate("users/search",$params);
	$users = json_decode($response);
	
	// NOTE:
	// Foursquare only allows for 500 api requests/hr for a given client (meaning the below code would be
	// a very inefficient use of your api calls on a production application). It would be a better idea in
	// this scenario to have a caching layer for user details and only request the details of users that
	// you have not yet seen. Alternatively, several client keys could be tried in a round-robin pattern 
	// to increase your allowed requests.
	
?>
	<ul>
		<?php foreach($users->response->results as $user): ?>
			<li>
				<?php 
					if(property_exists($user,"firstName")) echo $user->firstName . " ";
					if(property_exists($user,"lastName")) echo $user->lastName;
					
					// Grab user twitter details
					$request = $foursquare->GetPrivate("users/{$user->id}");
					$details = json_decode($request);
					$u = $details->response->user;
					if(property_exists($u->contact,"twitter")){
						echo " -- follow this user <a href=\"http://www.twitter.com/{$u->contact->twitter}\">@{$u->contact->twitter}</a>";
					}
					
				?>
			
			</li>
		<?php endforeach; ?>
	</ul>
</body>
</html>
