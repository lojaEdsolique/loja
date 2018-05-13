<?php

use \EDS\Page;
use \EDS\Model\Product;
use \EDS\Model\Category;
use \EDS\Model\Cart;
use \EDS\Model\Address;
use \EDS\Model\User;
use \EDS\Model\Order;
use \EDS\Model\OrderStatus;
	
	//Rota - Lista os produtos
	$app->get('/', function() {
	    $products = Product::listAll();
		$page = new Page();
		$page->setTpl("index", [
			'products' =>Product::checkList($products)
		]);
	});

	//Rota - Paginação
	$app->get("/categories/:idcategory", function($idcategory) {
		$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
		$category = new Category();
		$category->get((int)$idcategory);
		$pagination = $category->getProductsPage($page);
		$pages = [];

		for ($i=1; $i <= $pagination['pages']; $i++) { 
			array_push($pages, [
				'link' =>'/categories/'.$category->getidcategory().'?page='.$i,
				'page' =>$i
			]);
		}
		$page = new Page();
		$page->setTpl("category", [
			'category' =>$category->getValues(),
			'products' =>$pagination["data"],
			'pages'    =>$pages
		]);
	});

	//Rota - Detalhe do produto
	$app->get("/products/:desurl", function($desurl) {
		$product = new Product();
		$product->getFromURL($desurl);
		$page = new Page();
		$page->setTpl("product-detail", [
			'product'    =>$product->getValues(),
			'categories' =>$product->getCategories()
		]);
	});

	//Rota - Carrinho
	$app->get('/cart', function() {
		$cart = Cart::getFromSession();
		$page = new Page();
		$page->setTpl("cart", [
			'cart'     =>$cart->getValues(),
			'products' =>$cart->getProducts(),
			'error'    =>Cart::getMsgError()
		]);
	});

	//Rota - adicina produto carrinho
	$app->get("/cart/:idproduct/add", function($idproduct) {
		$product = new Product();
		$product->get((int)$idproduct);
		$cart = Cart::getFromSession();
		$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
			for ($i = 0; $i < $qtd; $i++) {
				$cart->addProduct($product);
			}
		
		header("Location: /cart");
		exit;
	});

	//Rota - Remove um produto do carrinho
	$app->get("/cart/:idproduct/minus", function($idproduct) {
		$product = new Product();
		$product->get((int)$idproduct);
		$cart = Cart::getFromSession();
		$cart->removeProduct($product);
		header("Location: /cart");
		exit;
	});

	//Rota - Remove todos os produtos do carrinho
	$app->get("/cart/:idproduct/remove", function($idproduct) {
		$product = new Product();
		$product->get((int)$idproduct);
		$cart = Cart::getFromSession();
		$cart->removeProduct($product, true);
		header("Location: /cart");
		exit;
	});

	$app->post("/cart/freight", function() {
		$cart = Cart::getFromSession();
		$cart->setFreight($_POST['zipcode']);
		header("Location: /cart");
		exit;

	});

	$app->get("/checkout", function() {
		User::verifyLogin(false);
		$address = new Address();
		$cart = Cart::getFromSession();

		if (isset($_GET['zipcode'])) {
			$_GET['zipcode'] = $cart->getdeszipcode();
		}

		if (isset($_GET['zipcode'])) {
			$address->loadFromCEP($_GET['zipcode']);
			$cart->setdeszipcode($_GET['zipcode']);
			$cart->save();
			$cart->getCalculateTotal();
		}

		if (!$address->getdesaddress()) $address->setdesaddress('');
		if (!$address->getdescomplement()) $address->setdescomplement('');
		if (!$address->getdesdistrict()) $address->setdesdistrict('');
		if (!$address->getdescity()) $address->setdescity('');
		if (!$address->getdesstate()) $address->setdesstate('');
		if (!$address->getdescountry()) $address->setdescountry('');
		if (!$address->getdeszipcode()) $address->setdeszipcode('');

		$page = new Page();
		$page->setTpl("checkout", [
			'cart'     =>$cart->getValues(),
			'address'  =>$address->getValues(),
			'products' =>$cart->getProducts(),
			'error'    =>Address::getMsgError()
		]);
	});

	$app->post("/checkout", function() {
		User::verifyLogin(false);

		if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
			Address::setMsgError("Informe o CEP.");
			header('Location: /checkout');
			exit;
		}

		if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
			Address::setMsgError("Informe o endereço.");
			header('Location: /checkout');
			exit;
		}

		if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
			Address::setMsgError("Informe o bairro.");
			header('Location: /checkout');
			exit;
		}

		if (!isset($_POST['descity']) || $_POST['descity'] === '') {
			Address::setMsgError("Informe a cidade/localidade.");
			header('Location: /checkout');
			exit;
		}

		if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
			Address::setMsgError("Informe o estado.");
			header('Location: /checkout');
			exit;
		}

		if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
			Address::setMsgError("Informe o país.");
			header('Location: /checkout');
			exit;
		}

		$user = User::getFromSession();
		$address = new Address();
		$_POST['deszipcode'] = $_POST['zipcode'];
		$_POST['idperson'] = $user->getidperson();
		$address->setData($_POST);
		$address->save();
		$cart = Cart::getFromSession();
		$cart = getCalculateTotal();
		$order = new Order();
		$order->setData([
			'idcart'=>$cart->getidcart(),
			'idaddress'=>$address->getidaddress(),
			'iduser'=>$user->getiduser(),
			'idstatus'=>OrderStatus::EM_ABERTO,
			'vltotal'=>$cart->getvltotal()
		]);

		$order->save();

		header("Location: /order/".$order->getidorder());
		exit;
	});

	//Rota - erro no login
	$app->get("/login", function() {
		$page = new Page();
		$page->setTpl("login", [
			'error'          =>User::getError(),
			'errorRegister'  =>User::getErrorRegister(),
			'registerValues' =>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
		]);
	});

	//Rota - Login no site
	$app->post("/login", function() {
		try {
			User::login($_POST['login'], $_POST['password']);
		} catch(Exception $e) {
			User::setError($e->getMessage());
		}
		header("Location: /checkout");
		exit;
	});

	//Rota - 
	$app->get("/logout", function() {
		User::logout();
		header("Location: /login");
		exit;
	});

	//Rota - Registro de novo usuario no site
	$app->post("/register", function() {

		$_SESSION['registerValues'] = $_POST;

		if (!isset($_POST['name']) || $_POST['name'] == '') {
			User::setErrorRegister("Preencha o seu nome.");
			header("Location: /login");
			exit;
		}

		if (!isset($_POST['email']) || $_POST['email'] == '') {
			User::setErrorRegister("Preencha o seu e-mail.");
			header("Location: /login");
			exit;
		}
		if (!isset($_POST['password']) || $_POST['password'] == '') {
			User::setErrorRegister("Preencha a senha.");
			header("Location: /login");
			exit;
		}

		if (User::checkLoginExist($_POST['email']) === true) {
			User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuáro.");
			header("Location: /login");
			exit;
		}


		$user = new User();
		$user->setData([
			'inadmin'    =>0,
			'deslogin'   =>$_POST['email'],
			'desperson'  =>$_POST['name'],
			'desemail'   =>$_POST['email'],
			'despassword'=>$_POST['password'],
			'nrphone'    =>$_POST['phone']
		]);
		$user->save();
		User::login($_POST['email'], $_POST['password']);
		header('Location: /checkout');
		exit;
	});

	//========== Site Forgot/Esqueci a Senha ==============	
	//Get site forgot
	$app->get("/forgot", function() {
		$page = new Page();
		$page->setTpl("forgot");
	});

	//Post site forgot
	$app->post("/forgot", function() {		
		$user = User::getForgot($_POST["email"], false);
		header("Location: /forgot/sent");
		exit;
	});

	//Get site forgot sent
	$app->get("/forgot/sent", function() {
		$page = new Page();
		$page->setTpl("forgot-sent");
	});

	//Get site forgot reset
	$app->get("/forgot/reset", function() {
		$user = User::validForgotDecrypt($_GET["code"]);
		$page = new Page();
		$page->setTpl("forgot-reset", array(
			"name" =>$user["desperson"],
			"code" =>$_GET["code"]
		));
	});

	//Post site/forgot/reset
	$app->post("/forgot/reset", function() {
	     $forgot = User::validForgotDecrypt($_POST["code"]);
	     User::setForgotUsed($forgot["idrecovery"]);
	     $user = new User();
	     $user->get((int)$forgot["iduser"]);
	     $password = user::getPasswordHash($_POST['password']);
	     $user->setPassword($password);
	     $page = new Page();
	     $page->setTpl("forgot-reset-success");
	});

	//Profile - Edição do perfil do usuário
	$app->get("/profile", function() {
		User::verifyLogin(false);
		$user = User::getFromSession();
		$page = new Page();
		$page->setTpl("profile", [
			'user'         =>$user->getValues(),
			'profileMsg'   =>User::getSuccess(),
			'profileError' =>User::getError()
		]);

	});

	$app->post("/profile", function() {
		User::verifyLogin(false);

		if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {
			User::setError("Preencha o seu nome.");
			header('Location: /profile');
			exit;
		}

		if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {
			User::setError("Preencha o seu e-mail.");
			header('Location: /profile');
			exit;
		}

		$user = User::getFromSession();
		if ($_POST['desemail'] !== $user->getdesemail()) {
			if (User::checkLoginExist($_POST['desemail']) === true) {
				User::setError("Este endereço de e-mail já está cadastrado.");
				header('Location: /profile');
			exit;
			}
		}
		
		$_POST['inadmin'] = $user->getinadmin();
		$_POST['despassword'] = $user->getdespassword();
		$_POST['deslogin'] = $_POST['desemail'];
		$user->setData($_POST);
		$user->save();
		User::setSuccess("Dados alterados com sucesso!");
		header('Location: /profile');
		exit;
	});

	$app->get("/order/:idorder", function($idorder) {
		User::verifyLogin(false);
		$order = new Order();
		$order->get((int)$idorder);
		$page = new Page();
		$page->stTpl("payment", [
			'order'=>$order->getValues()
		]);
	});

	$app->get("/boleto/:idorder", function($idorder) {

		User::verifyLogin();
		$order = new Order();
		$order->get((int)$idorder);

		// DADOS DO BOLETO PARA O SEU CLIENTE
		$dias_de_prazo_para_pagamento = 5;
		$taxa_boleto = 4.95;
		$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006";
		$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
		$valor_cobrado = str_replace(".", "", $valor_cobrado);
		$valor_cobrado = str_replace(",", ".",$valor_cobrado);
		$valor_boleto = number_format($valor_cobrado + $taxa_boleto, 2, ',', '');

		$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
		$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
		$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
		$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
		$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
		$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

		// DADOS DO SEU CLIENTE
		$dadosboleto["sacado"] = $order->getdesperson();
		$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
		$dadosboleto["endereco2"] = $order->getdescity() . " - " .$order->getdesstate() . " - " . $order->getdescountry() . " - CEP: " . $order->getdeszipcode();

		// INFORMACOES PARA O CLIENTE
		$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Nonononono";
		$dadosboleto["demonstrativo2"] = "Mensalidade referente a nonon nonooon nononon<br>Taxa bancária - R$ ".number_format($taxa_boleto, 2, ',', '');
		$dadosboleto["demonstrativo3"] = "BoletoPhp - http://www.boletophp.com.br";
		$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
		$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
		$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: lojaedsolique@gmil.com";
		$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja EdSolique E-commerce - www.lojaedsolique.com";

		// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
		$dadosboleto["quantidade"] = "001";
		$dadosboleto["valor_unitario"] = $valor_boleto;
		$dadosboleto["aceite"] = "";
		$dadosboleto["especie"] = "R$";
		$dadosboleto["especie_doc"] = "DS";

		// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //

		// DADOS DA SUA CONTA - BRADESCO
		$dadosboleto["agencia"] = "1281"; // Num da agencia, sem digito
		$dadosboleto["agencia_dv"] = "5"; // Digito do Nm da agencia
		$dadosboleto["conta"] = "0504758";	// Num da conta, sem digito
		$dadosboleto["conta_dv"] = "7"; 	// Digito do Num da conta

		// DADOS PERSONALIZADOS - Bradesco
		$dadosboleto["conta_cedente"] = "0102003"; // ContaCedente do Cliente, sem digito (Somente Números)
		$dadosboleto["conta_cedente_dv"] = "4"; // Digito da ContaCedente do Cliente
		$dadosboleto["carteira"] = "06";  // Código da Carteira: pode ser 06 ou 03

		// SEUS DADOS
		$dadosboleto["identificacao"] = "loja EdSolique";
		$dadosboleto["cpf_cnpj"] = "113.457.188.70";
		$dadosboleto["endereco"] = "Rua Pearci Pael Castro, 287 - Almesinda Costa Souza, 79750-000";
		$dadosboleto["cidade_uf"] = "Nova Andradina - MS";
		$dadosboleto["cedente"] = "EDSOLIQUE LTDA - ME";

		// NÃO ALTERAR!
		$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

		require_once($path . "funcoes_bradesco.php");
		require_once($path . "layout_bradesco.php");
	
	});

	$app->get("/profile/orders", function() {
		User::verifyLogin(false);
		$user = User::getFromSession();
		$page = new Page();
		$page->setTpl("profile-orders", [
			'orders'=>$user->getOrders()
		]);
	});

	$app->get("/profile/orders/:idorder", function($idorder) {
		User::verifyLogin(false);
		$order = new Order();
		$order->get((int)$idorder);
		$cart = new Cart();
		$cart->get((int)$order->getidcart());
		$cart->getCalculateTotal();
		$page = new Page();
		$page->setTpl("profile-orders-datail", [
			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()
		]);
	});

	$app->get("/profile/change-password", function() {
		User::verifyLogin(false);
		$page = new Page();
		$page->setTpl("profile-change-password", [
			'changePassError'=>User::getError(),
			'changePassSuccess'=>User::getSuccess()
		]);
	});

	$app->post("/profile/change-password", function() {
		User::verifyLogin(false);

		if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {
			User::setError("Digite a senha atual.");
			header("Location: /profile/change-password");
			exit;
		}

		if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {
			User::setError("Digite a nova senha.");
			header("Location: /profile/change-password");
			exit;
		}

		if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {
			User::setError("Confirme a nova senha.");
			header("Location: /profile/change-password");
			exit;
		}

		if ($_POST['current_pass'] === $_POST['new_pass']) {
			User::setError("A sua nova senha deve ser diferente da atual.");
			header("Location: /profile/change-password");
			exit;
		}

		$user = User::getFromSession();
			if (!password_verify($_POST['current_pass'], $user->getdespassword())) {

					User::setError("A senha está inválida.");
					header("Location: /profile/change-password");
					exit;
			}

			$user->setdespassword($_POST['new_pass']);
			$user->update();
			User::setSuccess("Senha alterada com sucesso.");

			header("Location: /profile/change-password");
			exit;
	});

 ?>