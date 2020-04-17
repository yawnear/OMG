<?php
function curl_post($url, $post){
	$options = array(
		CURLOPT_RETURNTRANSFER =>true,
		CURLOPT_HEADER =>false,
		CURLOPT_POST =>true,
		CURLOPT_POSTFIELDS => $post,
	);
	try{
		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	} catch (Exception $e) {
		return '提交失败'.$e;
	}
}

function get_url_base(){
	$script = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$url_base = "http://".$_SERVER["HTTP_HOST"].substr($script, 0, strrpos($script, '/') + 1);
	return $url_base;
}

function _get($str){
	$val = isset($_GET[$str]) ? $_GET[$str] : null;
	return $val;
	}
	
function _post($str){
	$val = isset($_POST[$str]) ? $_POST[$str] : null;
	return $val;
}

function chk_request(){
	foreach($_REQUEST as $key=>$value){
		var_dump($req);
	}
}

function chk_server(){
	echo("<table border=1>");
	foreach($_SERVER as $key=>$value){
		echo("<tr><th>$key</th><td>$value</td></tr>");
	}
	echo("</table>");
}

function chk_session(){
	echo("<table border=1>");
	foreach($_SESSION as $key=>$value){
		echo("<tr><th>$key</th><td>$value</td></tr>");
	}
	echo("</table>");
}

function console_log($data)
	{
		if (is_array($data) || is_object($data))
		{
			echo("<script>console.log('".json_encode($data)."');</script>");
		}
		else
		{
			echo("<script>console.log('".$data."');</script>");
		}
	}
	
function export_data($var, $data)
{
	if (is_array($data) || is_object($data))
	{
		print("\n<script>var ".$var."=".json_encode($data).";</script>");
	}
	else
	{
		print("\n<script>var ".$var."=".$data.";</script>");
	}
}

/** 输出JSON **/
function showJSON($status, $info, $data=array()){
	header('Access-Control-Allow-Origin:*');
	header("Content-Type: text/html; charset=utf-8");
	$datas = array(
		'status' => intval($status),
		'info'   => $info,
		'data'   => (Object)$data,
	);
	if($status != 10000 && array() != $data) {
		$datas['data'] = (Object)array();
		$datas['error'] = $data;
	}
	if(version_compare(PHP_VERSION, '5.4.0') >= 0) {
		echo json_encode($datas, JSON_UNESCAPED_UNICODE);
	}else{
		echo json_encode($datas);
	}
	exit();
}

?>