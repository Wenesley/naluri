<?php
	
	//está no namespace principal
	namespace Naluri;

	//Classe reponsável por gerar getters e setters automaticamente.
	class Model
	{	
		//Atributo vai ter tos os valores dos campos tem temos no nosso objeto. Ex. Usuário, todos os campos de usuário.
		private $values = [];

		//método mágico, recebe dois parametros, o name (nome do método que foi chamado, set ou get), e args (argumentos passados, parametros).
		public function __call($name, $args)
		{
			//a variável recebe as 3 primeira posições da variável $name, através da função substr.
			//começa na posição 0 e termina na posição 2. São as 3 primeiras posições.
			$method = substr($name, 0, 3);

			//a variável $fieldName armazena o valor da variável $name sem as três primeiras prosições, descarta os 3 primeiros e pega o restante.
			//substr traz o valor de uma string a partir, ou até... ou seja começa na posição 3.
			//strlen conta a quantidade de caracteres de uma string até o final. 
			$fieldName = substr($name, 3, strlen($name));

			//verifica as 3 primeira posições da variável $name passado na funcao __call().
			switch ($method) {
				case 'get':
					//pega o valor da variável $fieldName
					return $this->values[$fieldName];
					break;

				case 'set':
					//seta (atribui) o valor passado por parametro na variável $args para a variável $fieldName.
					$this->values[$fieldName] = $args[0];
					break;				
			}

		}

		//método responsável por pegar todos os valores das classes de domínio.
		public function getValues($data = array())
		{

			return $this->values;

		}

		//método responsável por settar todos os valores das classes de domínio.
		public function setValues($data = array())
		{

			foreach ($data as $key => $value) {
				//tudo que for criar dinamicamente no php tem que colocar entre chaves {}.
				//{"set".$key} = esse é o nome do metodo.
				//($value) = valor dos campos
				$this->{"set".$key}($value);
			}

		}
		
		
	}

?>