<?php
	
	use \Naluri\Model\User;
	
	//São funções para serem utilizadas nos templates, como escopo global.
	function formatPrice(float $vlprice)
	{

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

?>