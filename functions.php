<?php
	
	use \Naluri\Model\User;
	use \Naluri\Model\Cart;
	
	//São funções para serem utilizadas nos templates, como escopo global.
	function formatPrice($vlprice)
	{
		//Validação para quando o carrinho estiver vazio, para não gerar erro.
		if(!$vlprice > 0) $vlprice = 0;
		
		return number_format($vlprice, 2, ",", ".");
		
	}


	function checkLogin($inadmin = true)
	{

		return User::checkLogin($inadmin);

	}

	//função paara pegar o nome do usuário na sessão.
	function getUserName()
	{
		
		$user = User::getFromSession();			
	
		return $user->getdesperson();

	}

	//função para calcular a quantidade de itens no carrinho.
	function getCartNrQtd()
	{

		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		return $totals['nrqtd'];
	}

	//função para calcular o valor do carrinho de compras.
	function getCartVlSubTotal()
	{

		$cart = Cart::getFromSession();

		$totals = $cart->getProductsTotals();

		return formatPrice($totals['vlprice']);
	}

?>