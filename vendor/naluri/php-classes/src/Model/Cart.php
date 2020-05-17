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
		const SESSION_ERROR = "CartError"; //não podemos ter constantes com nomes iguais, pois uma sobrescreve a outra.

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

			$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
				':idcart'=>$this->getidcart(),
				':dessessionid'=>$this->getdessessionid(),
				':iduser'=>$this->getiduser(),
				':deszipcode'=>$this->getdeszipcode(),
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

			//responsável por calcular o Total, o Subtotal e atualizar o frete.
			$this->getCalculateTotal();
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

			//responsável por calcular o Total, o Subtotal e atualizar o frete.
			$this->getCalculateTotal();

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

		//método responsável por buscar todos os produtos na tabela tb_cartsproducts e somar para calcular o frete.
		public function getProductsTotals()
		{

			$sql = new Sql();

			$results = $sql->select("
				SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
				FROM tb_products a
				INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
				WHERE b.idcart = :idcart AND dtremoved IS NULL;
				", [
					'idcart'=> $this->getidcart()
				]);

			if(count($results) > 0) {
				return $results[0];
			} else {
				return [];
			}
		}


		//método responsável por se comunicar com VIACEP e calcular o frete.
		public function setFreight($nrzipcode)
		{

			//tira o ifen do CEP.
			$nrzipcode = str_replace('-', '', $nrzipcode);

			//traz os valores totais dos produtos que estão no carrinho.
			$totals = $this->getProductsTotals();

			//verficando se realmente trouxe algum produto do carrinho.
			if($totals['nrqtd'] > 0) {

				//regras validacao para atender requisitos minimos das dimensoes do correio.
				if($totals['vlheight'] < 2 ) $totals['vlheight'] = 2;
				if($totals['vllength'] < 16 ) $totals['vllength'] = 16;

				//vamos passar as variáveis na query string. 
				$qs = http_build_query([
					'nCdEmpresa'=>'',
					'sDsSenha'=>'',
					'nCdServico'=>'40010',
					'sCepOrigem'=>'08226021',
					'sCepDestino'=>$nrzipcode,
					'nVlPeso'=>$totals['vlheight'],
					'nCdFormato'=>'1',
					'nVlComprimento'=>$totals['vllength'],
					'nVlAltura'=>$totals['vlheight'],
					'nVlLargura'=>$totals['vlwidth'],
					'nVlDiametro'=>'0',
					'sCdMaoPropria'=>'S',
					'nVlValorDeclarado'=>$totals['vlprice'],
					'sCdAvisoRecebimento'=>'S'
				]);
				//passando as informações para o webservice dos correios.
				//vamos ultizar uma função que lê xml, pq a webservice vai retornar no formato xml.
				//recebe tanto um caminho fisico de um arquivo, quanto um endereço na internet.
				$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

				//echo json_encode($xml);
				//var_dump($xml);
				//exit;

				//vamos colocar os valores retornado do correios no carrinho de compras.
				//pegamos o resultado do webservice e amarzemanos na variavel $result.
				$result = $xml->Servicos->cServico;

				//verica se foi gerado algum erro no retorno do webservice.
				if($result->MsgErro != '') {

					Cart::setMsgError($result->MsgErro);

				} else {

					Cart::clearMsgError();
				}

				$this->setnrdays($result->PrazoEntrega);
				$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
				$this->setdeszipcode($nrzipcode);

				$this->save();

				return $result;

			} else {

				//não tem nenhum produto no carrinho. Limpa as informações e ou limpa os erros.
			}

		}

		//métod responsavel por formatar moeda.
		public static function formatValueToDecimal($value):float
		{

			$value = str_replace('.', '', $value);
			return str_replace(',', '.', $value);
		}


		//método responsável por setar uma mensagem de erro na sessão.
		public static function setMsgError($msg)
		{

			$_SESSION[Cart::SESSION_ERROR] = $msg;

		}

		//método responsável por pegar a mensagem de erro na sessão.
		public static function getMsgError()
		{

			$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

			Cart::clearMsgError();

			return $msg;

		}

		//método responsável por limpar o erro da sessao.
		public static function clearMsgError()
		{

			$_SESSION[Cart::SESSION_ERROR] = NULL;

		}

		//método responsável por atualizar o frete.
		public function updateFreight()
		{

			//verifica se tem um cep no carrinho.
			if($this->getdeszipcode() != '') {

				//chama o método para recalcular o frete passando o cep que está no carrinho.
				$this->setFreight($this->getdeszipcode());
			}

		}


		//Sobrescreve o método getValues da classe Pai, acrecentando o valor Total, Subtotal e atualizando o frete.
		public function getValues()
		{

			$this->getCalculateTotal();

			return parent::getValues();

		}


		public function getCalculateTotal()
		{

			//atualiza o frete
			$this->updateFreight();

			//pega o somatório do total do carrinho de compras.
			$totals = $this->getProductsTotals();

			$this->setvlsubtotal($totals['vlprice']);

			$this->setvltotal($totals['vlprice'] + $this->getvlfreight());
			
		}
	}	

?>