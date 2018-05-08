<?php

use \EDS\Model\user;
	//function formatPrice()
	function formatPrice(float $vlprice)
	{
		return number_format($vlprice, 2, ",", ".");
	}

	function checklogin($inadmin = true) 
	{
		return User::checkLogin($inadmin);
	}

	function getUserName()
	{
		$user = User::getFromSession();
		return $user->getdesperson();
	}

 ?>