<?php
# Murmur-REST Server module for WHMCS
# (C) 2016 James Fraser <fwaggle@fwaggle.org>

if (!defined("WHMCS")) {
	die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

function murmurrest_MetaData()
{
	return array(
		'DisplayName' => 'Mumble Server (REST)',
		'APIVersion' => '1.1', // Use API Version 1.1
		'RequiresServer' => true, // Set true if module requires a server to work
		'DefaultNonSSLPort' => '80', // Default Non-SSL Connection Port
		'DefaultSSLPort' => '443', // Default SSL Connection Port
	);
}

function murmurrest_ConfigOptions() {
	return array(
		"Slots" => array(
			"Type" => "text",
			"Size" => "25",
			"Default" => "25",
		),
	);
}

function murmurrest_TestConnection(array $params) {
	$ch =  murmurrest_buildGetRequest($params, '/stats/');
	$resp = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($code == 200)
		return array('success'=>true);
	
	return array('success' => false, 'error' => print_r($resp));
}

function murmurrest_CreateAccount($params) {
	# Initial configuration.
	$post = [
		'users' => $params['configoption1'],
		];

	# First, create a new server.
	$ch = murmurrest_buildPostRequest($params, '/servers/', $post);
	$resp = curl_exec($ch);
	$server = json_decode($resp);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	# Check for errors
	if ($code != 200 or $server == NULL)
	{
		logModuleCall('murmurrest', __FUNCTION__,
		$params, $resp, $code);

		return('Error: ' . $resp);
	}

	# Got the server ID
	$id = intval($server->{'id'});

	# Check for errors
	if ($code != 200 or $server == NULL)
	{
		logModuleCall('murmurrest', __FUNCTION__,
		$params, $resp, $code);

		return('Error: ' . $resp);
	}

	# Update the database
	# Words cannot express how much I hate interacting with the WHMCS DB
	# and I have no idea why these things can't be updated without
	# resorting to DB queries.
	Capsule::table('tblhosting')
		->where('id', (int)$params['serviceid'])
		->update(
			[
				'dedicatedip' => $server->{'address'},
				'username' => 'SuperUser',
			]
		);	
	
	# Set superuser password
	$url = '/servers/' . $id . '/setsuperuserpw';
	$post = ['password'=>$params['password']];
	$ch = murmurrest_buildPostRequest($params, $url , $post);
	$resp = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	logModuleCall(
            'murmurrest',
            __FUNCTION__,
            $params,
            $id, print_r($server, true));
        
    return 'success';
}

function murmurrest_ClientArea($params) {
	$server = $params['templatevars']['dedicatedip'];
	$output  = sprintf("Server Address: <a href=\"mumble://%s/?version=1.2.0\">%s</a><br />\n", $server, $server);
	return $output;
}

# Build a curl handle for a GET request with most of the
# settings already correct. Returns a curl handle.
# User is expected to curl_close() it.
function murmurrest_buildGetRequest($params, $url) {
	$ch = curl_init();
	$url = $params['serverhttpprefix'] . '://' . 
		$params['serverhostname'] . ':' .
		$params['serverport'] . $url;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "/dev/null");
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
	curl_setopt($ch, CURLOPT_USERPWD,
		$params['serverusername'] . ':' .
		$params['serverpassword']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	return $ch;
}

# Build a curl handle for a POST request with most of the
# settings already correct. Returns a curl handle.
# User is expected to curl_close() it.
function murmurrest_buildPostRequest($params, $url, $vars) {
	$ch = curl_init();
	$url = $params['serverhttpprefix'] . '://' . 
		$params['serverhostname'] . ':' .
		$params['serverport'] . $url;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "/dev/null");
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
	curl_setopt($ch, CURLOPT_USERPWD,
		$params['serverusername'] . ':' .
		$params['serverpassword']);
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
	return $ch;
}

?>