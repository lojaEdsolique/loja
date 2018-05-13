<?php

use \EDS\Model\User;
use \EDS\Model\Cart;

	//function formatPrice()
	function formatPrice($vlprice)
	{
		if (!$vlprice > 0) $vlprice = 0;

		return number_format($vlprice, 2, ",", ".");
	}

	function checkLogin($inadmin = true) 
	{
		return User::checkLogin($inadmin);
	}

	function getUserName()
	{
		$user = User::getFromSession();
		return $user->getdesperson();
	}

	function getCartNrQtd()
	{
		$cart = Cart::getFromSession();
		$totals = $cart->getProductsTotals();
		return ($totals['nrqtd']);
	}

	function getCartVlTotal()
	{
		$cart = Cart::getFromSession();
		$totals = $cart->getProductsTotals();
		return formatPrice($totals['vlprice']);
	}

 ?>