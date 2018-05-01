<?php
use \EDS\Page;
use \EDS\Model\Product;

	$app->get('/', function() {
	    $products = Product::listAll();
		$page = new Page();
		$page->setTpl("index", [
			'products'=>Product::checkList($products)
		]);

	});	

 ?>