<?php
error_reporting( E_ALL&~E_NOTICE );
header('Content-type:text/json,charset="UTF-8"');
require_once './include/comm.php';
require_once './include/device.php';

if(isset($_POST["userid"])){
	$debug = 1;
}
else{
	$debug = 0;
}
// 用户请求数据
if(isset($_POST["userid"])){
$userid = $_POST["userid"];
//"o7dNp0_O1Txb-aRr7nHS-hhyASVE",
$transactionId = $_POST["transactionId"];
// "000001"
}
else{
	echo(json_encode(array("status_code"=>"-1","message"=>"用户数据错误"), JSON_UNESCAPED_UNICODE));
	die();
}

// 装配
$data = array(
	"userid"=>$userid,
	"transactionId"=>$transactionId
);

// 获取用户信息
$url_base = get_url_base();
// 安全码
$safecode = '85036D28F87DF7CE8DB2DAF5B175FE8F';
$param = array(
	'userid' => $userid,
	'safecode' => $safecode
);
$data["userinfo"] = json_decode(curl_post($url_base."get_userinfo.php", $param));

// 基础数据部分
/*
字段	说明
person_num	人体数目
person_info[]	人体姿态信息
body_parts	身体部位信息，包含14个关键点
left_ankle	左脚踝
left_elbow	左手肘
left_hip	左髋部
left_knee	左膝
left_shoulder	左肩
left_wrist	左手腕
neck	颈
nose	鼻子
right_ankle	右脚踝
right_elbow	右手肘
right_hip	右髋部
right_knee	右膝
right_shoulder	右肩
right_wrist	右手腕
location	人体坐标信息
height	人体区域的高度
left	人体区域离左边界的距离
top	人体区域离上边界的距离
width	人体区域的宽度
log_id	唯一的log id，用于问题定位
*/
// $data["pic"] = json_decode(file_get_contents('data/posture_pic.json'), JSON_UNESCAPED_UNICODE);
// $data["front"] = json_decode(file_get_contents('data/posture_front.json'), JSON_UNESCAPED_UNICODE);
// $data["side"] = json_decode(file_get_contents('data/posture_side.json'), JSON_UNESCAPED_UNICODE);
$data_front = $_POST["body_front"];
$data_side = $_POST["body_side"];

// TODO : 指标参考
function fn_reference($item)
{
	$data_ref = array(
		"头部侧倾"=>rand(1, 3),
		"高低肩"=>rand(1, 3),
		"脊柱异位"=>rand(1, 3),
		"XO型腿"=>rand(1, 3),
		"X型腿"=>rand(1, 3),
		"O型腿"=>rand(1, 3),
		"头部前倾"=>rand(1, 3),
		"圆肩"=>rand(1, 3),
		"骨盆前倾"=>rand(1, 3),
		"膝过伸"=>rand(1, 3)
	);
	return array("reference"=>$data_ref[$item]);
}

function fn_reference_old($item)
{
	$data_ref = array(
		"头部侧倾"=>rand(60, 80),
		"高低肩"=>rand(60, 80),
		"脊柱异位"=>rand(60, 80),
		"XO型腿"=>rand(60, 80),
		"X型腿"=>rand(60, 80),
		"O型腿"=>rand(60, 80),
		"头部前倾"=>rand(60, 80),
		"圆肩"=>rand(60, 80),
		"骨盆前倾"=>rand(60, 80),
		"膝过伸"=>rand(60, 80)
	);
	return array("reference"=>$data_ref[$item]);
}

// 位置标准化
function fn_point($point){
	// 取整/数值化。。。
	return array(
		"x"=>$point["x"],
		"y"=>$point["y"]
	);
}

function fn_cov_point($point){
	// 格式化
	return array(
		"x"=>$point[0],
		"y"=>$point[1]
	);
}

// 位置翻转
function fn_point_trans($point){
	return array(
		"x"=>$point["y"],
		"y"=>$point["x"]
	);
}

function fn_line(string $label, array $point_start, array $point_end){
	if(!is_array($point_start)){
		echo("起点设置错误");
	}
	if(!is_array($point_end)){
		echo("终点设置错误");
	}
	// 斜率->角度转换
	if($point_end["x"] != $point_start["x"]){
		$angle = rad2deg(atan(($point_end["y"] - $point_start["y"]) / ($point_end["x"] - $point_start["x"])));		
		if($angle < 0){
			$angle = $angle + 180;
		}
	}
	else{
		$angle = 90;
	}
	$line = array(
		"label"=>$label,
		"point_start"=>$point_start,
		"point_end"=>$point_end,
		"angle"=>$angle,
		"point_start"=>$point_start,
	);
	return $line;
}

// 获取身体正面关键点坐标
$part_front = json_decode($data_front, true)["person_info"][0]["body_parts"];
// 正面脚踝中点
$fp_middle_ankle = array(
	"x"=>($part_front["left_ankle"]["x"] + $part_front["right_ankle"]["x"]) / 2,
	"y"=>($part_front["left_ankle"]["y"] + $part_front["right_ankle"]["y"]) / 2
);
// 身体正面垂直中心线，简版，待改进
$fln_vertical = fn_line("身体中垂线", $part_front["neck"], $fp_middle_ankle);
// 身体正面水平线，简版，待改进
$fln_horizontal = fn_line("身体水平线", fn_point_trans($part_front["neck"]), fn_point_trans($fp_middle_ankle));
// var_dump($fln_vertical);
// var_dump($fln_horizontal);
// $xln_test = fn_line("test", array("x"=>5,"y"=>1), array("x"=>0, "y"=>1.1));
// var_dump($xln_test);
// $xln_test = fn_line("test", array("x"=>5,"y"=>1), array("x"=>0, "y"=>0.5));
// var_dump($xln_test);
// die();
$fln_shoulder = fn_line("肩水平线", $part_front["left_shoulder"], $part_front["right_shoulder"]);
$fln_nose2neck = fn_line("鼻颈线", $part_front["nose"], $part_front["neck"]);
$fln_LeftThigh = fn_line("左侧大腿", $part_front["nose"], $part_front["neck"]);
$fln_RightThigh = fn_line("右侧大腿", $part_front["nose"], $part_front["neck"]);
$fln_LeftShank = fn_line("左侧小腿", $part_front["nose"], $part_front["neck"]);
$fln_RightShank = fn_line("右侧小腿", $part_front["nose"], $part_front["neck"]);
$fln_hip = fn_line("髋关节左右连线", $part_front["left_hip"], $part_front["right_hip"]);

// 获取身体侧面关键点坐标
$part_side = json_decode($data_side, true)["person_info"][0]["body_parts"];
// 侧面脚踝中点
$sp_middle_ankle = array(
	"x"=>($part_side["left_ankle"]["x"] + $part_side["right_ankle"]["x"]) / 2,
	"y"=>($part_side["left_ankle"]["y"] + $part_side["right_ankle"]["y"]) / 2
);
// 侧面肩中点
$sp_middle_shoulder = array(
	"x"=>($part_side["left_shoulder"]["x"] + $part_side["right_shoulder"]["x"]) / 2,
	"y"=>($part_side["left_shoulder"]["y"] + $part_side["right_shoulder"]["y"]) / 2
);
// 侧面髋中点
$sp_middle_hip = array(
	"x"=>($part_side["left_hip"]["x"] + $part_side["right_hip"]["x"]) / 2,
	"y"=>($part_side["left_hip"]["y"] + $part_side["right_hip"]["y"]) / 2
);
// 侧面膝中点
$sp_middle_knee = array(
	"x"=>($part_side["left_knee"]["x"] + $part_side["right_knee"]["x"]) / 2,
	"y"=>($part_side["left_knee"]["y"] + $part_side["right_knee"]["y"]) / 2
);
// 身体侧面垂线，简版，待改进：脚踝与肩中线
$sln_vertical = fn_line("身体侧面垂线", $sp_middle_shoulder, $sp_middle_ankle);
$sln_Nose2Ankle = fn_line("鼻踝线", $part_side["nose"], $sp_middle_ankle);
$sln_Neck2Ankle = fn_line("颈踝线", $part_side["neck"], $sp_middle_ankle);
$sln_Hip2Ankle = fn_line("髋踝线", $sp_middle_hip, $sp_middle_ankle);
$sln_Knee2Ankle = fn_line("膝踝线", $sp_middle_knee, $sp_middle_ankle);

// 测量 -----------------------------------------------------------------------
function fn_measure(array $partline, array $baseline, $dir){
	/**
	 * $partline： 计算线
	 * $baseline： 基准线
	 * $dir     ： 1-正面水平，2-正面垂直，3-左侧垂直，4-右侧垂直
	 */
	switch ($dir) {
		case 1:
			$arr_dir = array("左高","右高","");
			break;
		case 2:
			$arr_dir = array("偏右","偏左","");
			break;
		case 3:
			$arr_dir = array("后倾","前倾","");
			break;
		case 4:
			$arr_dir = array("前倾","后倾","");
			break;
		default:
			$arr_dir = array("左","右","");
			break;
	}
	$gap_angle = $partline["angle"] - $baseline["angle"];
	if($gap_angle > 0){
		$idx = 0;
	}
	else if($gap_angle < 0){
		$idx = 1;
	}
	else if($gap_angle = 0){
		$idx = 2;
	}
	// 去符号判断
	$gap_angle = abs($gap_angle);
	if($gap_angle > 90){
		$gap_angle = 180 - $gap_angle;
	}
	if($gap_angle <> 0){
		$gap_angle = round($gap_angle, 1);
		$level = array('轻度','中度','重度');
		$score = array(1, 2, 3);
		$checkpoint = array(5, 10);
		// echo("<hr>".count($level));
		for($i=0;$i<count($checkpoint);$i++){
			if($gap_angle<$checkpoint[$i]){
				break;
			}
		}
		$exp = "$i.角度：$gap_angle - 程度：".$level[$i];
		$rst = array("result"=>$score[$i],"issueside"=>$arr_dir[$idx],"angle"=>$gap_angle,"level"=>$level[$i]);
	}
	else{
		$rst = array("result"=>"0","angle"=>$gap_angle,"level"=>"无");
	}
	return $rst;
}

function fn_measure_old(array $partline, array $baseline, $abs){
	/**
	 * $partline： 计算线
	 * $baseline： 基准线
	 * 
	 */
	if($abs){
		$gap_angle = abs($partline["angle"] - $baseline["angle"]);
	}
	else{
		$gap_angle = $partline["angle"] - $baseline["angle"];
	}
	// 有方向性判断，符合则输出症状问题
	if($gap_angle >= 0){
		$gap_angle = round($gap_angle, 1);
		$level = array('良好','正常','轻度异常','中度异常','严重异常');
		$score = array(80, 70, 60, 50, 40);
		$checkpoint = array(1, 2, 5, 10);
		// echo("<hr>".count($level));
		for($i=0;$i<count($checkpoint);$i++){
			if($gap_angle<$checkpoint[$i]){
				break;
			}
		}
		$exp = "$i.角度：$gap_angle - 程度：".$level[$i];
		$rst = array("result"=>$score[$i],"angle"=>$gap_angle,"level"=>$level[$i]);
	}
	else{
		$rst = array("result"=>"0","angle"=>$gap_angle,"level"=>"无");
	}
	return $rst;
}

// TODO : 症状分析
// 高低肩	正面观
function fn_ShoulderHL($partline, $baseline){
	$gap_angle = abs($partline["angle"] - $baseline["angle"]);
	$gap_angle = round($gap_angle, 1);
	// echo("<hr>".$partline["angle"]);
	// echo("<hr>".$baseline["angle"]);
	// echo("<hr>".$gap_angle);
	// 程度分级：良好，正常，异常，严重
	$level = array('良好','正常','轻度异常','中度异常','严重异常');
	$score = array(80, 70, 60, 50, 40);
	$checkpoint = array(1, 2, 5, 10);
	// echo("<hr>".count($level));
	for($i=0;$i<count($checkpoint);$i++){
		if($gap_angle<$checkpoint[$i]){
			break;
		}
	}
	$exp = "$i.角度：$gap_angle - 程度：".$level[$i];
	$rst = array("result"=>$score[$i],"angle"=>$gap_angle,"level"=>$level[$i]);
	return $rst;
}
// 头部侧倾	正面观
function fn_HeadHeel($partline, $baseline){
	$gap_angle = abs($partline["angle"] - $baseline["angle"]);
	$gap_angle = round($gap_angle, 1);
	$level = array('良好','正常','轻度异常','中度异常','严重异常');
	$score = array(80, 70, 60, 50, 40);
	$checkpoint = array(1, 2, 5, 10);
	// echo("<hr>".count($level));
	for($i=0;$i<count($checkpoint);$i++){
		if($gap_angle<$checkpoint[$i]){
			break;
		}
	}
	$exp = "$i.角度：$gap_angle - 程度：".$level[$i];
	$rst = array("result"=>$score[$i],"angle"=>$gap_angle,"level"=>$level[$i]);
	return $rst;
}

// 调试用
function fn_test_measure(array $partline, array $baseline, $dir){
	$rst = array("result"=>3,"angle"=>15,"level"=>"严重");
	return $rst;
}


// TODO : 分析结果存入
$data_ana = array();
$item = array();
// 头部侧倾	正面观
$item = array("direction"=>"正面", "item"=>"头部侧倾");
array_push($data_ana, array_merge($item, fn_measure($fln_nose2neck, $fln_vertical, 2), fn_reference("头部侧倾")));
// 高低肩	正面观
$item = array("direction"=>"正面", "item"=>"高低肩");
array_push($data_ana, array_merge($item, fn_measure($fln_shoulder, $fln_horizontal, 1), fn_reference("高低肩")));
// 脊柱异位	正面观
$item = array("direction"=>"正面", "item"=>"脊柱异位");
array_push($data_ana, array_merge($item, fn_measure($fln_hip, $fln_horizontal, 2), fn_reference("脊柱异位")));
// // XO型腿	正面观（当前数据集无法判断）
// $item = array("direction"=>"正面", "item"=>"XO型腿");
// array_push($data_ana, array_merge($item, fn_measure($fln_RightThigh, $fln_RightShank, 2), fn_reference("XO型腿")));
// X型腿	正面观
$item = array("direction"=>"正面", "item"=>"X型腿");
array_push($data_ana, array_merge($item, fn_measure($fln_RightShank, $fln_RightThigh, 2), fn_reference("X型腿")));
// O型腿	正面观
$item = array("direction"=>"正面", "item"=>"O型腿");
array_push($data_ana, array_merge($item, fn_measure($fln_RightThigh, $fln_RightShank, 2), fn_reference("O型腿")));
// 头部前倾	侧面观
$item = array("direction"=>"侧面", "item"=>"头部前倾");
array_push($data_ana, array_merge($item, fn_measure($sln_Nose2Ankle, $sln_vertical, 4), fn_reference("头部前倾")));
// 圆肩	侧面观
$item = array("direction"=>"侧面", "item"=>"圆肩");
array_push($data_ana, array_merge($item, fn_measure($sln_vertical, $sln_Neck2Ankle, 3), fn_reference("圆肩")));
// 骨盆前倾	侧面观
$item = array("direction"=>"侧面", "item"=>"骨盆前倾");
array_push($data_ana, array_merge($item, fn_measure($sln_Hip2Ankle, $sln_vertical, 3), fn_reference("骨盆前倾")));
// 膝过伸	侧面观
$item = array("direction"=>"侧面", "item"=>"膝过伸");
array_push($data_ana, array_merge($item, fn_measure($sln_vertical, $sln_Knee2Ankle, 3), fn_reference("膝过伸")));

// 528固定程序清单（来源：528蓝牙通讯协议v1.8_180505.docx）
$omp_528_BT = array(
	"大师精选"=>array("code"=>7, "name"=>"大师精选", "type"=>"专属", "order"=>1),
	"轻松自在"=>array("code"=>8, "name"=>"轻松自在", "type"=>"专属", "order"=>2),
	"关节呵护"=>array("code"=>9, "name"=>"关节呵护", "type"=>"专属", "order"=>3),
	"脊柱支持"=>array("code"=>10, "name"=>"脊柱支持", "type"=>"专属", "order"=>4),
	"上班族"=>array("code"=>11, "name"=>"上班族", "type"=>"主题", "order"=>1),
	"低头族"=>array("code"=>12, "name"=>"低头族", "type"=>"主题", "order"=>2),
	"驾车族"=>array("code"=>13, "name"=>"驾车族", "type"=>"主题", "order"=>3),
	"运动派"=>array("code"=>14, "name"=>"运动派", "type"=>"主题", "order"=>4),
	"御宅派"=>array("code"=>15, "name"=>"御宅派", "type"=>"主题", "order"=>5),
	"爱购派"=>array("code"=>16, "name"=>"爱购派", "type"=>"主题", "order"=>6),
	"巴黎式"=>array("code"=>17, "name"=>"巴黎式", "type"=>"区域", "order"=>1),
	"中式"=>array("code"=>18, "name"=>"中式", "type"=>"区域", "order"=>2),
	"泰式"=>array("code"=>19, "name"=>"泰式", "type"=>"区域", "order"=>3),
	"深层按摩"=>array("code"=>20, "name"=>"深层按摩", "type"=>"女士", "order"=>1),
	"活血循环"=>array("code"=>21, "name"=>"活血循环", "type"=>"女士", "order"=>2),
	"活力唤醒"=>array("code"=>22, "name"=>"活力唤醒", "type"=>"女士", "order"=>3),
	"美臀塑性"=>array("code"=>23, "name"=>"美臀塑性", "type"=>"女士", "order"=>4),
	"元气复苏"=>array("code"=>24, "name"=>"元气复苏", "type"=>"女士", "order"=>5),
	"绽放魅力"=>array("code"=>25, "name"=>"绽放魅力", "type"=>"女士", "order"=>6),
	"清晨唤醒"=>array("code"=>26, "name"=>"清晨唤醒", "type"=>"场景", "order"=>1),
	"瞬间补眠"=>array("code"=>27, "name"=>"瞬间补眠", "type"=>"场景", "order"=>2),
	"夜晚助眠"=>array("code"=>28, "name"=>"夜晚助眠", "type"=>"场景", "order"=>3)
);

// 症状对应手法
function fn_program($issue){
	// 引入手法库
	global $omp_528_WIFI;
	$device = $omp_528_WIFI;
	$omp = array(
		"头部前倾"=>"清晨唤醒",
		"头部侧倾"=>"清晨唤醒",
		"XO型腿"=>"关节呵护",
		"X型腿"=>"关节呵护",
		"O型腿"=>"关节呵护",
		"膝过伸"=>"关节呵护",
		"骨盆前倾"=>"脊柱支持",
		"脊柱异位"=>"泰式",
		"圆肩"=>"驾车族",
		"高低肩"=>"驾车族"
	);
	if(isset($omp[$issue])){
		return array("issue"=>$omp[$issue],$device[$omp[$issue]]);
	}
	else{
		return array("issue"=>$issue,array_rand($device));
	}
}

function fn_program_old($issue){
	global $omp_528;
	switch($issue){
		case "头部前倾": return $omp_528["清晨唤醒"];
		case "头部侧倾": return $omp_528["清晨唤醒"];
		case "XO型腿": return $omp_528["关节呵护"];
		case "X型腿": return $omp_528["关节呵护"];
		case "O型腿": return $omp_528["关节呵护"];
		case "膝过伸": return $omp_528["关节呵护"];
		case "骨盆前倾": return $omp_528["脊柱支持"];
		case "脊柱异位": return $omp_528["泰式"];
		case "圆肩": return $omp_528["驾车族"];
		case "高低肩": return $omp_528["驾车族"];
		default: return null;
	}
}
// 症状对应风险
function fn_risk($issue){
	switch($issue){
		case "头部侧倾": return "颈椎病、头晕头痛";
		case "头部前倾": return "颈椎病、头晕头痛";
		case "XO型腿": return "膝关节病变";
		case "膝过伸": return "膝关节病变";
		case "X型腿": return "膝关节病变";
		case "O型腿": return "膝关节病变";
		case "骨盆侧倾": return "脊柱侧弯、椎间盘突出、腰痛";
		case "脊柱异位": return "脊柱侧弯、椎间盘突出、腰痛";
		case "圆肩": return "颈肩疼痛、胸椎病变";
		case "高低肩": return "颈肩疼痛、脊柱侧弯、椎间盘突出";
		default: return null;
	}
}

// TODO : 分析结果
function fn_diagnose($data){
	// 诊断结论
	$Lvl3 = array();
	$Risk3 = array();
	$Lvl2 = array();
	$Risk2 = array();
	// 问题对应手法
	$omp = array();
	// 问题数量
	$cnt = 0;
	// 低于参考值得分则输出
	for($i=0;$i<count($data);$i++){
		if($data[$i]["result"]==3){
			$Lvl3[] = $data[$i]["item"];
			$Risk3[] = fn_risk($data[$i]["item"]);
		}
		else if($data[$i]["result"]==2){
			$Lvl2[] = $data[$i]["item"];
			$Risk2[] = fn_risk($data[$i]["item"]);
		}
		$cnt++;
	}
	if(count($Lvl3) > 0){
		$str = "本次检测中，您的".implode("、", $Lvl3)."风险高于全国平均水平。";
		$risk = implode("、", array_unique($Risk3));
		foreach($Lvl3 as $val){
			$omp[] = array("item"=>$val, "result"=>3, "program"=>fn_program($val));
		}
	}
	else if(count($Lvl2) > 0){
		$str = "本次检测中，您的 ".implode("、", $Lvl2)."风险接近全国平均水平。";
		$risk = implode("、", array_unique($Risk2));
		foreach($Lvl2 as $val){
			$omp[] = array("item"=>$val, "result"=>3, "program"=>fn_program($val));
		}
	}
	else{
		$str = "本次检测中，您的整体体态很好，请继续保持！";
	}

	return array("result"=>$str,"risk"=>"$risk","program"=>$omp);
}

function fn_diagnose_old($data){
	// 诊断结论
	$str = "";
	// 问题对应手法
	$omp = array();
	// 问题数量
	$cnt = 0;
	// 低于参考值得分则输出
	for($i=0;$i<count($data);$i++){
		if($data[$i]["result"]<$data[$i]["reference"]){
			// echo("<font color:\"red\">".$data[$i]["item"]."</font>");
			$str = $str."\n".$data[$i]["item"].$data[$i]["angle"]."度";
			$omp[] = array("item"=>$data[$i]["item"],"result"=>$data[$i]["result"], "program"=>fn_program_old($data[$i]["item"]));
			$cnt++;
		}
	}
	if($cnt > 0){
		$str = "共有 $cnt 项异常".$str;
	}
	else{
		$str = "恭喜，您的体型非常好！";
	}

	// 按严重程度排序
	foreach ($omp as $key => $row)
	{
		$result[$key]  = $row['result'];
		$program[$key] = $row['program'];
	}	
	array_multisort($result, SORT_ASC, $program, SORT_DESC, $omp);
	return array("result"=>$str,"program"=>$omp);
}



// 报告数据部分
$diagnose = fn_diagnose($data_ana);

$data["analysis"] = array(
	"chart"=>$data_ana,
	"result"=>$diagnose["result"],
	"risk"=>$diagnose["risk"]
);
// $data["analysis"] = json_decode(file_get_contents('analysis.json'), JSON_UNESCAPED_UNICODE);


// 手法数据部分
$data_program = array();
// $data["programx"] = $diagnose["program"];
// $data["program-count"] = count($diagnose["program"]);
for($i=0;$i<count($diagnose["program"])&$i<3;$i++){
	$data_program[] = array("name"=>"方案$i","program"=>$diagnose["program"][$i]["program"]);
}
// 随机补足3个方案
while($i<3){
	$data_program[] = array("name"=>"方案$i","program"=>$omp_528_WIFI[array_rand($omp_528_WIFI)]);
	$i++;
}


// // 方案1
// array_push($data_program, array("time"=>"8分钟", "program"=>"按摩程序名称或者指令集"));
// // 方案2
// array_push($data_program, array("time"=>"15分钟", "program"=>"按摩程序名称或者指令集"));
// // 方案3
// array_push($data_program, array("time"=>"20分钟", "program"=>"按摩程序名称或者指令集"));
$data["programs"] = $data_program;

// 输出
echo(json_encode($data, JSON_UNESCAPED_UNICODE));
?>