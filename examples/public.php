<?php 
	require_once("../src/FoursquareAPI.class.php");
	$location = array_key_exists("location",$_GET) ? $_GET['location'] : "Montreal, QC";
?>
<!doctype html>
<html>
<head>
	<title>PHP-Foursquare :: Unauthenticated Request Example</title>
</head>
<body>
<h1>Basic Request Example</h1>
<p>
	Search for venues near...
	<form action="" method="GET">
		<input type="text" name="location" />
		<input type="submit" value="Search!" />
	</form>
<p>Searching for venues near <?php echo $location; ?></p>
<hr />
<?php 
	// Set your client key and secret
	$client_key = "GEGMN5Y0JKHM3HA5B0DCTPCK0NPQJJ4JGE1WVBWDIOF0XX3A";
	$client_secret = "UI0LCMZZYSQGFDVG15LG4D0UYDOZAMF3HO2DIEJMIJS4CVFG";
	// Load the Foursquare API library
	$foursquare = new FoursquareAPI($client_key,$client_secret);
	
	// Generate a latitude/longitude pair using Google Maps API
	list($lat,$lng) = $foursquare->GeoLocate($location);
	
	// Prepare parameters
	$params = array("ll"=>"$lat,$lng");
	
	// Perform a request to a public resource
	$response = $foursquare->GetPublic("venues/search",$params);
	$venues = json_decode($response);
	
	foreach($venues->response->groups as $group):
?>

	<h2><?php echo $group->name; ?></h2>
	<ul>
		<?php foreach($group->items as $venue): ?>
			<li>
				<?php 
					echo $venue->name;
					if(property_exists($venue->contact,"twitter")){
						echo " -- follow this venue <a href=\"http://www.twitter.com/{$venue->contact->twitter}\">@{$venue->contact->twitter}</a>";
					}
				?>
			
			</li>
		<?php endforeach; ?>
	</ul>

<?php endforeach; ?>
</body>
</html>
