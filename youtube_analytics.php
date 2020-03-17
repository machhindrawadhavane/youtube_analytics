<?php
if(!defined("STDIN")) define("STDIN", "fopen('php://stdin','r')");
echo "<pre>";
/**
 * Sample PHP code for youtubeAnalytics.reports.query
 * See instructions for running these code samples locally:
 * https://developers.google.com/explorer-help/guides/code_samples#php
 */

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
}
require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName('DataImport');
$scope = array("https://www.googleapis.com/auth/youtube.force-ssl", "https://www.googleapis.com/auth/youtubepartner-channel-audit", "https://www.googleapis.com/auth/youtube", "https://www.googleapis.com/auth/youtube.readonly", "https://www.googleapis.com/auth/yt-analytics.readonly", "https://www.googleapis.com/auth/yt-analytics-monetary.readonly","https://www.googleapis.com/auth/youtubepartner");
$client->setScopes($scope);

// TODO: For this request to work, you must replace
//       "YOUR_CLIENT_SECRET_FILE.json" with a pointer to your
//       client_secret.json file. For more information, see
//       https://cloud.google.com/iam/docs/creating-managing-service-account-keys

$client->setAuthConfig('client_secret.json');
$client->setAccessType('offline');
$client->setRedirectUri("http://localhost/YoutubeDataAPI/youtube_analytics.php");
$client->setApprovalPrompt('force');
// Request authorization from the user.
$authUrl = $client->createAuthUrl();
printf("Open this link in your browser:\n%s\n", $authUrl);
print('Enter verification code: ');


$authCode = trim(fgets(STDIN));
// Exchange authorization code for an access token.
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
$client->setAccessToken($accessToken);

$servername = "mysqlservernewjprod.mysql.database.azure.com";
$username = "phantom@mysqlservernewjprod";
$password = "Zurich$1";
$dbname = "youtube_analytics_data";
$conn = new mysqli($servername, $username, $password, $dbname);
if (mysqli_connect_errno()) {
    die("Connection failed: " . $conn->connect_error);
}

if($client->getAccessToken()) {
    // Define service object for making API requests.
    $service = new Google_Service_YouTubeAnalytics($client);
    /*$video_id = 'vpxR2OPSXiY';*/
    $youtube_videos_id_listing = getVideoListingArr();
    foreach($youtube_videos_id_listing as $videoData) {
         $video_id = "video==" .$videoData['v_id'];
          $queryParams = [
              'endDate' => date("Y-m-d"),
              'ids' => 'channel==UCvTAAa8-yBQEVgLLCKYbAiQ',
              'metrics' => 'views,comments,likes,dislikes,estimatedMinutesWatched,averageViewDuration',
              'startDate' => '2017-01-01',
              'filters' =>$video_id,
          ];
          $response = $service->reports->query($queryParams);
          $analytics_data = json_decode(json_encode($response), true);
         $coulumnHeaderCounts = isset($analytics_data['columnHeaders']) ? count($analytics_data['columnHeaders']) : 0;
         $x = 0;
         $queryValues = '`channelId`,`v_id`';
         $vls = "'".$videoData['channelId']."','".$videoData['v_id']."'";
         $updateQueryValues = "channelId='".$videoData['channelId']."',v_id='".$videoData['v_id']."' ";
         while($x < $coulumnHeaderCounts) {
            $queryValues.= ',`'.$analytics_data['columnHeaders'][$x]['name'].'`';
            $vls .= ",'".$analytics_data['rows'][0][$x]."' ";
            $updateQueryValues.= ",".$analytics_data['columnHeaders'][$x]['name']."='".$analytics_data['rows'][0][$x]."' ";
            $x++;
        }
        $query = "select * from youtube_analytics_data where v_id = '".$videoData['v_id']."' ";
        $result = mysqli_query($conn, $query);
        $rowcount=mysqli_num_rows($result);
        if($rowcount > 0){
          echo $sql = "UPDATE youtube_analytics_data SET $updateQueryValues WHERE v_id = '".$videoData['v_id']."' ";
        }else{
          $sql = "INSERT INTO youtube_analytics_data (".$queryValues.") VALUES (".$vls.") ";
        }
        $conn->query($sql);
    }
    $conn->close();
}


function getVideoListingArr(){
  global $conn;
  $my_videos = array();
  $video_listing_query = "select * from video_data"; 
  $result = mysqli_query($conn, $video_listing_query);
  while($row = mysqli_fetch_assoc($result)){
      $my_videos[] = $row;
  }
  return $my_videos;
}

/*function getVideoAnalyticsDataByVideoId($videoId=null){
  return array('columnHeaders'=>array(0=>array('name'=>"views"),1=>array('name'=>"comments")),'rows'=>array(0=>array(0=>0,1=>44)));
}*/




