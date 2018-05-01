<?php
use \EDS\Page;
use \EDS\Model\Product;
use \EDS\Model\category;
	
	//Lista os produtos
	$app->get('/', function() {
	    $products = Product::listAll();
		$page = new Page();
		$page->setTpl("index", [
			'products'=>Product::checkList($products)
		]);
	});

	//Get /categories/:idcategory
	$app->get("/categories/:idcategory", function($idcategory) {
		$category = new category();
		$category->get((int)$idcategory);
		$page = new Page();
		$page->setTpl("category", [
			'category'=>$category->getValues(),
			'products'=>Product::checkList($category->getProducts())
		]);
	});
	

 ?>