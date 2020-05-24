<?php

	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.	

	//Ao invés de criar em cada classe metodos get e set, foi decidido criar uma classe capaz de gerar esses métodos mágicos, por isso a classe herda de Model.
	class Product extends Model
	{	
		//método responsável por listar todos as categorias do banco de dados.
		public static function listAll()
		{
			//cria objeto Sql().
			$sql = new Sql();

			//precisamos juntar as tabelas users e persons para trazer todos os dados. Retorna para a chamada da rota.
			return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
		}

		//Faz checkList no produto para listar as imagens na loja.
		public static function checkList($list)
		{
			//manipula $row na memoria
			foreach ($list as &$row) {
				$p = new Product();
				$p->setValues($row);
				$row = $p->getValues();
			}

			return $list;
		}

		public function save()
		{
			$sql = new Sql();

			//nessa procedure ela já insere no banco de dados, e já busca, como esse idcategory não existe no array precisamos fazer uma validação.
			//como ela tenta atualizar primeiro, temo que validar o getValue na classe model.
			$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(				
				":idproduct"=>$this->getidproduct(),
				":desproduct"=>$this->getdesproduct(),
				":vlprice"=>$this->getvlprice(),
				":vlwidth"=>$this->getvlwidth(),
				":vlheight"=>$this->getvlheight(),
				":vllength"=>$this->getvllength(),
				":vlweight"=>$this->getvlweight(),
				":desurl"=>$this->getdesurl()
			));

			$this->setValues($results[0]);			
		}

		//método responsável por pegar um produto específico.
		public function get($idproduct){

			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
				":idproduct"=>$idproduct
			]);

			$this->setValues($results[0]);
		}

		public function delete()
		{
			$sql = new Sql();

			$sql->query("DELETE FROM tb_products WHERE idproduct = :idproduct", [
				':idproduct'=>$this->getidproduct()
			]);			
		}

		//Verifica se tem foto para atribuir para o objeto, e então exibí-la no formulario.
		public function checkPhoto()
		{
			if(file_exists(
				$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
				"res" . DIRECTORY_SEPARATOR .
				"site" . DIRECTORY_SEPARATOR .
				"img" . DIRECTORY_SEPARATOR .
				"products" . DIRECTORY_SEPARATOR .
				$this->getidproduct() . "jpg" 
			))
			{
				$url = "/res/site/img/products/" . $this->getidproduct() . "jpg";
			}
			else
			{
				$url = "/res/site/img/product.jpg";
			}

			return $this->setdesphoto($url);
		}

		//sobrescrevendo o metodo getValues da Classe pai.
		public function getValues() 
		{
			//chama método para verificar se possui foto.
			$this->checkPhoto();

			$values = parent::getValues();

			return $values;
		}

		//Salva a foto
		public function setPhoto($file)
		{
			$extension = explode(".", $file["name"]);

			$extension = end($extension);

			switch ($extension) {
				case 'jpg':
				case 'jpeg':
					$image = imagecreatefromjpeg($file["tmp_name"]);
					break;
				
				case 'gif':
					$image = imagecreatefromgif($file["tmp_name"]);
					break;

				case 'png':
					$image = imagecreatefrompng($file["tmp_name"]);
					break;
			}

			$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
					"res" . DIRECTORY_SEPARATOR .
					"site" . DIRECTORY_SEPARATOR .
					"img" . DIRECTORY_SEPARATOR .
					"products" . DIRECTORY_SEPARATOR .
					$this->getidproduct() . "jpg";

			imagejpeg($image, $dist);

			imagedestroy($image);

			$this->checkPhoto();
		}

		//método responsável por buscar todos os produtos pelo campo desurl.
		public function getFromURL($desurl)
		{

			$sql = new Sql();

			$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
				':desurl'=>$desurl			
			]);

			$this->setValues($rows[0]);
		}	


		//método responsável por buscar a categoria em que o produto está relacionado 
		//para detalhes do produdo.
		public function getCategories()
		{

			$sql = new Sql();

			return $sql->select("
				SELECT * FROM tb_categories a  
				INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory 
				WHERE b.idproduct = :idproduct", [
					':idproduct'=>$this->getidproduct()
			]);
		}

		//método responsável pela panginação dos produtos.
		//recebe dois parametros, qual página estamos e quantos item queremos por página.
		public static function getPage($page = 1, $itemsPerPage = 10)
		{

			$start = ($page - 1) * $itemsPerPage;

			$sql = new Sql();

			//busca todos os produtos por categoria.
			$results = $sql->select("
				SELECT SQL_CALC_FOUND_ROWS *
				FROM tb_products 
				ORDER BY desproduct			
				LIMIT $start, $itemsPerPage;			
			");

			//retorna a quantidade de items que tem.
			$resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

			//retorna todos os produtos(dados dos produtos), a quantidade linhas(quantos registros vieram)
			//e a quantidade de páginas. (ceil função php que arredonda para cima)
			return [
				'data'=>$results,
				'total'=>$resultsTotal[0]["nrtotal"],
				'pages'=>ceil($resultsTotal[0]["nrtotal"] / $itemsPerPage)				
			];

		}


		//método responsável pela panginação dos produtos.
		//recebe dois parametros, qual página estamos e quantos item queremos por página.
		public static function getPageSearch($search, $page = 1, $itemsPerPage = 10)
		{

			$start = ($page - 1) * $itemsPerPage;

			$sql = new Sql();

			//busca todos os produtos por categoria.
			$results = $sql->select("
				SELECT SQL_CALC_FOUND_ROWS *
				FROM tb_products 							 
				WHERE desproduct LIKE :search 
				ORDER BY desproduct
				LIMIT $start, $itemsPerPage;			
			", [
				':search'=>'%'.$search.'%'

			]);

			//retorna a quantidade de items que tem.
			$resultsTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

			//retorna todos os produtos(dados dos produtos), a quantidade linhas(quantos registros vieram)
			//e a quantidade de páginas. (ceil função php que arredonda para cima)
			return [
				'data'=>$results,
				'total'=>$resultsTotal[0]["nrtotal"],
				'pages'=>ceil($resultsTotal[0]["nrtotal"] / $itemsPerPage)				
			];

		}

	}

?>