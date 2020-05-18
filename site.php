<?php

use \Naluri\Page;
use \Naluri\Model\Product;
use \Naluri\Model\Category;
use \Naluri\Model\Cart;
use \Naluri\Model\Address;
use \Naluri\Model\User;


$app->get('/', function() { //qual o link da página index da loja. Raiz principal.

	$Products = Product::listAll();
    
	$page = new Page(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index", [
		"products"=>Product::checkList($Products)
	]); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});

//criar uma rota para exibir os produtos por categoria, e a paginação.
$app->get("/categories/:idcategory", function($idcategory) {
	
	$numberOfPage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($numberOfPage);

	$pages = [];

	for ($i=1; $i <= $pagination['pages'] ; $i++) { 
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}

	//nesse caso não chamamos uma página, mas um template. Então só precisamos do page.
	$page = new Page();	

	//chamar somete o template dessa categoria, e vamos passar os dados dessa categoria, para mostrar lá dentro.
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);

});

$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

$app->get("/cart", function(){

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);

});

$app->get("/cart/:idproduct/add", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	//recupera ou cria um carrinho na sessão.
	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i=0; $i < $qtd; $i++) { 

		//adiciona o produto dentro do carrinho(que foi recuperado ou criado).
		$cart->addProduct($product);
	}	

	header("Location: /cart");
	exit;

});

//responsável por remover apenas um produto do carrinho.
$app->get("/cart/:idproduct/minus", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	//recupera ou cria um carrinho na sessão.
	$cart = Cart::getFromSession();

	//adiciona o produto dentro do carrinho(que foi recuperado ou criado).
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});


//responsável por remover todos os produto do carrinho.
$app->get("/cart/:idproduct/remove", function($idproduct) {

	$product = new Product();

	$product->get((int)$idproduct);

	//recupera ou cria um carrinho na sessão.
	$cart = Cart::getFromSession();

	//adiciona o produto dentro do carrinho(que foi recuperado ou criado).
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});


//rota responsavel por calcular o frete e retornar com o valor.
$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;

});


//rota responsável pela requisição para finalizar compra.
$app->get("/checkout", function(){

	//para finalizar a compra, deve está logado na loja.
	//Não é uma rota da Administração.
	User::verifyLogin(false);

	//pega o carrinho de compras da sessão.
	$cart = Cart::getFromSession();

	$address = new Address();
	
	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues()
	]);
});


//rota responsável pela requisição para logar na loja.
$app->get("/login", function(){	
	
	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getError(), //passando o erro para o template.
		'errorRegister'=>User::getErrorRegister(), //passando o erro para o template.
		//passa os dados digitados para o template, para não perder caso gere error.
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);
});


//rota responsável por logar e redirecionar para checkout.
$app->post("/login", function(){	
	
	try {

		User::login($_POST['login'], $_POST['password']);

	}catch(Exception $e) {

		User::setMsgError($e->getMessage());
	}

	header("Location: /checkout");
	exit;
});


//rota responsável por deslogar e redirecionar para tela de login.
$app->get("/logout", function(){	

	User::logout();

	header("Location: /login");
	exit;	
});


$app->post("/register", function(){

	//guardar os dados digitados no template, para não perder quando gerar error no template.
	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['name']) || $_POST['name'] == '') {

		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['email']) || $_POST['email'] == '') {

		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['password']) || $_POST['password'] == '') {

		User::setErrorRegister("Preencha a senha.");
		header("Location: /login");
		exit;
	}

	if (User::checkLoginExist($_POST['email']) === true) {
		
		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setValues([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");
	exit;
});



?>