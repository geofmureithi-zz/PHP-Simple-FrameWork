<?php
$ppf= include "/library/base.php";
//$ppf->setBasePath("/ppf"); //Uncomment if not on root of site, change '/ppf' to your site folder
//$ppf->app_etize("db", "mysql:dbname=db", "root","root");//Uncomment and set db name
$ppf->map('GET|POST', '/', 'home#index', 'home');
$ppf->map('GET','/hello/[:name]','home#hello#uppercase');
$ppf->map('GET', '/hello/', function(){ echo 'Hello world'; });
$ppf->map('GET', '/user/[i:id]', 
							function($ppf, $params){
								$id= $params['id'];
								$user = $ppf->db->select('users');
								$user = $user->where('id= ?', $id);
								$user = $user->fetch();
								var_dump($user);
							});
$ppf->map('GET', '/images/logo.png', 
							function($ppf, $params){
								$ppf->stock("logo", "Image");
								$ppf->logo->hollow("250","80","");
								$ppf->logo->text('Your Text', 'GoodDog.otf', 32, '#FFFFFF', 'top', 0, 20);
								$ppf->logo->best_fit(320, 200)->invert();
								$ppf->logo->output();
							});
class home{
	function index($ppf){
		$name= $ppf->read("name");
		Echo "Hello $name!";
	}
	function hello($ppf,$params,$function=""){
		$name= $params["name"];
		if($function=='uppercase'){
		$name = ucwords($name);
		}
		Echo "Hello $name!";
		$link=$ppf->linkTo('home');
		echo "<a href='$link'>Go Home</a>";
	}
}
$ppf->play();

?>
