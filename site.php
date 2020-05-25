<?php

use \Naluri\Page;
use \Naluri\Model\Product;
use \Naluri\Model\Category;
use \Naluri\Model\Cart;
use \Naluri\Model\Address;
use \Naluri\Model\User;
use \Naluri\Model\Order;
use \Naluri\Model\OrderStatus;


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

	//pega o usuário da sessão da loja.
	$user = User::getFromSession();


	//pega o carrinho de compras da sessão.
	$cart = Cart::getFromSession();

	$address = new Address();


	if(isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();
	}


	if(isset($_GET['zipcode'])) {

		//nesse momento já temos o endereço carregados e atualizados.
		$address->loadFromCEP($_GET['zipcode']);

		//caso o cliente tenha mudado o cep, tiver enviado no $_GET. 
		//no endereço, precisamos colocá-lo no carrinho de compracas e recaulcular o frete.
		$cart->setdeszipcode($_GET['zipcode']);		
	
		//responsável por verificar o usuario logado, para possívelmente lembrá-lo do carrinho...
		$cart->setiduser($user->getiduser());		

		//salva o carrinho.
		$cart->save();

		//calcula novamente.
		$cart->getCalculateTotal();
	}
	

	if(!$address->getdesaddress()) $address->setdesaddress('');
	if(!$address->getdesnumber()) $address->setdesnumber('');
	if(!$address->getdescomplement()) $address->setdescomplement('');
	if(!$address->getdesdistrict()) $address->setdesdistrict('');
	if(!$address->getdescity()) $address->setdescity('');
	if(!$address->getdesstate()) $address->setdesstate('');
	if(!$address->getdescountry()) $address->setdescountry('');
	if(!$address->getdeszipcode()) $address->setdeszipcode('');			
	
	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);
});


//reponsável do salvar o endereço no bando de dados.
$app->post("/checkout", function() {

	User::verifyLogin(false);

	if(!isset($_POST['zipcode']) || $_POST['zipcode'] == '') {

		Address::setMsgError("Informe o CEP.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['desaddress']) || $_POST['desaddress'] == '') {

		Address::setMsgError("Informe o Endereço.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['desdistrict']) || $_POST['desdistrict'] == '') {

		Address::setMsgError("Informe o Bairro.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['descity']) || $_POST['descity'] == '') {

		Address::setMsgError("Informe a Cidade.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['desstate']) || $_POST['desstate'] == '') {

		Address::setMsgError("Informe o Estado.");
		header("Location: /checkout");
		exit;
	}

	if(!isset($_POST['descountry']) || $_POST['descountry'] == '') {

		Address::setMsgError("Informe o País.");
		header("Location: /checkout");
		exit;
	}

	$user = User::getFromSession();

	$address = new Address();

	//sobrescrevendo o $_POST['deszipcode'] com o $_POST do formulário.
	$_POST['deszipcode'] = $_POST['zipcode'];

	//sobrescrevendo o $_POST['idperson'] com o id do usuário da sessão.
	$_POST['idperson'] = $user->getidperson();

	//atribui todos os dados para o endereço.
	$address->setValues($_POST);

	//salva o endereço no bando de dados.
	$address->save();

	$cart = Cart::getFromSession();

	//força o calculo do carrinho de compras e atribui os valores para variáveis da classe Cart.
	$cart->getCalculateTotal();

	$order = new Order();

	$order->setValues([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	//salva o pedido, aí então, o pedido ganha um ID, que passamos esse ID para próxima rota (/order/:idorder).	
	$order->save();	
	
	//redireciona para pagamento.
	//passamos o id do pedido gerado para ser utilizado na geração do boleto (payment).
	header("Location: /order/".$order->getidorder());
	exit;

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

		User::setError($e->getMessage());
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


//rota para alterar a senha de usuário da administração. //devemos colocar o link forgot no arquivo login.html.
$app->get("/forgot", function() {

	$page = new Page();

	$page->setTpl("forgot");
});


//rota para enviar o email via post. Precisamos pegar o email que o usuário digitou no formulário. (Enviou via post)
$app->post("/forgot", function() {

	//$_POST["email"]pega o email digitado no campo email da pagina /admin/forgot.
	//User::getForgot precisamos criar o método que faça todas as verificações.
	$user = User::getForgot($_POST["email"], false);

	//forgot realizado com sucesso, vamos redirecionar para rota /admin/forgot/sent, avisando que foi enviado com sucesso.
	header("Location: /forgot/sent");

	exit;
});

//rota redirecionada com sucesso da rota ("/admin/forgot"), para para o usuário que o forgot foi realizado com sucesso.
$app->get("/forgot/sent", function() {

	$page = new Page();

	$page->setTpl("forgot-sent"); //carrega o template para o usuário que o email foi enviado com sucesso.
});

//quando o usuário dentro de seu email clica no link para redefinir a senha, essa rota é invocada.
$app->get("/forgot/reset", function() {

	//precisamos criar o metodo para validar o codigo, e verifica de qual usuário é esse código.
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	//verificando na página ("forgot-reset.html"), ela precisa da variáveis ("{$name}, {$code}").
	//carrega o template para o usuário informar a nova senha.
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"] //vai precisar validar na outra página. observe que é o mesmo codigo criptografado, pois um hacker pode invadir a segunda página e não a primeira.
	)); 
});


//rota responsável por alterar a senha no banco de dados.
$app->post("/forgot/reset", function() {

	//precisamos verificar novamente para verificar se teve brecha de segurança nessa transisão, dessa vez recuperar via $_POST.
	//temos os dados do usuário novamente.
	$forgot = User::validForgotDecrypt($_POST["code"]);

	//precisamos de um método para alterar a senha no banco de dados.
	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	//pega os dados do usuário pelo id.
	$user->get((int)$forgot["iduser"]);

	//código para salvar a senha no banco de dados criptografada.
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
 		"cost"=>12
 	]);

	//chama o metodo setPassword para salvar a senha que veio do formulário. Foi criado esse método pq precisamos salvar no banco o hash da senha que será codificado nesse método.
	$user->setPassword($password);

	$page = new Page();
	
	//carrega o template para informar o usuário que a senha foi alterada com sucesso.
	$page->setTpl("forgot-reset-success"); 
});


//rota responsável por criar profile (perfil) do cliente.
//depois que você loga, poderá ver todos os seus dados...
$app->get("/profile", function() {

	//false pq não é rota administrativa.
	User::verifyLogin(false);

	//pega o usuário da sessão.
	$user = User::getFromSession();

	$page = new Page();

	//chmama o template e passa as informações para o template.
	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);

});
 

//rota responsável por salvar no bando de dados o profile (perfil) do cliente.
$app->post("/profile", function() {

	//false pq não é rota administrativa.
	User::verifyLogin(false);

	//validações do formulario do template
	if(!isset($_POST['desperson']) || $_POST['desperson'] === ''){
		User::setError("Preencha o seu nome.");

		echo "entro no if 1";
		exit;

		header("Location: /profile");
		exit;
	}


	//validações do formulario do template
	if(!isset($_POST['desemail']) || $_POST['desemail'] === ''){
		User::setError("Preencha o seu e-mail.");

		echo "entro no if 2";
		exit;

		header("Location: /profile");
		exit;
	}

	//recuperar o usuário da sessão, pois não sabemos se ele alterou tudo, então assim aproveitamos o que ele já tem, e só trocar o que ele mudou.
	$user = User::getFromSession();


	//verifica se o e-mail já está sendo utilizado por outro usuário.
	//captura o e-mail digitado no template e verifica se está sendo alterado, se estiver entra no if.
	if($_POST['desemail'] != $user->getdesemail()){

		echo "entro no if 3";
		exit;
		
		//aqui é verificado se e-mail já está sendo utilizado por outro usuário.
		if(User::checkLoginExist($_POST['desemail']) === true) {
			User::setError("Este endereço de e-mail já está cadastrado.");

			echo "entro no if 4";
			exit;

			header("Location: /profile");
			exit;
		}
	}


	//sobrescrevendo inadmin e despasswor que vem do $_POST com getinadmin() e getdespassword() que vem do banco, pois esses dois parametros tem que ser usado o que possui atualmente, mes que decubrua que tenha esse parametros via post. A idea é ignora o que ele está mandando (command inject) e mantenha o que já está no banco. Assim temos certeza que não está alterando essa informação. 
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	//atribui o que foi alterado sobrescrevendo o objeto usuário.
	$user->setValues($_POST);	

	//altera os ddos no bando de dados.
	$user->update();

	//Obs.: para verificação do usuário dos novos dados, este deve deslogar e logar novamente.
	User::setSuccess("Dados alterados com sucesso.");

	header("Location: /profile");
	exit;

});


//rota responsável por gerar o boleto, temo que mostar qual o pedido do cliente, para tanto informamos o id do pedido.
//precisamos gerar o pedido antes...
$app->get("/order/:idorder", function($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);		

	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);
});


$app->get("/boleto/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 

	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
});


//rota responsável por buscar todos os pedidos do usuário.
$app->get("/profile/orders", function(){

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();	

	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);
});


//rota responsável por mostar os detalhes de cada pedido.
$app->get("/profile/orders/:idorder", function($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl("profile-orders-detail", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()		
	]);
});

//rota responsável por chamar o template para alterar a senha.
$app->get("/profile/change-password", function(){

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl("profile-change-password", [
		'changePassError'=>User::getError(),
		'changePassSuccess'=>User::getSuccess()
	]);
});

//rota responsável por enviar os dados para o servidor BD.(Alterar a senha).
$app->post("/profile/change-password", function(){

	User::verifyLogin(false);

	if(!isset($_POST['current_pass']) || $_POST['current_pass'] === '') 
	{
		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;
	}

	if(!isset($_POST['new_pass']) || $_POST['new_pass'] === '') 
	{
		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if(!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') 
	{
		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if($_POST['current_pass'] === $_POST['new_pass']) 
	{
		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;
	}


	$user = User::getFromSession();

	if(!password_verify($_POST['current_pass'], $user->getdespassword()))
	{

		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;

	}

	$user->setdespassword($_POST['new_pass']);

	$user->update();

	User::setSuccess("Senha alterada com sucesso.");

	//redireciona para o usuário ver a mensagem de sucesso.
	header("Location: /profile/change-password");
	exit;

});

?>