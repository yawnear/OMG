<?php
header('Content-type:text/json,charset="UTF-8"');

require_once './include/db_conn.php';
require_once './include/comm.php';
require_once './include/device.php';
// 问卷版本检查
// $ver = $answer["version"];
// $cfg_analysis = "survey/$ver/analysis.json";
// if (file_exists($cfg_analysis)) {
// 	
// } else {
// 	showJSON(30002,'当前版本不支持！');
// }
$analysis = json_decode(file_get_contents("detection/analysis.json"), JSON_UNESCAPED_UNICODE);
function get_ana($part){
	global $analysis;
	return $analysis[$part];
}
// var_dump($analysis);
// print_r(get_ana('肩部'));
// die();
function estimate(array $data, array $device){

	$solution = array();
	$status = array();
	$omp = array();
	for($i=0;$i<count($data);$i++){
		$item = str_ireplace("检测结果", "", "".$data[$i]["monitoringObj"]);
		$status[$item] = hexdec($data[$i]["displayMonDataContent"]);
		if($item == "ache"){
			$status[$item] = $data[$i]["dtl"];
			$ache = decode_ache(hexdec($data[$i]["displayMonDataContent"]));
		}
	}
	// var_dump($ache);
	// die();
	// 血氧参数
	$value = $status["bloodoxygen"];
	if($value >= 90 and $value <= 95){
		array_push($omp, $device["活血循环"]);
	}
	else{
		array_push($omp, $device[array_rand($device,1)]);
	}
	// 心率参数
	$value = $status["heartrate"];
	if($value >= 81 and $value <= 100){
		array_push($omp, $device["巴厘式"]);
	}
	else{
		array_push($omp, $device[array_rand($device,1)]);
	}
	// 疲劳指数（并重计分）
	$value = $status["fatigue"];
	switch ($value)
	{
		case 0:
			array_push($omp, $device[array_rand($device,1)]);
			$status["fatigue"] = rand(0, 20);
			break;  
		case 1:
			array_push($omp, $device["元气复苏"]);
			$status["fatigue"] = rand(21, 40);
			break;  
		case 2:
			array_push($omp, $device["大师精选"]);
			$status["fatigue"] = rand(41, 70);
			break;  
		case 3:
			array_push($omp, $device["运动派"]);
			$status["fatigue"] = rand(71, 90);
			break;  
		default:
			array_push($omp, $device[array_rand($device,1)]);
	}
	// 酸痛
	if(isset($status["ache"])){
		$value = $status["ache"];
		switch ($value)
		{
			case '轻度':
				array_push($omp, $device[array_rand($device,1)]);
				break;  
			case '中度':
				array_push($omp, $device["元气复苏"]);
				break;  
			case '重度':
				array_push($omp, $device["瞬间补眠"]);
				break;  
			default:
				array_push($omp, $device[array_rand($device,1)]);
		}
	}
	// 压力指数
	if(isset($status["pressure"])){
		$value = $status["pressure"];
		switch ($value)
		{
			case '轻度':
				array_push($omp, $device[array_rand($device,1)]);
				break;  
			case '中度':
				array_push($omp, $device["元气复苏"]);
				break;  
			case '重度':
				array_push($omp, $device["瞬间补眠"]);
				break;  
			default:
				array_push($omp, $device[array_rand($device,1)]);
		}
	}

	// 疲劳 + 综述输出
	$score_detail = array();
	$ana_result = array();
	$score_detail["基础分"] = rand(0,15);
	foreach($ache as $key=>$val){
		$score_detail[$key] = score($val);
		// 中度以上输出分析
		if($val > 1){
			$ana_result[] = array_merge(array("part"=>$key),get_ana($key));
		}

	}
	$score = array_sum($score_detail);

	$solution["status"] = $status;
	$solution["ache"] = $ache;
	$solution["score"] = $score;
	$solution["score_detail"] = $score_detail;
	$solution["omp"] = $omp;
	$solution["analysis"] = $ana_result;
	$solution["score_detail"] = $score_detail;

	return $solution;
}

function decode_ache($dec){
	$var=sprintf("%08b", $dec);//生成8位数，不足前面补0
	$result = array(
		"颈部"=>bindec(substr($var,0,2)),
		"肩部"=>bindec(substr($var,2,2)),
		"背部"=>bindec(substr($var,4,2)),
		"腰部"=>bindec(substr($var,6,2))
	);
	return $result;
}

function score($level){
	switch ($level){
		case 1:
			$result = 0;
			break;  
		case 2:
			$result = 10;
			break;  
		case 3:
			$result = 20;
			break;  
		default:
			$result = 0;
	}
	return $result;
}

// $answer = (Object)json_decode(_post("answer"), JSON_UNESCAPED_UNICODE);
$answer = json_decode(_post("answer"), JSON_UNESCAPED_UNICODE);
$result = $answer["result"];
$result = estimate($result, $omp_528_WIFI);
// 返回用户信息
$result["openid"] = $answer["openid"];
$result["transactionId"] = $answer["transactionId"];
// 原表单数据回显
// $result["answer"] = $answer;

showJSON(10000, 'success', $result);
?>