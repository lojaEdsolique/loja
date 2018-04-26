<?php 
namespace EDS\Model;
use \EDS\DB\Sql;
use \EDS\Model;
use \EDS\Mailer;

class Category extends Model {

	public static function listAll()
	{
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
	}

	public function save()
	{
		$sql = new Sql();
		$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
			":idcategory"=>$this->getidcategory(),
			":descategory"=>$this->getdescategory(),
		));

		$this->setData($results[0]);
	}

	public function get($idcategory)
	{
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_categorias WHERE idcategory = :idcategory", [
			':idcategory'=>$idcategory
		]);
		
		$this->setData($results[0]);
	}

	public function delete()
	{
		$sql = new Sql();
		$sql->query("DELETE FROM tb_categorias WHERE idcategory = :idcategory", [
			':idcategory'=>$this->getidcategory()
		]);
	}
}

 ?>