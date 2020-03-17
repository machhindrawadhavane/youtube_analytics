<?php

function youtubeVideosMetaDataAPIListing(){
	$API_Url = 'https://www.googleapis.com/youtube/v3/';
	$API_Key = 'AIzaSyDQkWigM9bDK3ZlNvLljoPormq-Dpjy8AQ';
	$ChannelId = 'UCvTAAa8-yBQEVgLLCKYbAiQ';//Newj
	$parameters = ['id'=>$ChannelId,'part'=>'contentDetails','key'=>$API_Key];
	$channel_URL = $API_Url."channels?".http_build_query($parameters);
	$jsonDetails = json_decode(file_get_contents($channel_URL),true);
	$playlistId = $jsonDetails['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
	$parameters = ['part'=>'snippet','key'=>$API_Key,'playlistId'=>$playlistId,'maxResults'=>50,'order'=>'date'];
	$channel_URL = $API_Url."playlistItems?".http_build_query($parameters);
	$jsonDetails = json_decode(file_get_contents($channel_URL),true);
	$my_videos = [];
	foreach($jsonDetails['items'] as $video){
	  $my_videos[] = array('v_id'=>$video['snippet']['resourceId']['videoId'],'v_name'=>$video['snippet']['title'],'channelId'=>$video['snippet']['channelId'],'publishedAt'=>$video['snippet']['publishedAt']);
	}


	while (isset($jsonDetails['nextPageToken'])) {
	  $next_page_URL = $channel_URL.'&pageToken='.$jsonDetails['nextPageToken'];
	  $jsonDetails = json_decode(file_get_contents($next_page_URL),true);
	  foreach($jsonDetails['items'] as $video){
	    $my_videos[] = array('v_id'=>$video['snippet']['resourceId']['videoId'],'v_name'=>$video['snippet']['title'],'channelId'=>$video['snippet']['channelId'],'publishedAt'=>$video['snippet']['publishedAt']);
	  }
	}

	return $my_videos;
}

$servername = "mysqlservernewjprod.mysql.database.azure.com";
$username = "phantom@mysqlservernewjprod";
$password = "Zurich$1";
$dbname = "youtube_analytics_data";
$conn = new mysqli($servername, $username, $password, $dbname);
if (mysqli_connect_errno()) {
    die("Connection failed: " . $conn->connect_error);
}

$my_videos = youtubeVideosMetaDataAPIListing();
if(count($my_videos) > 0){
	foreach ($my_videos as $videoData) {
		$query = "select * from video_data where v_id = '".$videoData['v_id']."' "; 
		$result = mysqli_query($conn, $query);
		if(!mysqli_num_rows($result) > 0){
			$sql = "INSERT INTO video_data (v_id, v_name, channelId,publishedAt) VALUES ('".$videoData['v_id']."','".trim($videoData['v_name'])."','".$videoData['channelId']."','".$videoData['publishedAt']."') ";
			$conn->query($sql);
		}
	}
}
$conn->close();

echo '<pre>';print_r($my_videos);

