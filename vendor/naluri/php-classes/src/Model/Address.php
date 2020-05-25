<?php

	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.	

	//Ao invés de criar em cada classe metodos get e set, foi decidido criar uma classe capaz de gerar esses métodos mágicos, por isso a classe herda de Model.
	class Address extends Model
	{	

		const SESSION_ERROR = "AddressError"; 

		public static function getCep($nrcep)
		{

			$nrcep = str_replace("-", "", $nrcep);

			//http://viacep.com.br/ws/01001000/json/

			//inciciar, rastrear uma url
			$ch = curl_init();

			//depois de iniciar o curl_init, precisamos passar as opoções pela qual iremos fazer as chamadas.
			curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");

			//vamos passar mais duas opoções, a primeira para saber se tem que devolvar...true, estou esperando que você retorne.
			//é um processo assincrono.
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			//aqui verificamos se vai exigir alguma autenticação SSL.
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			//capturamos o retorno da variável $ch e atribuimos para a variável $data.
			//utilizamos a função json_decode e passamos true para serialiar, para vir como array e não como objeto.
			$data = json_decode(curl_exec($ch), true);

			//é um ponteiro, então precisamos fechar, se não fechar a cada chamada do método vai se abrir mais referencia na memória...
			curl_close($ch);

			//retornamos para ser utilizado por outros...
			return $data;
		}


		//médodo responsavel por captuar dados que veio do webservice e atribuir para o objeto.
		//responsável por carregar o endereço com os campos certos.
		//pq os nomes que vem do webservice é diferente do padrão que temos no bando de dados. 
		//vamos fazer a conversão de nomes.
		public function loadFromCEP($nrcep)
		{

			$data = Address::getCep($nrcep);

			$this->setdesaddress($data['logradouro']);
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);
		}

		public function save()
		{

			$sql = new Sql();

			$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [

				':idaddress'=>$this->getidaddress(),
				':idperson'=>$this->getidperson(),
				':desaddress'=>utf8_decode($this->getdesaddress()), 
				':desnumber'=>$this->getdesnumber(),
				':descomplement'=>utf8_decode($this->getdescomplement()), 
				':descity'=>utf8_decode($this->getdescity()),
				':desstate'=>utf8_decode($this->getdesstate()), 
				':descountry'=>utf8_decode($this->getdescountry()), 
				':deszipcode'=>$this->getdeszipcode(),
				':desdistrict'=>utf8_decode($this->getdesdistrict())
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

		//método responsável por setar uma mensagem de erro na sessão.
		public static function setMsgError($msg)
		{

			$_SESSION[Address::SESSION_ERROR] = $msg;

		}

		//método responsável por pegar a mensagem de erro na sessão.
		public static function getMsgError()
		{

			$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";

			Address::clearMsgError();

			return $msg;

		}

		//método responsável por limpar o erro da sessao.
		public static function clearMsgError()
		{

			$_SESSION[Address::SESSION_ERROR] = NULL;

		}
		
	}

?>