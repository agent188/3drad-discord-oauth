<?php
include('libs/redbean/db.php');
$apiURLBase = 'https://discord.com/api/users/@me';
switch ($_GET['act']) {
    case "live":
        $player = R::getAll("SELECT * FROM players WHERE token = ?", [$_GET['token']]);
        if(!isset($player[0]['id'])) {
            echo 0;
            exit;
        }
        if($player[0]['status'] == 2) { // banned
            echo 2;
            exit;
        }
        $discordCheck = apiRequest($apiURLBase, false, [
            'Authorization: Bearer ' . $player[0]['discordtoken']
        ]);
        if(!isset($discordCheck->username)) {
            echo 0;
            exit;
        }
        R::exec("UPDATE players SET lastOnline = CURRENT_TIMESTAMP(), online = 1, email = :email, ip = :ip, nick = :nick, avatar = :avatar WHERE discord = :discord", [
                'nick' => $discordCheck->username,
                'discord' => $discordCheck->id,
                'email' => $discordCheck->email,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'avatar' => "https://cdn.discordapp.com/avatars/{$discordCheck->id}/{$discordCheck->avatar}.png" 
        ]);
        echo 1;
        break;
    case "nick":
        $player = R::getAll("SELECT * FROM players WHERE token = ?", [$_GET['token']]);
        if(!isset($player[0]['id'])) {
            exit;
        }
        echo $player[0]['nick'];
        break;
    case "avatar":
        $player = R::getAll("SELECT * FROM players WHERE token = ?", [$_GET['token']]);
        if(!isset($player[0]['id'])) {
            exit;
        }
        header("Content-type: image");
        echo file_get_contents($player[0]['avatar']);
        break;
    case "banreason":
        $player = R::getAll("SELECT * FROM players WHERE token = ?", [$_GET['token']]);
        if(isset($player[0]['id']) && $player[0]['status'] == 2) {
            echo R::getAll("SELECT * FROM bans WHERE id = ?", [$player[0]['id']])[0]['reason'];
            exit;
        }
        break;
}

function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $response = curl_exec($ch);


  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

  $headers[] = 'Accept: application/json';

  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);
  return json_decode($response);
}
