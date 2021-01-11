<?php
echo "<pre>";
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
}
require_once __DIR__ . '/vendor/autoload.php';
session_start();
ini_set('max_execution_time',0);
date_default_timezone_set('Asia/Calcutta');
require "database_connection.php";
require "db_config.php";
$conn = new Database(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE);
$link = $conn->connect();

$REDIRECT_URI = 'http://localhost/youtube_analytics/youtube.php';
$KEY_LOCATION = __DIR__ . '/client_secret.json';
$TOKEN_FILE   = "token.txt";
$SCOPES  = array("https://www.googleapis.com/auth/youtube.force-ssl", "https://www.googleapis.com/auth/youtubepartner-channel-audit", "https://www.googleapis.com/auth/youtube", "https://www.googleapis.com/auth/youtube.readonly", "https://www.googleapis.com/auth/yt-analytics.readonly", "https://www.googleapis.com/auth/yt-analytics-monetary.readonly","https://www.googleapis.com/auth/youtubepartner");
$client = new Google_Client();
$client->setApplicationName("DataImport");
$client->setAuthConfig($KEY_LOCATION);
// Incremental authorization
$client->setIncludeGrantedScopes(true);
// Allow access to Google API when the user is not present.
$client->setAccessType('offline');
$client->setRedirectUri($REDIRECT_URI);
$client->setScopes($SCOPES);


$isDayWiseDataFlag = true;
$isunsetSessionToekn = false;


if($isunsetSessionToekn == true){
	unset($_SESSION['accessToken']);
	$authUrl = $client->createAuthUrl();
	header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
}


if(isset($_GET['code']) && !empty($_GET['code'])) {
    try {
        // Exchange the one-time authorization code for an access token
        $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        // Save the access token and refresh token in local filesystem
        file_put_contents($TOKEN_FILE, json_encode($accessToken));
        $_SESSION['accessToken'] = $accessToken;
        header('Location: ' . filter_var($REDIRECT_URI, FILTER_SANITIZE_URL));
        exit();
    }
    catch (\Google_Service_Exception $e) {
        print_r($e);
    }
}

if (!isset($_SESSION['accessToken'])) {
    $token = @file_get_contents($TOKEN_FILE);
    if ($token == null) {
        // Generate a URL to request access from Google's OAuth 2.0 server:
        $authUrl = $client->createAuthUrl();
        // Redirect the user to Google's OAuth server
        header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
        exit();
    } else {
        $_SESSION['accessToken'] = json_decode($token, true);
    }
}

$client->setAccessToken($_SESSION['accessToken']);




/* Refresh token when expired */
if ($client->isAccessTokenExpired()) {
    // the new access token comes with a refresh token as well
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($TOKEN_FILE,json_encode($client->getAccessToken()));
}



if($client->getAccessToken()) {
      $service = new Google_Service_YouTubeAnalytics($client);
      $youtube_videos_id_listing = getVideoListingArr();
      $recordCounter=0;
      foreach($youtube_videos_id_listing as $videoData) {
          $video_id = "video==".$videoData['v_id'];
		  $channel_id = "channel==".$videoData['channelId'];
		  $video_start_date = date('Y-m-d',strtotime($videoData['publishedAt']));
		  if($videoData['date_start_daywise'] != '') {
			  $video_start_date = date('Y-m-d',strtotime($videoData['date_start_daywise']));
		  }
		  echo $recordCounter.'/'.$video_start_date."/".$videoData['v_id']."\n";
		  $video_end_date = date('Y-m-d');
		  $queryParams = [
					'endDate' => $video_end_date,
					'ids' =>$channel_id,
					'metrics' => 'views,comments,likes,dislikes,shares,estimatedMinutesWatched,averageViewDuration,averageViewPercentage',
					'filters' =>$video_id,
					'dimensions' =>'day',
					'sort' => 'day',
					'startDate' => $video_start_date
				];
            $response = $service->reports->query($queryParams);
            $analytics_data = json_decode(json_encode($response), true);
            insertUpdateDayWiseYoutubeAnalyticsData($videoData,$analytics_data);
			$recordCounter++;
     }
     echo "data_dump_successfully";      
}


function insertUpdateDayWiseYoutubeAnalyticsData($videoData=array(),$analytics_data=array()){
    global $link;
    $insertQueryColumns = '`channelId`,`v_id`';
    $updateQueryCoulumnValues = "channelId='".$videoData['channelId']."',v_id='".$videoData['v_id']."' ";
    $monthCounter = 0;
    $monthDataArr = array();
    foreach($analytics_data['rows'] as $analyticData){
        $vls = "'".$videoData['channelId']."','".$videoData['v_id']."'";
        $insertQueryColumns = '`channelId`,`v_id`';
        $updateQueryCoulumnValues = "channelId='".$videoData['channelId']."',v_id='".$videoData['v_id']."' ";
        foreach ($analytics_data['columnHeaders'] as $coulmnName) {
           $insertQueryColumns.= ',`'.$coulmnName['name'].'`';
        }
        $coulmnCounterUpdateQuery = 0;
        foreach($analyticData as $innerMonthlyData){
            $vls .= ",'".$innerMonthlyData."' ";
            $updateQueryCoulumnValues.= ",".$analytics_data['columnHeaders'][$coulmnCounterUpdateQuery]['name']."='".$innerMonthlyData."' ";
            $coulmnCounterUpdateQuery++;
        }
        $query = "select * from youtube_videos_insights_daywise where v_id = '".$videoData['v_id']."' and day = '".$analyticData[0]."' ";
        $result = mysqli_query($link, $query);
        $rowcount=mysqli_num_rows($result);
        if($rowcount > 0){
            $sql = "UPDATE youtube_videos_insights_daywise SET $updateQueryCoulumnValues WHERE v_id = '".$videoData['v_id']."' and day = '".$analyticData[0]."' ";
        }else{
          $sql = "INSERT INTO youtube_videos_insights_daywise (".$insertQueryColumns.") VALUES (".$vls.") ";
        }
		
		/*update statuses*/
        $link->query($sql);
		$sqlStatuses = "UPDATE youtube_statuses SET day_v_id = '".$videoData['v_id']."' WHERE channel_id = '".$videoData['channelId']."' ";
		$link->query($sqlStatuses);
		
		$since_video_day = date('Y-m-d',strtotime($analyticData[0]));
		$until_video_day = date('Y-m-d',strtotime('+1day', strtotime($analyticData[0])));
		$sqlVideoStatuses = "UPDATE video_data SET date_start_daywise = '".$since_video_day."',date_end_daywise = '".$until_video_day."' WHERE channelId = '".$videoData['channelId']."' AND v_id = '".$videoData['v_id']."' ";
		$link->query($sqlVideoStatuses);
		
        $monthCounter++;
    }
}


function getVideoListingArr(){
  global $link;
  $my_videos = array();
  $video_listing_query = 'select * from video_data where channelId = "UCsxUfRSw0AOnnghHpuN8dvA" order by publishedAt asc'; 
  $result = mysqli_query($link, $video_listing_query);
  while($row = mysqli_fetch_assoc($result)){
      $my_videos[] = $row;
  }
  return $my_videos;
}

  
