<?php

	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.	
	use \Naluri\Model\User;
	use \Naluri\Model\Product;
	//Ao invés de criar em cada classe metodos get e set, foi decidido criar uma classe capaz de gerar esses métodos mágicos, por isso a classe herda de Model.
	class Cart extends Model
	{	

		const SESSION = "CartSession";

		public static function getFromSession()
		{

			$cart = new Cart();

			//verificar se o carrinho já está na sessão. Caso exista vamos buscar com o get.
			if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
				$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
			} else {

				//Se não existir, vamos buscar no banco com os dados do campo (dessessionid) da tabela carts.
				$cart->getFromSessionID();

				if(!(int)$cart->getidcart() > 0) {

					$data = [
						'dessessionid'=>session_id()
					];

					if(User::checkLogin(false)) {
						//responsável por verificar o usuario logado, para possívelmente lembrá-lo do carrinho...
						$user = User::getFromSession();

						$data['iduser'] = $user->getiduser();
					}

					$cart->setValues($data);

					$cart->save();

					$cart->setToSession();					
				}
			}

			return $cart;
		}


		//método responsável por colocar o carrinho na sessão.
		public function setToSession()
		{

			$_SESSION[Cart::SESSION] = $this->getValues();
		}

		public function getFromSessionID()
		{

			$sql = new Sql();

			$results = $sql->select("SELECT * FROM  tb_carts WHERE dessessionid = :dessessionid", [
				':dessessionid'=>session_id()
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}			
		}

		public function get(int $idcart)
		{

			$sql = new Sql();

			$results = $sql->select("SELECT * FROM  tb_carts WHERE idcart = :idcart", [
				':idcart'=>$idcart
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}			
		}
		
		public function save()
		{

			$sql = new Sql();

			$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipecode, :vlfreight, :nrdays)", [
				':idcart'=>$this->getidcart(),
				':dessessionid'=>$this->getdessessionid(),
				':iduser'=>$this->getiduser(),
				':deszipecode'=>$this->getdeszipecode(),
				':vlfreight'=>$this->getvlfreight(),
				':nrdays'=>$this->getnrdays()
			]);

			$this->setValues($results[0]);
		}

		//método responsável por adicionar um produto no carrinho de compras.
		public function addProduct(Product $product)
		{

			$sql = new Sql();

			$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", [
				'idcart'=>$this->getidcart(),
				'idproduct'=>$product->getidproduct()
			]);
		}

		//Método responsável por remover o produto do carrinho de compras.
		//Pode remover um ou todos os produtos do carrinho de compras.
		public function removeProduct(Product $product, $all = false)
		{

			$sql = new Sql();

			if($all){

				$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
					':idcart'=>$this->getidcart(),
					'idproduct'=>$product->getidproduct()
				]);

			} else {

				$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
					':idcart'=>$this->getidcart(),
					'idproduct'=>$product->getidproduct()
				]);

			}

		}


		//método responsável por pegar(listar) todos os produtos que estão no carrinho de compras.	
		public function getProducts()
		{

			$sql = new Sql();

			$rows = $sql->select("
				SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal
				FROM tb_cartsproducts a
				INNER JOIN tb_products b ON a.idproduct = b.idproduct
				WHERE a.idcart = :idcart AND a.dtremoved IS NULL
				GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
				ORDER BY b.desproduct
				", [
					':idcart'=>$this->getidcart()
				]);

			return Product::checkList($rows);

		}
	}

?>