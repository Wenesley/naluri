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
	}

?>