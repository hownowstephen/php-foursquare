## Why another Foursquare library?

I couldn't find a php library for the Foursquare API that encapsulated all the functionality of the API while still remaining easy to plug in to an application quickly. This library seeks to fill that need.

### Installation

The best way to install Foursquare library is to use [Composer](https://getcomposer.org/):

```
composer require hownowstephen/php-foursquare:'1.1.*'
```

If you are not using an autoloader, you need to require_once the autoload file:

```
require_once 'vendor/autoload.php';
```

Another way to install is to download the latest version directly from this repository.

### Usage

This library wrappers for php applications to make requests to both public and authenticated-only resources on Foursquare. As well it provides functions that encapsulate the main methods needed to retrieve an auth_token for a user from Foursquare. Instead of having explicit functions for calling API endpoints, you can call individual endpoints directly by their path (eg. [venues/40a55d80f964a52020f31ee3/tips](https://api.foursquare.com/v2/venues/40a55d80f964a52020f31ee3/tips))

#### Querying the API

```php
$foursquare = new FoursquareAPI("<your client key>", "<your client secret>");

// Searching for venues nearby Montreal, Quebec
$endpoint = "venues/search";
	
// Prepare parameters
$params = array("near"=>"Montreal, Quebec");

// Perform a request to a public resource
$response = $foursquare->GetPublic($endpoint,$params);

// Returns a list of Venues
// $POST defaults to false
$venues = $api->GetPublic($endpoint [,$params], $POST=false);
		
// Note: You don't need to add client_id, client_secret, or version to $params

// Setting the access token to enable private requests
// See examples/tokenrequest.php for how to retrieve this
$auth_token = "<your auth token>";
$foursquare->SetAccessToken($auth_token);

// Request a private endpoint (Requires Acting User)
$endpoint_private = "users/self";

// Returns a single user object
$me = $foursquare->GetPrivate($endpoint_private);
// Note: You don't need to add oauth_token to $params
```

#### Authenticating the user (see [examples/tokenrequest.php](examples/tokenrequest.php))

```php
$foursquare = new FoursquareAPI("<your client key>", "<your client secret>");

// Some real url that accepts a foursquare code (see examples/tokenrequest.php)
// This URL should match exactly the URL given in your foursquare developer account settings
$redirect_url = "http://localhost/foursquare_code_handler";

// Generates an authentication link for you to display to your users
// (https://foursquare.com/oauth2/authenticate?...)
$auth_link = $foursquare->AuthenticationLink($redirect_url);
		
// Converts an authentication code (sent from foursquare to your $redirect_url) into an access token
// Use this on your $redirect_url page (see examples/tokenrequest.php for more)
$code = $_GET['code'];	

$token = $foursquare->GetToken($code, $redirect_url);

// again, redirect_url must match the one you set in your account exactly
// and here is where you would store the token for future usage
```

### Adding features & testing

If you want to commit features, please also update the [tests/FoursquareAPITest.php](tests/FoursquareAPITest.php) file with a proper unit test - this will ensure changes can be accepted more quickly.

Running tests requires phpunit, and can be run as the following:

```
export FOURSQUARE_CLIENT_ID=<your client id>
export FOURSQUARE_CLIENT_SECRET=<your client secret>
export FOURSQUARE_TOKEN=<your access token>
phpunit --bootstrap src/FoursquareAPI.class.php tests/FoursquareAPITest.php
```

**PROTIP**: The easiest way to get an access token to test with is to [look yourself up in the api explorer](https://developer.foursquare.com/docs/explore#req=users/self) and pull it directly from the grayed-out url information (OAuth token automatically added. https://api.foursquare.com/v2/users/self?oauth_token=...) underneath the input box.

### What else do I need?

This library does not deal with the management of your tokens - only the interaction between your code and the Foursquare API. If you are using it in an application that needs to use user data via an auth_token, you will need to store the token across sessions separately from the library.
