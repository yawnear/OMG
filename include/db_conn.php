<?php
// mysql-pdo
try{
	$pdo=new PDO('mysql:host=127.0.0.1;dbname=omg', 'health', 'Abc@12345');
	$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	$pdo->exec("SET CHARACTER SET utf8");
	//print("connected!");
}catch(PDOException$e){
	print"Error!:".$e->getMessage()."<br/>";
	die();
}
// mysqli
?>
