<?php
define("TOKEN",     "pasteTokenThere");
function Query($query)
{
	$host	= "localhost";
	$user	= "user";
	$pwd	= "pwd";
	$dbase	= "dbase";
	$answerLine	= "";
	$answer= array();
	$link = mysqli_connect($host, $user, $pwd, $dbase);
	if (mysqli_connect_errno()) {
	    exit();
	}
	if (mysqli_multi_query($link, $query)) {
	    do {
	        if ($result = mysqli_store_result($link)) {
	            while ($row = mysqli_fetch_row($result)) {
					$answer[]=$row;
	            }
	            mysqli_free_result($result);
	        }
	        if (mysqli_more_results($link)) {
	        	;
	        }
	    } while (mysqli_more_results($link)&&mysqli_next_result($link));
	}
	mysqli_close($link);
	return $answer;
}
function sendMessage($chat,$messageText)
{
	file_get_contents('https://api.telegram.org/bot'.TOKEN.'/sendMessage?chat_id='.$chat.'&text='.prepareMessage($messageText));
}
function getQueue($user,$all=false)
{
	//get user's database id
	$query="SELECT `id` FROM `trans` WHERE `user`='{$user}'";
	$result= Query($query);
	if (count($result[0])!=0||$all)
	{
		//get user's database queue
		if ($all)	$query="SELECT COUNT(`id`) FROM `trans` WHERE `style`!=''";
		else		$query="SELECT COUNT(`id`) FROM `trans` WHERE `style`!='' AND `id`<={$result[0][0]}";
		$result= Query($query);
		if (count($result[0])!=0) return "".$result[0][0];
		else return 'N';
	}
}
function prepareMessage($message)
{
	$message = str_replace(' ','%20',$message);
	return $message;
}
$json = file_get_contents('php://input');
$data = json_decode($json, true);
$message	= $data['message']['text'];
$chat		= $data['message']['chat']['id'];
$user		= $data['message']['from']['id'];
if(isset($data['message']['photo'])) //////////////////////////////// photo interaction
{
	$photos		= $data['message']['photo'];
	$count=count($photos)-1;
	$photo=$photos[''.$count];
	$file_id=$photo['file_id'];
	$file_info=json_decode(file_get_contents('https://api.telegram.org/bot'.TOKEN.'/getFile?file_id='.$file_id),true);
	$path=$file_info['result']['file_path'];
	$url='https://api.telegram.org/file/bot'.TOKEN.'/'.$path;
	file_put_contents($path, file_get_contents($url));
	//check for existing queries
	$query="SELECT `user`,`content`,`style`,`required` FROM `trans` WHERE `user`='{$user}'";
	$result= Query($query);
	if (count($result[0])==0)	//add content
	{	
		$query="INSERT INTO `trans` (`user`, `content`) VALUES ('{$user}', '{$path}')";
		$result= Query($query);
		sendMessage($chat,"Content saved. Now, send me a style image");
	}
	else if(empty($result[0][2]))
	{
		$requiredDate=date("Y-m-d H:i:s");
		$query="UPDATE `trans` 
			SET 
				`style`='{$path}', 
				`required`='{$requiredDate}'
			WHERE `user`='{$user}'";
		$result= Query($query);
		
		$queue=getQueue($user);
		sendMessage($chat,"Style saved. Wait for ".(4*$queue+4)." minutes. You are ".$queue."-th in queue");
	}
	else 
	{
		$queue=getQueue($user);
		sendMessage($chat,"Wait for ".(4*$queue+4)." minutes. Or cancel. You are ".getQueue($user)."-th in queue");
	}
}
else ////////////////////////////// text interaction
{
	if($message=='/start') 
	{
		//check for existing queries
		$query="SELECT 
			`user`, 
			`content`, 
			`style`,
			`required`
			FROM 
			`trans` 
			WHERE 
			`user`='{$user}'";
		$result= Query($query);
		if (count($result[0])==0)		sendMessage($chat,prepareMessage('Tips and tricks:%0aThe content should have  a clear-cut distinct object.%0aAs content, advise to try with:%0a- Cats%0a- Dogs%0a- Sunset city%0a- Plane view.%0aThe style should have a simply clear-cut lines, several uniform tones. The same scetch type object in style will be very helpful.%0aAs style, advise to try with:%0a- Cubisme%0a- Expressionism%0a- Tachisme%0a- Comics%0a- Pop art%0aIn addition to the usual sending of photos, the use of the @pic inline mode is very convenient.%0aDo not stop where you reach. Keep experimenting.%0aEvery time, you want to made a style transfer, first, send me a content image!'));
		else if(empty($result[0][2]))	sendMessage($chat,'I have a content. Now send me a style image');
		else sendMessage($chat,"Wait for result. Or cancel. You are ".getQueue($user)."-th in queue");
	}
	if($message=='/cancel') 
	{
		//check for existing queries
		$query="SELECT 
			`user`, 
			`content`, 
			`style`,
			`required`
			FROM 
			`trans` 
			WHERE 
			`user`='{$user}'";
		$result= Query($query);
		if (count($result[0])==0)	sendMessage($chat,'Have no any task from you. There is nothing to cancel');
		else
		{
			//Delete the task
			if(!empty($result[0][1])) unlink($result[0][1]);
			if(!empty($result[0][2])) unlink($result[0][2]);
			$query="DELETE FROM `trans` WHERE `user`='{$user}'";
			$result= Query($query);
			sendMessage($chat,'Task canceled');
		}
	}
	if($message=='/status') 
	{
		//check for existing queries
		$query="SELECT 
			`user`, 
			`content`, 
			`style`,
			`required`
			FROM 
			`trans` 
			WHERE 
			`user`='{$user}'";
		$result= Query($query);
		if (count($result[0])==0) sendMessage($chat,'There is '.getQueue($user,true).' queries in queue. Have no any task from you');
		else sendMessage($chat,'Your task is '.getQueue($user).'-th in queue');
	}
}
?>
