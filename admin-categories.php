<?php
use \EDS\PageAdmin;
use \EDS\Model\User;
use \EDS\Page;
use \EDS\Model\Category;
use \EDS\Model\Product;

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

	//Get /categories/:idcategory/products
	$app->get("/admin/categories/:idcategory/products", function($idcategory) {
		User::verifyLogin();
		$category = new Category();
		$category->get((int)$idcategory);
		$page = new PageAdmin();
		$page->setTpl("categories-products", [
			'category'=>$category->getValues(),
			'productsRelated'=>$category->getProducts(),
			'productsNotRelated'=>$category->getProducts(false)
		]);
	});

	//Get /categories/:idcategory/products/:idproduct/add
	$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {
		User::verifyLogin();
		$category = new Category();
		$category->get((int)$idcategory);
		$product = new Product();
		$product->get((int)$idproduct);
		$category->addProduct($product);
		header("Location: /admin/categories/".$idcategory."/products");
		exit;
	});

	//Get /categories/:idcategory/products/:idproduct/remove
	$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {
		User::verifyLogin();
		$category = new Category();
		$category->get((int)$idcategory);
		$product = new Product();
		$product->get((int)$idproduct);
		$category->removeProduct($product);
		header("Location: /admin/categories/".$idcategory."/products");
		exit;
	});   

 ?>