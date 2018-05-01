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

	require_once("functions.php");
	require_once("site.php");
	require_once("admin.php");
	require_once("admin-users.php");
	require_once("admin-categories.php");
	require_once("admin-products.php");

	$app->run();

 ?>