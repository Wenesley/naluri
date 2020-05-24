<?php
	
	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.
	use \Naluri\Model\Cart; //classe responsável por gerar os métodos getters e setters automáticamente.
	

	class Order extends Model
	{

		const SUCCESS = "Order-Success";
		const ERROR = "Order-Error";

		public function save()
		{

			$sql = new Sql();

			$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
				':idorder'=>$this->getidorder(),
				':idcart'=>$this->getidcart(),
				':iduser'=>$this->getiduser(),
				':idstatus'=>$this->getidstatus(),
				':idaddress'=>$this->getidaddress(),
				':vltotal'=>$this->getvltotal()
			]);
			

			if (count($results) > 0) {
				$this->setValues($results[0]);				
			}


			
		}


		


		public function get($idorder)
		{

			$sql = new Sql();

			//ON é utilizado quando eu quero dizer especificamente de qual tabela anteriores eu quero...
			$results = $sql->select("
				SELECT * 
				FROM tb_orders a
				INNER JOIN tb_ordersstatus b USING(idstatus)
				INNER JOIN tb_carts c USING(idcart)
				INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER JOIN tb_persons f ON f.idperson = d.idperson
				WHERE a.idorder = :idorder
			", [

				':idorder'=>$idorder
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		public static function listAll()
		{

			$sql = new Sql();

			//ON é utilizado quando eu quero dizer especificamente de qual tabela anteriores eu quero...
			$results = $sql->select("
				SELECT * 
				FROM tb_orders a
				INNER JOIN tb_ordersstatus b USING(idstatus)
				INNER JOIN tb_carts c USING(idcart)
				INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER JOIN tb_persons f ON f.idperson = d.idperson
				ORDER BY a.dtregister DESC
			");

			return $results;

		}

		public function delete()
		{

			$sql = new Sql();

			$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
				':idorder'=>$this->getidorder()
			]);
		}


		//responsável por retornar o carrinho de compras do pedido. retorna uma instancia da classe Cart.
		//isso pq o pedito já tem um carrinho.
		public function getCart():Cart 
		{

			$cart = new Cart();

			$cart->get((int)$this->getidcart());

			return $cart;

		}

		//método responsável por setar uma mensagem de erro na sessão.
		public static function setSuccess($msg)
		{

			$_SESSION[Order::SUCCESS] = $msg;

		}

		//método responsável por pegar a mensagem de erro na sessão.
		public static function getSuccess()
		{

			$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : "";

			Order::clearSuccess();

			return $msg;

		}

		//método responsável por limpar o erro da sessao.
		public static function clearSuccess()
		{

			$_SESSION[Order::SUCCESS] = NULL;

		}



		//método responsável por setar uma mensagem de erro na sessão.
		public static function setError($msg)
		{

			$_SESSION[Order::ERROR] = $msg;

		}

		//método responsável por pegar a mensagem de erro na sessão.
		public static function getError()
		{

			$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : "";

			Order::clearError();

			return $msg;

		}

		//método responsável por limpar o erro da sessao.
		public static function clearError()
		{

			$_SESSION[Order::ERROR] = NULL;

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
				FROM tb_orders a
				INNER JOIN tb_ordersstatus b USING(idstatus)
				INNER JOIN tb_carts c USING(idcart)
				INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER JOIN tb_persons f ON f.idperson = d.idperson 
				ORDER BY a.dtregister DESC
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
				FROM tb_orders a 
				INNER JOIN tb_ordersstatus b USING(idstatus)
				INNER JOIN tb_carts c USING(idcart)
				INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER JOIN tb_persons f ON f.idperson = d.idperson 
				WHERE a.idorder = :idorder OR f.desperson LIKE :search
				ORDER BY f.desperson DESC
				LIMIT $start, $itemsPerPage;
			", [
				':search'=>'%'.$search.'%',
				':idorder'=>$search

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