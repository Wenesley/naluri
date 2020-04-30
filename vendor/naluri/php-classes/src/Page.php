<?php

	namespace Naluri;

	use Rain\Tpl;

	Class Page {

		private $tpl;
		private $options = [];
		private $defaults = [ //recebe opçoes padrões (váriveis que vem da rota)
			"data"=>[]
		];


		public function __construct($opts = array()){ // Parametros = recebe algumas opções (dados) da classe, que vem da rota.

			$this->options = array_merge($this->defaults, $opts); // array_merge - o último sobrescreve o primeiro. (vai mesclar e guardar no atributo)

			$config = array(
				//o template precisa de uma pasta para pegar os arquivos html e uma pasta de cache para salvar temporariamente
				"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/", // A partir do diretório ROOT procure a pasta tal...(views).
				"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
				"debug"         => false // set to false to improve the speed
			);

			Tpl::configure( $config );

			$this->tpl = new Tpl;

			$this->setData($this->options["data"]);			

			$this->tpl->draw("header"); //desenha o template na página header.html. Qual a primeira coisa que vai na Página? (Cabeçalho). Será criado na pasta views.
		}


		//método otimizado, utilizado somente nesta classe. Atribui os dados para o metodo assign do template.
		private function setData($data = array()){

			foreach ($data as $key => $value) {
				$this->tpl->assign($key, $value); //atribui a chave e o valor para a função assign. exp.: Título e Valor
			}

		}

		//Também teremos o conteúdo, o corpo da página, então será criado um método só para ele...
		//recebe os seguintes parametros, 1. qual é o nome do template, 2. quais são os dados, as variáveis que queremos passar, 3. se vc quer quer retorne o hmtl, ou já mostra na tela.
		public function setTpl($name, $data = array(), $returnHTML = false) {

			$this->setData($data); //chama o metodo privado da classe, e atribui os dados (chave e valor) para o metodo assign do template.

			return $this->tpl->draw($name, $returnHTML); //desenha na tela o template que é passado pelo $name, atraves do método draw do template.

		}

		public function __destruct(){
			//quando a página morrer...(sair da memória do php) o cabeçalho será criado o rodapé.
			//iremos fazer o draw do footer.
			$this->tpl->draw("footer");
			
		}
	}
?>