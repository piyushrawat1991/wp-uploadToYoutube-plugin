<?php
$oauthClientID = get_option( 'uty_google_client_id' );
$oauthClientSecret = get_option( 'uty_google_client_secret' );
$baseUri = plugin_dir_path( __FILE__ );
$redirectUri = admin_url('admin.php?page=upload-to-youtube');
$api_key = get_option( 'uty_google_client_api' );
$channel_id = get_option( 'uty_youtube_channel' );

define('API_KEY',$api_key);
define('OAUTH_CLIENT_ID',$oauthClientID);
define('OAUTH_CLIENT_SECRET',$oauthClientSecret);
define('REDIRECT_URI',$redirectUri);
define('BASE_URI',$baseUri);
define('CHANNEL_ID',$channel_id);

require_once 'src/Google/autoload.php'; 
require_once 'src/Google/Client.php';
require_once 'src/Google/Service/YouTube.php';

$client = new Google_Client();
$client->setAccessType('offline');
$client->setDeveloperKey(API_KEY);
$client->setClientId(OAUTH_CLIENT_ID);
$client->setClientSecret(OAUTH_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$client->setRedirectUri(REDIRECT_URI);
  

$youtube = new Google_Service_YouTube($client);