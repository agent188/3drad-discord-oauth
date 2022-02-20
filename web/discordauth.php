<?php
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}
include('libs/redbean/db.php');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// ini_set('max_execution_time', 300);

// error_reporting(E_ALL);

define('OAUTH2_CLIENT_ID', 'id');
define('OAUTH2_CLIENT_SECRET', 'secret');

$authorizeURL = 'https://discord.com/api/oauth2/authorize';
$tokenURL = 'https://discord.com/api/oauth2/token';
$apiURLBase = 'https://discord.com/api/users/@me';


if ($_GET['act'] == "new") {
    $state = generateRandomString(24);
    R::exec("INSERT INTO states (state, ip) VALUES (:state, :ip)", [
        'state' => $state,
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);
    echo $state;
    exit();
}

if($_GET['code']) {
  $token = apiRequest($tokenURL, array(
    "grant_type" => "authorization_code",
    'client_id' => OAUTH2_CLIENT_ID,
    'client_secret' => OAUTH2_CLIENT_SECRET,
    'redirect_uri' => 'http://192.168.0.29/discordauth.php',
    'code' => $_GET['code']
  ));
  $userToken = $token->access_token;
  $user = apiRequest($apiURLBase, false, [
    'Authorization: Bearer ' . $userToken
  ]);
  $username = $user->username;
  if(!isset($username)) {
    http_response_code(500);
    die("fail auth");
  }
  $states = R::getAll("SELECT * FROM states WHERE state = ?", [$_GET['state']]);
  if(!isset($states[0]['state'])) {
    http_response_code(404);
    die("not found state");
  } else {
    R::exec("DELETE FROM states WHERE state = ?", [$_GET['state']]);
  }
  $userId = $user->id;
  $userEmail = $user->email;
  $ip = $_SERVER['REMOTE_ADDR'];
  $players = R::getAll("SELECT * FROM players WHERE discord = ?", [$userId]);
  if(!isset($players[0]['id'])) {
    R::exec("INSERT INTO players (`nick`, `discord`, `token`, `email`, `ip`, `discordtoken`, `avatar`) VALUES (:nick, :discord, :token, :email, :ip, :discordtoken, :avatar)", [
        'nick' => $username,
        'discord' => $userId,
        'token' => $_GET['state'], 
        'email' => $userEmail,
        'ip' => $ip,
        'discordtoken' => $token->access_token,
        'avatar' => "https://cdn.discordapp.com/avatars/{$user->id}/{$user->avatar}.png" 
    ]);
  } else {
    if($players[0]['status'] == 2) {
        http_response_code(403);
        die("You are banned. Reason: " . R::getAll("SELECT reason FROM bans WHERE id = ?", [$players[0]['id']])[0]['reason']);
    }
    R::exec("UPDATE players SET token = :token, lastOnline = CURRENT_TIMESTAMP(), online = 1, email = :email, ip = :ip, nick = :nick, discordtoken = :discordtoken, avatar = :avatar WHERE discord = :discord", [
        'nick' => $username,
        'discord' => $userId,
        'token' => $_GET['state'],
        'email' => $userEmail,
        'ip' => $ip,
        'discordtoken' => $token->access_token,
        'avatar' => "https://cdn.discordapp.com/avatars/{$user->id}/{$user->avatar}.png" 
    ]);
  }
  echo "success auth, you can close this page";
  exit();
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

echo "nothing";

?>
