<?php
unlink($_GET['content']);
unlink($_GET['style']);
$token='pasteTokenThere';
$botUrl = "https://api.telegram.org/bot".$token."/sendPhoto?chat_id=".$_GET['user'];
$resultUrl='scriptlab.hopto.org/tensorflow-style-transfer/'.$_GET['result'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
curl_setopt($ch, CURLOPT_URL, $botUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, array("photo" => "@".$resultUrl,));
$output = curl_exec($ch);
print $output;
file_get_contents("http://scriptlab.net/telegram/bots/relaybot/relayPhoto.php?user=logsGroupid&photoUrl=$resultUrl");
?>
