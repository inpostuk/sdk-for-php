<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

define('ROOTPATH', __DIR__);

require 'src/Inpost/Api/Client.php';

$client_token = '---YOUR---TOKEN---';
$client_email = 'it_support@inpost.co.uk';

$client = new Inpost_Api_Client($client_token, Inpost_Api_Client::SANDBOX_API_ENDPOINT, $client_email);
$response = array();

//$response = $client->getMachinesList();

//$response = $client->createParcel('07712341234', 'UKLON32038', 'A', '10', 'john@doe.com');

//$response = $client->pay('13883900004874');

//$response = $client->getParcelData('13883900004874');

//$response = $client->getOutboundLabel('CP000001639UK', Inpost_Api_Client::LABEL_FILE_FORMAT_PDF, Inpost_Api_Client::LABEL_SIZE_A6, true);

/*
$target_address = array(
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company_name' => 'Beaconsfield Town Council',
    'street1' => 'Town Hall',
    'street2' => 'Penn Road',
    'city' => 'Beaconsfield',
    'post_code' => 'HP9 2PP',
    'province' => 'Buckinghamshire',
    'phone' => '7723452345',
    'email' => 'john@doe.com'
);
*/

//$response = $client->createReturn('7712341234', 'john@doe.com', 'a', $target_address);

//$response = $client->getReturnLabel('7364418727');
//$client->validateAccount();

//var_dump($response);
// CP000001639UK