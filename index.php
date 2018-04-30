<?php 

session_start();

require_once("vendor/autoload.php");

	use \Slim\Slim;
	use \EDS\Page;
	use \EDS\PageAdmin;
	use \EDS\Model\User;
	use \EDS\Model\Category;

	$app = new Slim();

	$app->config('debug', true);

	$app->get('/', function() {
	    
		$page = new Page();
		$page->setTpl("index");

	});

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

	//Get admin users
	$app->get("/admin/users", function(){

		User::verifyLogin();
		$users = User::listAll();
		$page = new PageAdmin();
		$page->setTpl("users", array(
			"users"=>$users
		));
	});

	//Get admin users create
	$app->get("/admin/users/create", function(){

		User::verifyLogin();
		$page = new PageAdmin();
		$page->setTpl("users-create");
	});

	//Get admin users :iduser delete
	$app->get("/admin/users/:iduser/delete", function($iduser) {

		User::verifyLogin();
		$user = new User();
		$user->get((int)$iduser);
		$user->delete();
		header("Location: /admin/users");
		exit;

	});

	//Get admin users :iduser
	$app->get('/admin/users/:iduser', function($iduser){
	    User::verifyLogin();
	    $user = new User();
	    $user->get((int)$iduser);
	    $page = new PageAdmin();
	    $page ->setTpl("users-update", array(
	        "user"=>$user->getValues()
	    ));
	});

	//Post admin users create
	$app->post("/admin/users/create", function() {

		User::verifyLogin();
		$user = new User();
		$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
		$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
		    "cost"=>12
		]);
		$user->setData($_POST);
		$user->save();
		header("Location: /admin/users");
		exit;
	});

	//Post admin users :iduser
	$app->post("/admin/users/:iduser", function($iduser) {

		User::verifyLogin();
		$user = new User();
		$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
		$user->get((int)$iduser);
		$user->setData($_POST);
		$user->update();
		header("Location: /admin/users");
		exit;
	});

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

		$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
			"cost"=>12
		]);

		$user->setPassword($password);

		$page = new PageAdmin([
			"header"=>false,
			"footer"=>false
		]);

		$page->setTpl("forgot-reset-success");
	});

	//===========ADMIN CATEGORY=============

	//Get admin/categories
	$app->get("/admin/categories", function() {

		User::verifyLogin();

		$categories = Category::listAll();

		$page = new PageAdmin();

		$page->setTpl("categories", [
			'categories'=>$categories
		]);
	});

	//Get admin/categories/create
	$app->get("/admin/categories/create", function() {

		User::verifyLogin();

		$page = new PageAdmin();

		$page->setTpl("categories-create");
	});

	//Post admin/categories/create
	$app->post("/admin/categories/create", function() {

		User::verifyLogin();

		$category = new Category();

		$category->setData($_POST);

		$category->save();

		header('Location: /admin/categories');
		exit;
	});

	//Get admin/categories/:idcategoy/delete
	$app->get("/admin/categories/:idcategory/delete", function($idcategory) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$category->delete();

		header('Location: /admin/categories');
		exit;

	});

	//Get admin/categories/:idcategory
	$app->get("/admin/categories/:idcategory", function($idcategory) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$page = new PageAdmin();

		$page->setTpl("categories-update", [
			'category'=>$category->getValues()
		]);

	});
	//Post admin/categories/:idcategory
	$app->post("/admin/categories/:idcategory", function($idcategory) {

		User::verifyLogin();

		$category = new Category();

		$category->get((int)$idcategory);

		$category->setData($_POST);

		$category->save();

		header('Location: /admin/categories');
		exit;

	});

	//Get /categories/:idcategory
	$app->get("/categories/:idcategory", function($idcategory) {
		$category = new category();
		$category->get((int)$idcategory);
		$page = new Page();
		$page->setTpl("category", [
			'category'=>$category->getValues(),
			'products'=>[]
		]);
	});

	$app->run();

 ?>