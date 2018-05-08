<?php
use \EDS\PageAdmin;
use \EDS\Model\User;

	//========Administração=============
	//Get admin
	$app->get('/admin', function() {	    
		User::verifyLogin();
		$page = new PageAdmin();
		$page->setTpl("index");
	});

	//Get admin login
	$app->get('/admin/login', function() {
		$page = new PageAdmin([
			"header"=>false,
			"footer"=>false
		]);
		$page->setTpl("login");
	});

	//Post admin login
	$app->post('/admin/login', function() {
		User::login($_POST["login"], $_POST["password"]);
		header("Location: /admin");
		exit;
	});

	//Get admin logout
	$app->get('/admin/logout', function() {
		User::logout();
		header("Location: /admin/login");
		exit;
	});

	//==========Administração Forgot/Esqueci a Senha==============	
	//Get admin forgot
	$app->get("/admin/forgot", function() {
		$page = new PageAdmin([
			"header"=>false,
			"footer"=>false
		]);
		$page->setTpl("forgot");
	});

	//Post admin forgot
	$app->post("/admin/forgot", function() {		
		$user = User::getForgot($_POST["email"]);
		header("Location: /admin/forgot/sent");
		exit;
	});

	//Get admin forgot sent
	$app->get("/admin/forgot/sent", function() {
		$page = new PageAdmin([
			"header"=>false,
			"footer"=>false
		]);
		$page->setTpl("forgot-sent");
	});

	//Get admim forgot reset
	$app->get("/admin/forgot/reset", function() {
		$user = User::validForgotDecrypt($_GET["code"]);
		$page = new PageAdmin([
			"header"=>false,
			"footer"=>false
		]);
		$page->setTpl("forgot-reset", array(
			"name"=>$user["desperson"],
			"code"=>$_GET["code"]
		));
	});

	//Post admin/forgot/reset
	$app->post("/admin/forgot/reset", function() {
	     $forgot = User::validForgotDecrypt($_POST["code"]);
	     User::setForgotUsed($forgot["idrecovery"]);
	     $user = new User();
	     $user->get((int)$forgot["iduser"]);
	     $user->setPassword($_POST["password"]);
	     $page = new PageAdmin([
	         "header"=>false,
	         "footer"=>false
	     ]);
	     $page->setTpl("forgot-reset-success");
	});

 ?>