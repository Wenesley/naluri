<?php

	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.	

	//Ao invés de criar em cada classe metodos get e set, foi decidido criar uma classe capaz de gerar esses métodos mágicos, por isso a classe herda de Model.
	class Category extends Model
	{	
		//método responsável por listar todos as categorias do banco de dados.
		public static function listAll()
		{
			//cria objeto Sql().
			$sql = new Sql();

			//precisamos juntar as tabelas users e persons para trazer todos os dados. Retorna para a chamada da rota.
			return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
		}

		public function save()
		{
			$sql = new Sql();

			//nessa procedure ela já insere no banco de dados, e já busca, como esse idcategory não existe no array precisamos fazer uma validação.
			//como ela tenta atualizar primeiro, temo que validar o getValue na classe model.
			$results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
				":idcategory"=>$this->getidcategory(),
				":descategory"=>$this->getdescategory()
			));

			$this->setValues($results[0]);

			//atualiza as categorias na página principal do site (loja).
			Category::updateFile();
		}

		public function get($idcategory){

			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", [
				":idcategory"=>$idcategory
			]);

			$this->setValues($results[0]);
		}

		public function delete()
		{
			$sql = new Sql();

			$sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", [
				':idcategory'=>$this->getidcategory()
			]);

			//atualiza as categorias na página principal do site (loja).
			Category::updateFile();
		}

		//método responsável por atualizar as categorias na página principal da loja.
		//as categorias são atualizada à medida que cadastramos, editamos, ou excluimos uma categoria na parte administrativa.
		public static function updateFile()
		{
			//tras todas as categorias que estão no banco de dados, e atribui à variável $categories.
			$categories = Category::listAll();

			//precisamos montar nosso html, categories-menu.html.
			//preciso criara varias tags "li". ("<li><a href="#">Categoria Um</a></li>")
			$html = [];
			//cada resgistro que vem do banco de dados vamos chamar de "$row"
			foreach ($categories as $row) {
				//adiciona dados dentro do array.
				//precisamos também da rota "/category/'.$row['idcategory']".
				array_push($html, '<li><a href="/categories/'.$row['idcategory'].'">'.$row['descategory'].'</a></li>');
			}

			//precisamos salvar esse arquivo, receber como parametros o caminho do arquivo, e o conteúdo.
			//como o conteudo "$html" é um array precisamos fazer um implode. 
			file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html" , implode('', $html));
		}

		//método responsável por trazer todos os produtos, ou somente os produtos por categoria.
		public function getProducts($related = true)
		{

			$sql = new Sql();

			if($related === true)
			{
				return $sql->select("
					SELECT * FROM tb_products WHERE idproduct IN(
						SELECT a.idproduct
						FROM tb_products a
						INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
						WHERE b.idcategory = :idcategory
					);

				", [
					':idcategory'=>$this->getidcategory()
				]);

			}
			else 
			{
				return $sql->select("
					SELECT * FROM tb_products WHERE idproduct NOT IN(
						SELECT a.idproduct
						FROM tb_products a
						INNER JOIN tb_productscategories b ON a.idproduct = b.idproduct
						WHERE b.idcategory = :idcategory
					);

				", [
					':idcategory'=>$this->getidcategory()
				]);

			}

		}


		//método reponsável por adcionar um produto na categoria específica
		public function addProduct(Product $product)
		{

			$sql = new Sql();

			$sql->query("INSERT INTO tb_productscategories (idcategory, idproduct) VALUES (:idcategory, :idproduct)", [
				':idcategory'=>$this->getidcategory(),
				':idproduct'=>$product->getidproduct()
			]);
		}	

		//método reponsável por remover um produto na categoria específica
		public function removeProduct(Product $product)
		{

			$sql = new Sql();

			$sql->query("DELETE FROM tb_productscategories WHERE idcategory = :idcategory AND idproduct = :idproduct", [
				':idcategory'=>$this->getidcategory(),
				':idproduct'=>$product->getidproduct()
			]);
		}		

	}

?>