README
======

Why another Foursquare library?
-------------------------------

I couldn't find a php library for the Foursquare API that encapsulated all the functionality
of the API while still remaining easy to plug in to an application quickly. This library seeks
to fill that need

What this library does
----------------------

Provides wrappers for php applications to make requests to both public and authenticated-only
resources on Foursquare. As well it provides functions that encapsulate the main methods needed
to retrieve an auth_token for a user from Foursquare.

What are the basic methods?
---------------------------

	GetPublic($endpoint[,$params])
		Performs a request using your $client_id and $client_secret to a public api resource
		$endpoint is the api endpoint
		$params is an associative array of parameters to pass
		Note: You don't need to add client_id and client_secret to $params
		
	GetPrivate($endpoint[,$params])
		Peforms a request using your $auth_token to a private api resource
		$endpoint is the api endpoint
		$params is an associative array of parameters to pass
		Note: You don't need to add oauth_token to $params
		
	AuthenticationLink($redirect)
		Returns a link to the Foursquare web authentication page
		$redirect is your configured redirect_uri
		Note: the $redirect provided must match the one you inserted when requesting your $client_id on Foursquare
		
	GetToken($code,$redirect)
		Returns a valid Foursquare auth_token
		$code is the code GET param supplied by the Foursquare web auth to your redirect_uri
		$redirect is your configured redirect_uri
		Note: the $redirect provided must match the one you inserted when requesting your $client_id on Foursquare

What else do I need?
--------------------

This library does not deal with the management of your tokens - only the interaction between
your code and the Foursquare API. If you are using it in an application that needs to use user
data via an auth_token, you will need to handle the storage of tokens for usage at later dates.
