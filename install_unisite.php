<?php
header('Access-Control-Allow-Origin: *');
header('Content-type: text/html; charset=utf-8');
ini_set('display_errors', 'off');
ini_set('max_execution_time', '300');

session_start();

if(isset($_GET['phpinfo'])){
		phpinfo();
		exit;
}

$basePath = __dir__ . "/";

if( isset($_POST["save_dist"]) ){

  $filename = uniqid()  . ".zip";

  if(move_uploaded_file($_FILES["upfile"]["tmp_name"], $basePath . $filename)){

			if( file_exists( $basePath . $filename ) ){
		          
				$zip = new ZipArchive;
				if ($zip->open( $basePath . $filename ) === TRUE) {

				    $zip->extractTo( $basePath );
				    $zip->close();
		              unset($_POST);
		              unlink($basePath . $filename);

				    echo json_encode( ["status" => true] );
				} else {
				    echo json_encode( ["status" => false, "answer" => "Ошибка скачивания или распаковки дистрибутива."] );
				}

			}else{
				echo json_encode( ["status" => false, "answer" => "Ошибка скачивания или распаковки дистрибутива."] );
			}


  }else{
  	echo json_encode( ["status" => false, "answer" => "Ошибка скачивания или распаковки дистрибутива."] );
  }

  exit;

}

if( isset($_POST["save_vendor"]) ){

  $filename = "vendor.zip";

  if(move_uploaded_file($_FILES["upfile"]["tmp_name"], $basePath . 'systems/' . $filename)){

			if( file_exists( $basePath . 'systems/' . $filename ) ){
		          
				$zip = new ZipArchive;
				if ($zip->open( $basePath . 'systems/' . $filename ) === TRUE) {

				    $zip->extractTo( $basePath . 'systems/' );
				    $zip->close();
		              unset($_POST);
		              unlink($basePath . 'systems/' . $filename);

				    echo json_encode( ["status" => true] );
				} else {
				    echo json_encode( ["status" => false, "answer" => "Ошибка скачивания или распаковки vendor."] );
				}

			}else{
				echo json_encode( ["status" => false, "answer" => "Ошибка скачивания или распаковки vendor."] );
			}


  }else{
  	echo json_encode( ["status" => false, "answer" => "Ошибка скачивания или распаковки vendor."] );
  }

  exit;

}

$confStatus = true;
$version = 11;
$phpversion = phpversion();

$getVersion = get_contents("https://api.unisitecloud.ru/api.php?action=installer_version&v=".$version);

$phpinfo_array = [];
$phpinfo_list = [];
ob_start();
phpinfo();
$p=ob_get_contents();
ob_end_clean();
preg_match_all('|<tr>(.+)</tr>|',$p,$m1);

if($m1){
	foreach ($m1[0] as $m2)
	{
		 preg_match_all('|<td(.+?)>(.+?)</td>|',$m2,$m3);
		 $phpinfo_array[]=$m3[2];
	}

	if($phpinfo_array){
		foreach ($phpinfo_array as $key => $value) {
			$phpinfo_list[$value[0]] = $value[1];
		}
	}
}

function diffVersion($verStart=0, $verEnd=0){
		$verStart = explode(".", $verStart);
		$verEnd = explode(".", $verEnd);
		if(intval($verStart[1]) >= intval($verEnd[1])){
			return true;
		}
		return false;
}

function get_contents($url) {

		$data = file_get_contents($url);
		if($data){

			return $data;

		}

}

function getIonCubeLoaderVersion(){
  if (function_exists('ioncube_loader_version')) {
      $version = ioncube_loader_version();
      $a = explode('.', $version);
      $count = count($a);
      if ($count == 3) {
          return $version;
      } elseif ($count == 2) {
          return $version . ".0";
      } elseif ($count == 1) {
          return $version . ".0.0";
      }
      $version = implode('.', array_slice($a, 0, 3));
      return $version;
  }
}

function table_insert($filename,$host, $user, $pass, $dbname, $drop = true)
{

  $templine = '';
  $fp = fopen($filename, 'r');

  if(!$fp){
  	 return "Не удалось прочитать файл базы данных $filename";
  }
  
	try {

	  $pdo = new PDO('mysql:host='.$host.';dbname='.$dbname, $user, $pass);

	  $pdo->exec("set names utf8mb4");

	  if($drop){

		  $result = $pdo->prepare('SHOW TABLES FROM '.$dbname);
		  $result->execute();

		  foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $key => $value) {
		  	$tables[] =  $value["Tables_in_".$dbname];
		  }

		  if($tables) $pdo->exec('drop table '.implode(",", $tables));

	  }

		if($fp)
		while(!feof($fp)) {
		  $line = fgets($fp);
		  if (substr($line, 0, 2) != '--' && $line != '') {
		    $templine .= $line;
		    if (substr(trim($line), -1, 1) == ';') {
		      $pdo->exec($templine);
		      $templine = '';
		    }
		  }
		}
		   
		fclose($fp);

		return true;

	} catch (PDOException $e) {
	  return $e->getMessage();
	}

}

function createHtaccess(){
   global $basePath;

   if( file_exists( $basePath . "/.htaccess" ) ){
     return true;
   }

   $content = '
    Options -Indexes

    <FilesMatch "config\.php">
      Order allow,deny
      Deny from all
    </FilesMatch>

    <FilesMatch ".(htaccess|temp|sql)$">
     Order Allow,Deny
     Deny from all
     </FilesMatch>

    <IfModule mod_php5.c>
      php_flag magic_quotes_gpc off
      php_flag magic_quotes_runtime off
      php_flag register_globals off
    </IfModule>

    RewriteEngine On 
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule .* index.php [L]';

   file_put_contents($basePath . "/.htaccess", $content);

}

if( $_POST["action"] == 1 ){

    $_SESSION["param_form"]["key"] = $_POST["key"];
    $_SESSION["param_form"]["domen"] = getenv("HTTP_HOST");
    $_SESSION["param_form"]["user_name"] = "User";
    $_SESSION["param_form"]["user_email"] = "admin@google.com";

    $_SESSION["param_form"]["update_info"] = '';

    $_SESSION["param_form"]["current_version"] = 4.11;

    echo json_encode(["status" => true, "update_status" => false]);

    exit;
}

if( $_POST["action"] == 3 ){

   if(!$_SESSION["param_form"]["key"]){
  	 echo json_encode( ["status" => false, "answer"=>"Ключ лицензии не определен. Укажите ключ на первом шаге установки."] );
  	 exit;
   }

    // Подготовка db.sql
    $request = file_get_contents("install_ub4/db.sql");
    $request = str_replace('##lkey##', $_SESSION["param_form"]["key"], $request);
    $request = str_replace('##domain##', getenv("HTTP_HOST"), $request);
    //---------------------------

	 if($request){

	 	 file_put_contents($basePath . "db.sql", $request);

	   $error = [];
	   
	   $db_host = $_POST["db_host"];
	   $db_name = $_POST["db_name"];
	   $db_user = $_POST["db_user"];
	   $db_pass = $_POST["db_pass"];

	   if(!$db_host) $error[] = "Пожалуйста, укажите хост"; 
	   if(!$db_name) $error[] = "Пожалуйста, укажите название БД"; 
	   if(!$db_user) $error[] = "Пожалуйста, укажите имя пользователя"; 
	   if(!$db_pass) $error[] = "Пожалуйста, укажите пароль"; 

	   if( count($error) == 0 ){

	   	  $_SESSION["param_form"]["db_host"] = $db_host;
	   	  $_SESSION["param_form"]["db_name"] = $db_name;
	   	  $_SESSION["param_form"]["db_user"] = $db_user;
	   	  $_SESSION["param_form"]["db_pass"] = $db_pass;

				if( file_exists( $basePath . "db.sql" ) ){
					$statusInsert = table_insert($basePath . "db.sql",$db_host, $db_user, $db_pass, $db_name);
			  	if($statusInsert === true){
				  	@unlink($basePath . "db.sql");
				  	echo json_encode( ["status" => true] );
			  	}else{
			  		echo json_encode( ["status" => false, "answer" => $statusInsert] );
			  	}
		    }else{
		      echo json_encode( ["status" => false, "answer" => "Файл БД не найден, проверьте права на запись в каталог и попробуйте еще раз."] );
		    }      

	   }else{
	   	  echo json_encode( ["status" => false, "answer" => implode("\n", $error)] );
	   }

	 }else{
	 	 echo json_encode( ["status" => false, "answer" => "Ошибка скачивания базы данных."] );
	 }

   exit;
    
}

if( $_POST["action"] == 4 ){

	$error = [];

	$user_name = $_POST["user_name"];
	$user_email = $_POST["user_email"];
	$user_pass = $_POST["user_pass"];
	$site_title = $_POST["site_title"];
	$site_name = $_POST["site_name"];
	$prefix = trim($_POST["prefix"], "/");

	if(!$user_name) $error[] = "Пожалуйста, укажите ваше имя";
	if(!$user_email) $error[] = "Пожалуйста, укажите ваш e-mail";
	if(!$user_pass) $error[] = "Пожалуйста, укажите пароль";
	if(!$site_title) $error[] = "Пожалуйста, укажите заголовок сайта";
	if(!$site_name) $error[] = "Пожалуйста, укажите название сайта";
    
    if(!$_SESSION["param_form"]["domen"]) { $error[] = "Домен не определен! Возможно истекла сессия, обновите страницу и попробуйте снова"; }else{
    	$domen = "http://" . $_SESSION["param_form"]["domen"];
    }

	if( $prefix && $prefix != "/" ){
       $_SESSION["domen_prefix"] = $domen . "/" . $prefix;
	}else{
	   $prefix = "/";	
	   $_SESSION["domen_prefix"] = $domen;
	}

	if( $prefix == "/" || !$prefix ){ $urlPrefix = "/"; }else{ $urlPrefix = "/".$prefix."/"; }

	if( count($error) == 0 ){

		$_SESSION["param_form"]["user_name"] = $user_name;
		$_SESSION["param_form"]["user_email"] = $user_email;
		$_SESSION["param_form"]["user_pass"] = $user_pass;
		$_SESSION["param_form"]["site_title"] = $site_title;
		$_SESSION["param_form"]["site_name"] = $site_name;
		$_SESSION["param_form"]["prefix"] = $prefix;

		$_SESSION["folder_admin"] = "cp_" . uniqid();
		$private_hash = md5( uniqid() );

		if( strpos("https", $_SESSION["domen_prefix"]) !== false ){
			$https = 'true';
		}else{
			$https = 'false';
		}

		$conf = file_get_contents( $basePath . "config.php" );
		$conf = preg_replace("/\"https\".*/i", '"https" => '.$https.',' , $conf);
		$conf = preg_replace("/\"folder_admin\".*/i", '"folder_admin" => "'.$_SESSION["folder_admin"].'",' , $conf);
		$conf = preg_replace("/\"private_hash\".*/i", '"private_hash" => "'.$private_hash.'",' , $conf);
		$conf = preg_replace("/\"private_hex\".*/i", '"private_hex" => "'.md5( uniqid() ).'",' , $conf);
		$conf = preg_replace("/\"feed_ads_key\".*/i", '"feed_ads_key" => "'.mt_rand(1000000000,9000000000).'",' , $conf);
		$conf = preg_replace("/\"cron_key\".*/i", '"cron_key" => "'.mt_rand(1000000000,9000000000).'",' , $conf);
		$conf = preg_replace("/\"api_key\".*/i", '"api_key" => "'.mt_rand(1000000000,9000000000).'",' , $conf);
		$conf = preg_replace("/\"urlPrefix\".*/i", '"urlPrefix" => "'.$urlPrefix.'",' , $conf);
		$conf = preg_replace("/\"urlPath\".*/i", '"urlPath" => "'.$_SESSION["domen_prefix"].'",' , $conf);
		$conf = preg_replace("/\"status\".*/i", '"status" => false,' , $conf);
		$conf = preg_replace("/\"display_errors\".*/i", '"display_errors" => false,' , $conf);

		$conf = preg_replace("/\"host\".*/i", '"host" => "'.$_SESSION["param_form"]["db_host"].'",' , $conf);
		$conf = preg_replace("/\"user\".*/i", '"user" => "'.$_SESSION["param_form"]["db_user"].'",' , $conf);
		$conf = preg_replace("/\"pass\".*/i", '"pass" => "'.$_SESSION["param_form"]["db_pass"].'",' , $conf);
		$conf = preg_replace("/\"database\".*/i", '"database" => "'.$_SESSION["param_form"]["db_name"].'",' , $conf);

		if( file_put_contents( $basePath . "config.php" , $conf) ){

			@rename( $basePath . "admin" , $basePath . $_SESSION["folder_admin"] );
			createHtaccess();

				try {

				  $pdo = new PDO('mysql:host='.$_SESSION["param_form"]["db_host"].';dbname='.$_SESSION["param_form"]["db_name"], $_SESSION["param_form"]["db_user"], $_SESSION["param_form"]["db_pass"]);

		    	$password = password_hash($user_pass.$private_hash, PASSWORD_DEFAULT);

          $pdo->exec("UPDATE uni_settings SET value='{$_SESSION["param_form"]["key"]}' WHERE name='lnc_key'");
          $pdo->exec("UPDATE uni_settings SET value='{$site_name}' WHERE name='site_name'");
          $pdo->exec("UPDATE uni_settings SET value='{$user_email}' WHERE name='contact_email'");
          $pdo->exec("UPDATE uni_settings SET value='{$user_email}' WHERE name='email_alert'");
          $pdo->exec("UPDATE uni_settings SET value='{$site_title}' WHERE name='title'");
          $pdo->exec("UPDATE uni_settings SET value='{$site_name}' WHERE name='name_responder'");

          $privileges = 'control_statistics,control_blog,control_page,control_clients,control_banner,control_city,control_manager,control_seo,control_admin,control_tpl,control_settings,control_orders,view_orders,control_responder,control_board,processing_board,control_secure,control_transactions,control_chat,control_reviews,control_multilang,control_shops';
    
					$pdo->exec("INSERT INTO uni_admin(fio,pass,email,role,privileges,main_admin)VALUES('".$user_name."','".$password."','".$user_email."','1','".$privileges."','1')");

		    	echo json_encode( ["status" => true] );

				} catch (PDOException $e) {
				  echo json_encode( ["status" => false, "answer" => "Ошибка подключения к БД. " . $e->getMessage()] );
				}

		}else{

			echo json_encode( ["status" => false, "answer" => "Недостаточно прав на запись. Установите права 777 на файл config.php."] );

		}
        
	}else{
		echo json_encode( ["status" => false, "answer" => implode("\n", $error)] );
	}

	exit;

}

?>

<!DOCTYPE html>
<html>
<head>
	<title>UniSite CMS. Установка</title>
	<link href="https://fonts.googleapis.com/css2?family=PT+Sans&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" >
	<link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/font-awesome-line-awesome/css/all.min.css">
	<link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
	<style type="text/css">
		body{
			background-color: #F7F8FA; font-family: "Open Sans",Roboto, Helvetica, Arial, sans-serif;
		}
		.container{
            max-width: 900px;
		}
		.box{
            padding: 30px; background-color: white;
            box-shadow: 0 1px 15px 1px rgba(52, 40, 104, .08);
            border-radius: 10px; margin-top: 20px;
		}	
		.header{
			margin-top: 50px;
		}	
		.header h4{
			margin: 0; font-size: 25px; font-weight: 600
		}	
		.footer{
			margin-top: 15px; margin-bottom: 60px;
		}
		input{
			height: 41px!important;
		}
		button, a.button{
			margin-top: 30px; padding: 10px 0!important; border-radius: 25px!important; font-size: 14px; display: block;
		}
		.label-success{
			display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; background-color: #02F4AA; color: white;
			border-radius: 50%; margin-right: 5px;
		}
		.label-error{
			display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; background-color: #fe4e95; color: white;
			border-radius: 50%; margin-right: 5px;
		}		
		.status-label i{
			font-size: 12px;
		}
		.mt30{
			margin-top: 30px;
		}	
		ul{
			list-style: none; padding: 0px;
		}	
		a{
			font-size: 14px;
		}
		.help-link{
			display: inline-block; padding: 10px 15px; border-radius: 25px; background-color: transparent; border: 2px solid #007BFF;
		}
		.mt5{
			margin-top: 5px;
		}
		.mt10{
			margin-top: 10px;
		}
		.item{
			display: none;
		}		
		.lang-select{
			position: relative; z-index: 10; margin-top: 10px;
		}	
		.lang-select:hover .lang-select-list{
			display: block;
		}
		.lang-select-list{
			position: absolute; right: 0px; top: 27px; background-color: white; padding: 10px 0; border-radius: 5px;
			width: 150px; text-align: left; border: 1px solid #007bff; display: none;
		}
		.lang-select-list a{
			display: block; padding: 5px 12px; color: black;
		}
		.lang-select-list a:hover{
			background-color: #007bff; text-decoration: none; color: white;
		}
		.lang-select-list a img{
			margin-right: 3px;
		}						
		.lang-select-change{
			display: inline-flex;
			align-items: center;
			cursor: pointer;
		}	
		.lang-select-change img{
			margin-right: 5px;
		}		
		.label-status{
            float: left;
            margin-right: 8px;
            width: 18px;
            margin-top: 3px;
		}
		.label-title{
            float: left; width: calc( 100% - 26px );
		}
		.clr{
			clear: both;
		}
		small{
			margin-top: 10px;
		}
		label{
			font-weight: 600;
		}
		p{
			font-size: 14px;
		}
		.mt50{ margin-top: 50px;  }
		.mt40{ margin-top: 40px;  }

	</style>
</head>
<body>
    
	<div class="container" >
        
        <?php if($getVersion){ ?>
		<div class="alert alert-danger" style="margin-top: 25px; font-size: 14px;" >
			<?php echo $getVersion; ?>
		</div>
	    <?php } ?>
        
        <div class="header" >
        	<div class="row" >
        		<div class="col-lg-9 col-6" >
        			<img src="https://unisite.org/assets/images/unisite-main-logo.png" height="40px" >
        		</div>
        		<div class="col-lg-3 text-right col-6" >

        		</div>
        	</div>
        </div>

		<div class="box" >

			<div class="row" >
				<div class="col-lg-4" >

					<div class="list-group">
					  <a href="?step=1" class="list-group-item list-group-item-action <?php if( $_GET["step"] == 1 || !$_GET["step"] ){ echo 'active'; } ?>">Начало установки</a>
					  <a href="?step=2" class="list-group-item list-group-item-action <?php if($_SESSION["param_form"]["key"]){ if( $_GET["step"] == 2 ){ echo 'active'; } }else{ echo 'disabled'; } ?>">Активация лицензии</a>
					  <a href="?step=3" class="list-group-item list-group-item-action <?php if($_SESSION["param_form"]["key"]){ if( $_GET["step"] == 3 ){ echo 'active'; } }else{ echo 'disabled'; } ?>">Проверка параметров</a>
					  <a href="?step=4" class="list-group-item list-group-item-action <?php if($_SESSION["param_form"]["key"]){ if( $_GET["step"] == 4 ){ echo 'active'; } }else{ echo 'disabled'; } ?>">Установка системы</a>
					  <a href="?step=5" class="list-group-item list-group-item-action <?php if($_SESSION["param_form"]["key"]){ if( $_GET["step"] == 5 ){ echo 'active'; } }else{ echo 'disabled'; } ?>">База данных</a>
					  <a href="?step=6" class="list-group-item list-group-item-action <?php if($_SESSION["param_form"]["key"]){ if( $_GET["step"] == 6 ){ echo 'active'; } }else{ echo 'disabled'; } ?>">Завершение</a>
					</div>
					
				</div>
				<div class="col-lg-8" >

					<div class="item" <?php if( $_GET["step"] == 1 || !$_GET["step"] ){ echo 'style="display: block"'; } ?> >

						<h3 class="text-center mt40" > UniSite CMS. Установка </h3>

						<div class="mt30" ></div>

						<button class="btn btn-primary btn-block step-1">Приступить к установке <i class="las la-arrow-right"></i></button>
						<a class="btn btn-light btn-block" href="https://unisite.org/doc/ustanovka-unisite-board-4-0" style="margin-top: 10px; padding: 10px 0; border-radius: 25px; vertical-align: middle; " >Инструкция по установке  <i class="las la-arrow-right"></i></a>

					</div>

					<div class="item" <?php if( $_GET["step"] == 2 ){ echo 'style="display: block"'; } ?>  >

						<h3> <strong>Ключ лицензии</strong> </h3>
						<p>Для установки необходимо указать лицензионный ключ который выдаётся после покупки системы. Приобрести систему можно на <a href="https://unisite.org" target="_blank" >официальном сайте</a> </p>

						<div class="mt30" ></div>
						
						<input type="text" class="form-control" name="lic_key" placeholder="Укажите ключ" value="<?php echo $_SESSION["param_form"]["key"]; ?>" >

						<button class="btn btn-light btn-block step-2">Продолжить <i class="las la-arrow-right"></i></button>

					</div>

					<div class="item" <?php if( $_GET["step"] == 8 ){ echo 'style="display: block"'; } ?>  >

              <div class="block-pay-update-container" ><?php echo $_SESSION["param_form"]["update_info"]; ?></div>

					</div>

					<div class="item" <?php if( $_GET["step"] == 3 ){ echo 'style="display: block"'; } ?> >

						<h3> <strong>Проверка параметров</strong> </h3>
						<p> Список параметров которые обязательно должны быть включены на вашем хостинге или сервере. </p>

						<div class="mt30" ></div>
						
						<ul>
							<li>
								<?php 

								if(diffVersion($_SESSION["param_form"]["current_version"], "4.9")){
                    if( strpos(phpversion(), "7.4") === false && strpos(phpversion(), "8.1") === false){
                       ?>
                       <span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title" > Текущая версия PHP <strong><?php echo phpversion(); ?></strong>. <br> <small>Для корректной работы системы необходимо использовать версию PHP 7.4 или 8.1 с модулем Apache или FastCGI!</small> </span>
                       <?php
                       $confStatus = false;
                    }else{
                    	 ?>
                    	 <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title" > Текущая версия PHP <strong><?php echo phpversion(); ?></strong>. <br> <small>Для корректной работы системы необходимо использовать версию PHP 7.4 или 8.1 с модулем Apache или FastCGI!</small> </span>
                    	 <?php 
                    }
                }else{
                    if( strpos(phpversion(), "7.4") === false){
                       ?>
                       <span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title" > Текущая версия PHP <strong><?php echo phpversion(); ?></strong>. <br> <small>Для корректной работы системы необходимо использовать версию PHP 7.4 с модулем Apache или FastCGI!</small> </span>
                       <?php
                       $confStatus = false;
                    }else{
                    	 ?>
                    	 <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title" > Текущая версия PHP <strong><?php echo phpversion(); ?></strong>. <br> <small>Для корректной работы системы необходимо использовать версию PHP 7.4 с модулем Apache или FastCGI!</small> </span>
                    	 <?php 
                    }                	
                }
								?>
								<div class="clr" ></div>
							</li>
							<li> 
								<?php
                    if(!extension_loaded('PDO')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">PDO</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">PDO</span>
                        <?php
                    }
								?>
								<div class="clr" ></div>
							</li>							
							<li> 
								<?php
                    if(!extension_loaded('imagick')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">Imagick</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Imagick</span>
                        <?php
                    }
								?>
								<div class="clr" ></div>
							</li>
              <li> 
								<?php
                    if(!extension_loaded('openssl')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">Openssl</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                      ?>
                      <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Openssl</span>
                      <?php
                    }
								?>
								<div class="clr" ></div>
							</li>							
							<li> 
								<?php
                    if(!extension_loaded('mbstring')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">Mbstring</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Mbstring</span>
                        <?php
                    }
								?>
								<div class="clr" ></div>
							</li>
							<li> 
								<?php
                    if(!extension_loaded('curl')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">Curl</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Curl</span>
                        <?php
                    }
								?>
								<div class="clr" ></div>
							</li>
							<li> 
								<?php
                    if(!extension_loaded('ionCube Loader')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">ionCube Loader</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">ionCube Loader</span>
                        <?php
                    }
								?>
								<div class="clr" ></div>
							</li>
							<li> 
								<?php
                    if(!extension_loaded('zip')){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">Zip</span>
                    	<?php
                    	$confStatus = false;
                    }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Zip</span>
                        <?php
                    }
								?>
								<div class="clr" ></div>
							</li>
							<li> 
								<span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">ffmpeg (Рекомендуем)</span>
								<div class="clr" ></div>
							</li>														
							<li> 
								<?php

								if(!mkdir("temp_folder", 0777)) {
								    $error = error_get_last();
								    if( strpos($error["message"], "denied") !== false ){
								       ?>
								       <span class="label-error label-status" ><i class="las la-check"></i></span> <span class="label-title">Права на запись</span>
								       <?php
								       $confStatus = false;
								    }else{
								       ?>
								       <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Права на запись</span>
								       <?php
								    }
								}else{
									?>
									<span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">Права на запись</span>
									<?php
								}

								@rmdir("temp_folder");

								?>
								<div class="clr" ></div>
							</li>	
							<li><div style="margin-top: 15px; margin-bottom: 15px;" ><strong>Настройки php</strong></div></li>
							<li>
								<?php
                  if($phpinfo_list["short_open_tag"] == "Off"){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">short_open_tag</span>
                    	<?php
                  }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">short_open_tag</span>
                        <?php
                  }
								?>
								<div class="clr" ></div>
								<small>Данный параметр меняется в настройках php.ini и должен быть в режиме On</small>
							</li>
							<li>
								<?php
                  if(intval($phpinfo_list["max_execution_time"]) <= 30){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">max_execution_time</span>
                    	<?php
                  }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">max_execution_time</span>
                        <?php
                  }
								?>
								<div class="clr" ></div>
								<small>Данный параметр меняется в настройках php.ini и должен быть больше 30 секунд, например 300</small>
							</li>
							<li>
								<?php 
                  if(intval($phpinfo_list["post_max_size"]) < 128){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">post_max_size</span>
                    	<?php
                  }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">post_max_size</span>
                        <?php
                  }
								?>
								<div class="clr" ></div>
								<small>Данный параметр меняется в настройках php.ini и должен быть не меньше 128мб</small>
							</li>
							<li>
								<?php 
                  if(intval($phpinfo_list["upload_max_filesize"]) < 128){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">upload_max_filesize</span>
                    	<?php
                  }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">upload_max_filesize</span>
                        <?php
                  }
								?>
								<div class="clr" ></div>
								<small>Данный параметр меняется в настройках php.ini и должен быть не меньше 128мб</small>
							</li>
							<li>
								<?php 
                  if(intval($phpinfo_list["max_input_vars"]) <= 1000){
                    	?>
                    	<span class="label-error label-status" ><i class="las la-times"></i></span> <span class="label-title">max_input_vars</span>
                    	<?php
                  }else{
                        ?>
                        <span class="label-success label-status" ><i class="las la-check"></i></span> <span class="label-title">max_input_vars</span>
                        <?php
                  }
								?>
								<div class="clr" ></div>
								<small>Данный параметр меняется в настройках php.ini и должен быть больше 1000, например 10000</small>
							</li>
							<div class="clr" ></div>																											

						</ul>

						<button class="btn btn-light btn-block step-3" <?php if(!$confStatus){ echo 'disabled=""'; } ?> >Продолжить <i class="las la-arrow-right"></i></button>
						

					</div>

					<div class="item" <?php if( $_GET["step"] == 4 ){ echo 'style="display: block"'; } ?> >

						<h3> <strong>Скачивание и установка системы</strong> </h3>
						<p> Это может занять некоторое время. </p>

						<div class="mt30" ></div>
						
						<div class="progress" >
						  <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
						</div>

					</div>

					<div class="item" <?php if( $_GET["step"] == 5 ){ echo 'style="display: block"'; } ?> >

						<h3> <strong>База данных</strong> </h3>
						<p> Необходимо создать базу данных на вашем хостинге или сервере и указать данные для подключения. </p>

						<div class="mt30" ></div>

						<form class="form-db" >
							
							<label> Хост </label>
							<input type="text" class="form-control" name="db_host" value="<?php echo $_SESSION["param_form"]["db_host"] ? $_SESSION["param_form"]["db_host"] : "localhost"; ?>" >

							<div class="mt10" ></div>

							<label> Название БД </label>
							<input type="text" class="form-control" name="db_name" value="<?php echo $_SESSION["param_form"]["db_name"]; ?>" >

							<div class="mt10" ></div>

							<label> Имя пользователя </label>
							<input type="text" class="form-control" name="db_user" value="<?php echo $_SESSION["param_form"]["db_user"]; ?>" >	

							<div class="mt10" ></div>					

							<label> Пароль </label>
							<input type="text" class="form-control" name="db_pass" value="<?php echo $_SESSION["param_form"]["db_pass"]; ?>" >

							<button class="btn btn-light btn-block step-5">Продолжить <i class="las la-arrow-right"></i></button>								
						</form>

					</div>

					<div class="item" <?php if( $_GET["step"] == 6 ){ echo 'style="display: block"'; } ?> >
						
						<h3> <strong>Завершение</strong> </h3>
						<p>Осталось совсем чуть-чуть, укажите настройки сайта и данные для входа в админ панель.</p>

						<div class="mt30" ></div>
                        
                        <form class="form-settings" >

							<label> Ваше имя </label>
							<input type="text" class="form-control" name="user_name" value="<?php echo $_SESSION["param_form"]["user_name"]; ?>" >	

							<div class="mt10" ></div>					

							<label> E-mail </label>
							<input type="text" class="form-control" name="user_email" value="<?php echo $_SESSION["param_form"]["user_email"]; ?>">

							<div class="mt10" ></div>					

							<label> Пароль </label>
							<input type="text" class="form-control" name="user_pass" value="<?php echo $_SESSION["param_form"]["user_pass"]; ?>">

							<div class="mt10" ></div>					

							<label> Заголовок сайта </label>
							<input type="text" class="form-control" name="site_title" placeholder="Например: SuperBoard - Супер доска объявлений" value="<?php echo $_SESSION["param_form"]["site_title"]; ?>" >

							<div class="mt10" ></div>

							<label> Название сайта/проекта </label>
							<input type="text" class="form-control" name="site_name" placeholder="Например: SuperBoard" value="<?php echo $_SESSION["param_form"]["site_name"]; ?>" >

							<hr>

							<div class="mt10" ></div>

							<label> Директория сайта </label>
							<input type="text" class="form-control" name="prefix" value="<?php echo $_SESSION["param_form"]["prefix"]; ?>" >
							<small>Если система устанавливается не в корень сайта, то укажите название директории. Например: mydir или mydir1/mydir2</small>							

							<button class="btn btn-success btn-block step-6">Завершить установку</button>

						</form>

					</div>

					<div class="item" <?php if( $_GET["step"] == 7 ){ echo 'style="display: block"'; } ?> >

						<h3> <strong>Установка успешно завершена!</strong> </h3>
						 
						<div class="mt30" ></div>   

						<p>Админ панель - <a href="<?php echo $_SESSION["domen_prefix"] . "/" . $_SESSION["folder_admin"]; ?>"><?php echo $_SESSION["domen_prefix"] . "/" . $_SESSION["folder_admin"]; ?></a></p>
						<p>Логин - <strong><?php echo $_SESSION["param_form"]["user_email"]; ?></strong> <br> Пароль - <strong><?php echo $_SESSION["param_form"]["user_pass"]; ?></strong></p>     

						<p style="color: red;" >Внимание! Обязательно удалите установочный файл!</p>                

					</div>

					
				</div>
			</div>
			
		</div>
        
        <?php if( $_GET["step"] != 1 && $_GET["step"] ){ ?>
		<div class="footer text-right" >
			<a href="https://unisite.org/doc/ustanovka-unisite-board-4-0" class="help-link" > <i class="fa fa-question" aria-hidden="true"></i> Инструкция по установке </a>
		</div>
		<?php } ?>		
		
	</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" ></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" ></script>


<script type="text/javascript">
	
$(document).ready(function () {

	 <?php 
	 if( $_GET["step"] == 4 ){
	 	 ?>

	 	 $(".progress-bar").css("width", "15%");

	 	 downDist();

		 function saveDist (blob) {

		  var dataForm = new FormData();
		  dataForm.append("upfile", blob);
		  dataForm.append("save_dist", '1');

				$.ajax({
            type: "POST",url: "install_unisite.php",data: dataForm,dataType: "json",cache: false,contentType: false,processData: false,                                                
            success: function (data) {

		          if( data["status"] == true ){
		          	$(".progress-bar").css("width", "50%");
		          	downVendor();
		          }else{
		          	$(".progress-bar").css("width", "0%");
		          	alert( data["answer"] );
		          }
                                        
            },
            error: function(jqXHR, textStatus, errorThrown) {
		          	$(".progress-bar").css("width", "0%");
		          	alert( errorThrown );
						}
        });

		 }

		 function saveVendor (blob) {

		  var dataForm = new FormData();
		  dataForm.append("upfile", blob);
		  dataForm.append("save_vendor", '1');

				$.ajax({
            type: "POST",url: "install_unisite.php",data: dataForm,dataType: "json",cache: false,contentType: false,processData: false,                                                
            success: function (data) {

		          if( data["status"] == true ){
		          	$(".progress-bar").css("width", "100%");
		          	location.href = "?step=5";
		          }else{
		          	$(".progress-bar").css("width", "0%");
		          	alert( data["answer"] );
		          }
                                        
            },
            error: function(jqXHR, textStatus, errorThrown) {
		          	$(".progress-bar").css("width", "0%");
		          	alert( errorThrown );
						}
        });

		 }

		 function downDist(){

			 var xhr = new XMLHttpRequest();
				xhr.open("GET", "/install_ub4/distr.zip");
				xhr.responseType = "arraybuffer";

				xhr.onload = function () {
				    if (this.status === 200) {
				    		$(".progress-bar").css("width", "30%");
				        var blob = new Blob([xhr.response], {type: "application/zip"});
				        var objectUrl = URL.createObjectURL(blob);
								saveDist(blob);
				    }
				};
				xhr.send();

		 }

		 function downVendor(){

			 var xhr = new XMLHttpRequest();
				xhr.open("GET", "/install_ub4/vendor.zip");
				xhr.responseType = "arraybuffer";

				xhr.onload = function () {
				    if (this.status === 200) {
				    		$(".progress-bar").css("width", "70%");
				        var blob = new Blob([xhr.response], {type: "application/zip"});
				        var objectUrl = URL.createObjectURL(blob);
								saveVendor(blob);
				    }
				};
				xhr.send();

		 }


	 	 <?php
	 }
	 ?>

   $(document).on('click','.step-1', function () {     
        location.href = "?step=2";
   }); 

   $(document).on('click','.step-2', function (e) {
    
    var element = this;

   	$(element).prop('disabled', true);

	   	if( $("input[name=lic_key]").val() ){

	      $.ajax({type: "POST",url: "install_unisite.php",data: "key=" + $("input[name=lic_key]").val() + "&action=1",dataType: "json",cache: false,
	      	  success: function (data) { 
	            
	            if( data["status"] == true ){

	            	if( !data["update_status"] ){
	            		 location.href = "?step=3";
	            	}else{
	            		 location.href = "?step=8";
	            	}
	            	
	            }else{
	            	alert( "Лицензия недействительна!" );
	            	$(element).prop('disabled', false);
	            }

	          }
	      });

	    }else{
	    	alert( "Пожалуйста, укажите ключ лицензии" );
	    	$(element).prop('disabled', false);
	    }

      e.preventDefault();
   }); 

   $(document).on('click','.step-3', function (e) {

	   	location.href = "?step=4";

      e.preventDefault();
   });

   $(document).on('click','.step-5', function (e) {

   	var element = this;

   	$(element).prop('disabled', true);

      $.ajax({type: "POST",url: "install_unisite.php",data: $(".form-db").serialize() + "&action=3",dataType: "json",cache: false,
      	  success: function (data) { 
            
            if( data["status"] == true ){
            	location.href = "?step=6";
            }else{
            	alert( data["answer"] );
            	$(element).prop('disabled', false);
            }

          }
      });

      e.preventDefault();
   }); 

   $(document).on('click','.step-6', function (e) {

   	var element = this;

   	$(element).prop('disabled', true);

      $.ajax({type: "POST",url: "install_unisite.php",data: $(".form-settings").serialize() + "&action=4",dataType: "json",cache: false,
      	  success: function (data) { 
            
            if( data["status"] == true ){
            	location.href = "?step=7";
            }else{
            	alert( data["answer"] );
            	$(element).prop('disabled', false);
            }

          }
      });

      e.preventDefault();
   });


});

</script>
</body>
</html>