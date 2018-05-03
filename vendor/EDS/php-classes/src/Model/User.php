<?php 
namespace EDS\Model;
use \EDS\DB\Sql;
use \EDS\Model;
use \EDS\Mailer;

class User extends Model {

	const SESSION = "User";
	const SECRET = "Edsolique_Secret";

	//function getFromSession
	public static function getFromSession()
	{
		$user = new User();
		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0) {			
			$user->setData($_SESSION[User::SESSION]);
			return $user;
		}
		return $user;
	}

	public static function checkLogin($inadmin = true)
	{
		if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;
		} else {
			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
				return true;
			} else if ($inadmin === false) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	//function login
	public static function login($login, $password)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.deslogin = :LOGIN", array(
			":LOGIN"=>$login
		)); 
		if (count($results) === 0)
		{
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}
		$data = $results[0];
		if (password_verify($password, $data["despassword"]) === true)
		{
			$user = new User();
			$user->setData($data);
			$_SESSION[User::SESSION] = $user->getValues();
			return $user;
		} else {
			throw new \Exception("Usuário inexistente ou senha inválida.");
		}
	}

	//function verifyLogin
	public static function verifyLogin($inadmin = true)
	{
		if (User::checkLogin($inadmin)) {
			
			header("Location: /admin/login");
			exit;
		}
	}
	
	//function logout
	public static function logout()
	{
		$_SESSION[User::SESSION] = NULL;
	}

	//function listAll
	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
	}

	//function save
	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));
		$this->setData($results[0]);
	}

	//function get ($iduser)
	public function get($iduser)
	{
	 
	 $sql = new Sql();
	 
	 $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser;", array(
	 ":iduser"=>$iduser
	 ));
	 
	 $data = $results[0];
	 
	 $this->setData($data);
	 
	 }

	//function update
	public function update()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));
		$this->setData($results[0]);		
	}

	//function delete
	public function delete()
	{
		$sql = new Sql();
		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));
	}

	//Function getForgot
	public static function getForgot($email, $inadmin = true)
	{
	     $sql = new Sql();
	     $results = $sql->select("
	         SELECT *
	         FROM tb_persons a
	         INNER JOIN tb_users b USING(idperson)
	         WHERE a.desemail = :email;
	     ", array(
	         ":email"=>$email
	     ));
	     if (count($results) === 0)
	     {
	         throw new \Exception("Não foi possível recuperar a senha!");
	     }
	     else
	     {
	         $data = $results[0];
	         $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
	             ":iduser"=>$data['iduser'],
	             ":desip"=>$_SERVER['REMOTE_ADDR']
	         ));
	         if (count($results2) === 0)
	         {
	             throw new \Exception("Não foi possível recuperar a senha!");
	         }
	         else
	         {
	             $dataRecovery = $results2[0];
	             $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
	             $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
	             $result = base64_encode($iv.$code);
	             if ($inadmin === true) {
	                 $link = "http://www.edsoliquecommerce.com.br/admin/forgot/reset?code=$result";
	             } else {
	                 $link = "http://www.edsoliquecommerce.com.br/forgot/reset?code=$result";
	             } 
	             $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha de Loja Edsolique", "forgot", array(
	                 "name"=>$data['desperson'],
	                 "link"=>$link
	             )); 
	             $mailer->send();
	             return $link;
	         }
	     }
	 }

	//Function validForgotDecrypt
	public static function validForgotDecrypt($result)
	 {
	     $result = base64_decode($result);
	     $code = mb_substr($result, openssl_cipher_iv_length('aes-256-cbc'), null, '8bit');
	     $iv = mb_substr($result, 0, openssl_cipher_iv_length('aes-256-cbc'), '8bit');;
	     $idrecovery = openssl_decrypt($code, 'aes-256-cbc', User::SECRET, 0, $iv);
	     $sql = new Sql();
	     $results = $sql->select("
	         SELECT *
	         FROM tb_userspasswordsrecoveries a
	         INNER JOIN tb_users b USING(iduser)
	         INNER JOIN tb_persons c USING(idperson)
	         WHERE
	         a.idrecovery = :idrecovery
	         AND
	         a.dtrecovery IS NULL
	         AND
	         DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
	     ", array(
	         ":idrecovery"=>$idrecovery
	     ));
	     if (count($results) === 0)
	     {
	         throw new \Exception("Não foi possível recuperar a senha!");
	     }
	     else
	     {
	         return $results[0];
	     }
	 }
	//function setForgotUsed
	public static function setForgotUsed($idrecovery)
	{
		$sql = new Sql();
		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));
	}


	// //function getPasswordHash
	// public static function getPasswordHash($password)
	// {
	// 	return password_hash($password, PASSWORD_DEFAULT, [
	// 		'cost'=>12
	// 	]);
	// }

	//function setPassword
	public function setPassword($password)
	{
		$sql = new Sql();
		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>User::getPasswordHash($password),
			":iduser"=>$this->getiduser()
		));
	}

	//function checkLoginExist
	public static function checkLoginExist($login):bool
	{
		$sql = new Sql();
		$result = $sql->select("SELECT COUNT(*) AS nrtotal FROM tb_users WHERE deslogin = :login", [
			':login'=>$login
		]);
		return ((int)$result[0]['nrtotal'] > 0);
	}

	//function getOrders
	public function getOrders()
	{
		$sql = new Sql();
		return $sql->select("
			SELECT * 
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.iduser = :iduser
			ORDER BY a.dtregister DESC
		", [
			':iduser'=>$this->getiduser()
		]);
	}
}
 ?>