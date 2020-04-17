<?php
// header('Content-type:text/json,charset="UTF-8"');

// select from user_info
/*

*/


$userinfo = array(
	"username"=>"用户名",
	"sex"=>"1",/*1男2女*/
	"age"=>"35",
	"weight"=>"52",
	"height"=>"168",
	"avg_hb"=>"70"
);
// echo($userinfo.toString());
echo(json_encode($userinfo, JSON_UNESCAPED_UNICODE));
?>