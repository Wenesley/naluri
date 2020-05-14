<?php

use \Naluri\PageAdmin;
use \Naluri\Model\Category;
use \Naluri\Model\User;
use \Naluri\Model\Product;

//rota responsável por abrir a página categories.html
$app->get("/admin/categories", function(){

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	//Precisamos criar a classe e o método que listará todas as categorias, e atribuir à variável $categories, para preencher o segundo parametro (dados, variaveis) do metodo setTpl, para enviar para o template categories.html.
	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories", [
		'categories'=>$categories //Exite um loop no template, então precisamos chamar a variável e criar uma lista para precher o loop.

	]);//inclui o template no metodo setTpl para abir a página.
});

//rota responsável por abrir a página categories-create.html
$app->get("/admin/categories/create", function(){	

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");
});

//rota responsável enviar os dados de cartgorias para o bandco de dados.
$app->post("/admin/categories/create", function(){	

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	//cria o objeto categoria.
	$category = new Category();

	//pega o nome da categoria para salvar no banco.
	$category->setValues($_POST);

	//salva a categoria, e busca a ultima categoria salva, e atuaiza o objeto, listando na página categories.
	$category->save();

	header('Location: /admin/categories');

	exit;
});

//rota resposavel por pegar o idcategory via post e excluir os dados no banco de dados.
$app->get("/admin/categories/:idcategory/delete", function($idcategory) {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	//cria o objeto categoria.
	$category = new Category();

	//verifica se existe a categoria, e a busca para exclusão.
	$category->get((int)$idcategory); 

	$category->delete();

	header('Location: /admin/categories');

	exit;
});


//rota para atualizar/update a categoria pelo idcategory passado. 
//rota para buscar os dados da categoria no banco de dados e preencher os campos da página category-updade.html. 
//acesando a rota "/admin/category/:idcategory", vc está solicitando os dados de uma categoria específica, que estamos recebendo como paramentro na funcao.
//queremos que tenha o header e o footer, e o usuário precisa está logado.
$app->get("/admin/categories/:idcategory", function($idcategory) { //o valor que recebemos no parametro é atribuido para rota "/admin/category/:idcategory".

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    //cria um usuario.
    $category = new Category();

    //pega o id da cateoria. Temos que criar o método get na classe categoria.
    $category->get((int)$idcategory);

	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("categories-update", array(
		//"category" -> é o nome que já está no template.
		"category"=>$category->getValues() //segundo parametro pega todos os dados do objeto.

	));//quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página category-update.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});

//rota que recebe os dados de que foram alterados da página categories-update.html para salvar no banco de dados.
$app->post("/admin/categories/:idcategory", function($idcategory) {
	
	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    $category = new Category();
    
    //busca os dados da categoria no banco de dados.
    $category->get((int)$idcategory);

    //pega os dados digitados na págnia categories-create.html e atribui os valores para o método setValues responsável por criar setar as variaveis do objeto. Só falta criar um método para salvar no banco de dados.
    //recebe os novos dados do fomulário se seta no objeto.
    $category->setValues($_POST);

    //metodo para alterar os dados no banco de dados, uma vez que essa procedute primeisa tenta alterar os dados, se não ela insere no banco de dados.
    $category->save();

    header("Location: /admin/categories");

    exit;

});


//criar uma rota para categoria
$app->get("/admin/categories/:idcategory/products", function($idcategory) {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	//nesse caso não chamamos uma página, mas um template. Então só precisamos do page.
	$page = new PageAdmin();

	//chamar somete o template dessa categoria, e vamos passar os dados dessa categoria, para mostrar lá dentro.
	$page->setTpl("categories-products", [
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(),
		'productsNotRelated'=>$category->getProducts(false)
	]);

});


//criar uma rota para categoria
$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct) {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

//criar uma rota para categoria
$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct) {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;

});

?>