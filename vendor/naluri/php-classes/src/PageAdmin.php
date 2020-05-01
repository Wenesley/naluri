<?php

	namespace Naluri;

	//Classe responsável por criar o template visual da tela Admin.
	//Esta classe herda todos os atributos e métodos da classe Page.
	class PageAdmin extends Page
	{
		
		public function __construct($opts = array(), $tpl_dir = "/views/admin/") //Parametros = recebe algumas opções (dados) da classe, que vem da rota. Foi acrescentado o parametro $tpl_dir para salvar o template na pasta cache, e encontrar as páginas que está no diretorio /views/admin/.
		{
			parent::__construct($opts, $tpl_dir); //Construtor da Classe Pai invocado (Page), que recebe dois parametros passado no construtor da classe PageAdmin.
		}
	}
?>