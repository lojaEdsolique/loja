<?php
use \EDS\Page;

	$app->get('/', function() {
	    
		$page = new Page();
		$page->setTpl("index");

	});	

 ?>