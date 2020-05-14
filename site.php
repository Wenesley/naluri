<?php

use \Naluri\Page;
use \Naluri\Model\Product;
use \Naluri\Model\Category;

$app->get('/', function() { //qual o link da página index da loja. Raiz principal.

	$Products = Product::listAll();
    
	$page = new Page(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index", [
		"products"=>Product::checkList($Products)
	]); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});

//criar uma rota para categoria
$app->get("/categories/:idcategory", function($idcategory) {
	
	$category = new Category();

	$category->get((int)$idcategory);

	//nesse caso não chamamos uma página, mas um template. Então só precisamos do page.
	$page = new Page();

	//chamar somete o template dessa categoria, e vamos passar os dados dessa categoria, para mostrar lá dentro.
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>Product::checkList($category->getProducts())
	]);

});


?>