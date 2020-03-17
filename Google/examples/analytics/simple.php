<?php
require_once '../../src/Google_Client.php';
require_once '../../src/contrib/Google_AnalyticsService.php';
require_once '../../src/contrib/Google_YouTubeAnalyticsService.php';
session_start();

$client = new Google_Client();
$client->setApplicationName("DataImport");
$scope = array("https://www.googleapis.com/auth/youtubepartner-channel-audit", "https://www.googleapis.com/auth/youtube", "https://www.googleapis.com/auth/youtube.readonly", "https://www.googleapis.com/auth/yt-analytics.readonly", "https://www.googleapis.com/auth/yt-analytics-monetary.readonly","https://www.googleapis.com/auth/youtubepartner");
$client->setScopes($scope);
$service = new Google_AnalyticsService($client);
$youtubeAnalyticsService = new Google_YouTubeAnalyticsService($client);
// Visit https://code.google.com/apis/console?api=analytics to generate your
// client id, client secret, and to register your redirect uri.
// $client->setClientId('insert_your_oauth2_client_id');
// $client->setClientSecret('insert_your_oauth2_client_secret');
// $client->setRedirectUri('insert_your_oauth2_redirect_uri');
// $client->setDeveloperKey('insert_your_developer_key');


if (isset($_GET['logout'])) {
  unset($_SESSION['token']);
}

if (isset($_GET['code'])) {
  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if ($client->getAccessToken()) {
  $video_id = "jhwUVMC3ngs";
  $video_id = "video==" .$video_id;
  
  $channel_id = 'channel==UCvTAAa8-yBQEVgLLCKYbAiQ';
  $ids = $channel_id;
  $end_date = date("Y-m-d"); //current date 
  $start_date = '2017-01-01'; //date when you uploaded your video
  $optparams = array(
      'filters' => $video_id,
  );

  $metric = 'likes,shares';
  $reports = $youtubeAnalyticsService->reports->query($ids, $start_date, $end_date, $metric);
  print_r($reports);
  //$props = $service->data_ga->get('channel==UCvTAAa8-yBQEVgLLCKYbAiQ','2017-01-01','2018-05-01','views,comments,likes,dislikes,estimatedMinutesWatched,averageViewDuration');
  //print "<h1>Web Properties</h1><pre>" . print_r($props, true) . "</pre>";


  /*$props = $service->management_webproperties->listManagementWebproperties("~all");
  print "<h1>Web Properties</h1><pre>" . print_r($props, true) . "</pre>";

  $accounts = $service->management_accounts->listManagementAccounts();
  print "<h1>Accounts</h1><pre>" . print_r($accounts, true) . "</pre>";

  $segments = $service->management_segments->listManagementSegments();
  print "<h1>Segments</h1><pre>" . print_r($segments, true) . "</pre>";

  $goals = $service->management_goals->listManagementGoals("~all", "~all", "~all");
  print "<h1>Segments</h1><pre>" . print_r($goals, true) . "</pre>";*/

  $_SESSION['token'] = $client->getAccessToken();
  print_r($_SESSION);
} else {
  $authUrl = $client->createAuthUrl();
  print "<a class='login' href='$authUrl'>Connect Me!</a>";
}