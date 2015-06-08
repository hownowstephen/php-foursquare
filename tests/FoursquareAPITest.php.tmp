<?php

DEFINE('CLIENT_ID', getenv('FOURSQUARE_CLIENT_ID'));
DEFINE('CLIENT_SECRET', getenv('FOURSQUARE_CLIENT_SECRET'));
DEFINE('TOKEN', getenv('FOURSQUARE_TOKEN'));

class FoursquareAPITest extends PHPUnit_Framework_TestCase {

    public function testPublicEnvironment(){
        $this->assertFalse(CLIENT_ID == false, "Must set the FOURSQUARE_CLIENT_ID environment variable to run public tests");
        $this->assertFalse(CLIENT_SECRET == false, "Must set the FOURSQUARE_CLIENT_SECRET environment variable to run public tests");
    }

    public function testPrivateEnvironment(){
        $this->assertFalse(TOKEN == false, "Must set the FOURSQUARE_TOKEN environment variable to run private tests");
    }

    public function testPublicEndpoint(){   
        $foursquare = new FoursquareAPI(CLIENT_ID, CLIENT_SECRET);
        $venues = json_decode($foursquare->GetPublic('venues/search', array('near'=>'Montreal, QC')));

        // Ensure we get a success response
        $this->assertLessThan(400, $venues->meta->code, $venues->meta->errorDetail);
    }

    public function testLanguageSupport(){   
        $foursquare = new FoursquareAPI(CLIENT_ID, CLIENT_SECRET, '', 'v2', 'fr');
        $categories = json_decode($foursquare->GetPublic('venues/categories'));

        foreach($categories->response->categories as $category){
            if($category->id == '4d4b7104d754a06370d81259'){
                $this->assertEquals($category->name, 'Culture et loisirs', "Locale failed or \"{$category->name}\" is a new translation");
                $run = true;
                break;
            }
        }

        $this->assertEquals($run, true, 'Test category no longer exists in fr locale, update test.');
        // Ensure we get a success response
        $this->assertLessThan(400, $venues->meta->code, $venues->meta->errorDetail);
    }

    public function testPrivateEndpoint(){

        // Load the API and set the token
        $foursquare = new FoursquareAPI(CLIENT_ID, CLIENT_SECRET);
        $foursquare->SetAccessToken(TOKEN);

        // Load response & convert to Stdclass object
        $venues = json_decode($foursquare->GetPrivate('users/self'));

        // Ensure we get a success response
        $this->assertLessThan(400, $venues->meta->code, $venues->meta->errorDetail);
    }

    public function testCheckin(){

        $foursquare = new FoursquareAPI(CLIENT_ID, CLIENT_SECRET);
        $foursquare->SetAccessToken(TOKEN);

        // Checks the acting user in at Aux Vivres in Montreal, Canada
        $response = json_decode($foursquare->GetPrivate("checkins/add", array("venueId"=>"4ad4c06bf964a5207ff920e3"), $POST=true));

        $this->assertLessThan(400, $response->meta->code, $response->meta->errorDetail);
    }
}
