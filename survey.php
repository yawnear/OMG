
<?php
// header("content-type:text/html;charset=utf-8");
header('Content-type:text/json,charset="UTF-8"');

require_once './include/db_conn.php';
require_once './include/comm.php';
require_once './include/device.php';

// $answer = (Object)json_decode(_post("answer"), JSON_UNESCAPED_UNICODE);
$answer = json_decode(_post("answer"), JSON_UNESCAPED_UNICODE);
// print_r($answer);
// echo("<hr>");
// 问卷版本检查
$ver = $answer["version"];
$cfg_analysis = "survey/$ver/analysis.json";
if (file_exists($cfg_analysis)) {
	$analysis = json_decode(file_get_contents($cfg_analysis), JSON_UNESCAPED_UNICODE);
} else {
	showJSON(30002,'当前版本不支持！');
}

$omp = array();
$report = array();
// 左肩、右肩、颈部、背部、腰部、臀部
$body = array(
	"head"=>"头部",
	"neck"=>"颈部",
	"shoulder"=>"肩部",
	"left_shoulder"=>"左肩",
	"right_shoulder"=>"右肩",
	"back"=>"背部",
	"waist"=>"腰部",
	"hip"=>"臀部"
);
// 部位对应手法
$solution = array(
	"head"=>"低头族",
	"neck"=>"轻松自在",
	"shoulder"=>"轻松自在",
	"back"=>"深层按摩",
	"waist"=>"深层按摩",
	"neck+shoulder"=>"中式",
	"back+waist"=>"绽放魅力"
);

// 用户信息
$userinfo = $answer["userinfo"];
// 部位症状
foreach ($answer["bodypart"] as $key => $val)
if($val){
	$bodyparts[] = $key;
	$bodyparts_name[] = $body[$key];
}

sort($bodyparts);
$opt = "".implode("+",$bodyparts);
if(array_key_exists($opt, $solution)){
	$omp = $solution[$opt];
}
else{
	$omp = "夜晚助眠";
}
$opt = implode("+",$bodyparts_name);

$report = "问题部位:".$opt;
$data = array(
	"openid"=>$answer["openid"],
	"transactionId"=>$answer["transactionId"],
	"program"=>$omp_528_WIFI[$omp],
	"report"=>$report
);

// TODO:上传手法库
// 用户ID + 手法名称 + 手法	
date_default_timezone_set('PRC');
$userid = $data["openid"];
$transactionid = $data["transactionId"];
$program = $data["program"];
$rst = $pdo->prepare('INSERT INTO omp_survey (userid, transactionid, program, report) values (?, ?, ?, ?)');
// $rst->bind_param($userid, $ompname, json_encode($result, JSON_UNESCAPED_UNICODE)));
$rst->execute(array($userid, $transactionid, json_encode($program, JSON_UNESCAPED_UNICODE), $report));
$pdo=null; //断开连接

// 输出手法
// var_dump($data);
showJSON(10000, 'success', $data);
?>
