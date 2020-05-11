<?php

use \Naluri\PageAdmin;
use \Naluri\Model\User;

$app->get('/admin', function() { //qual o link da página principal da Administração.
    //na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index"); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});


$app->get('/admin/login', function() { //qual o link da página principal da Administração.
    
	$page = new PageAdmin([ //Nesse momento de criação da página, o construtor é acionado e não cria o header e o footer, por se tratar da tela de login.
		"header"=> false,
		"footer"=> false
	]); 

	$page->setTpl("login"); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página login.html, neste caso não há cabeçalhe e nem rodapé, sendo necessário desabilitar a chamada automática dos mesmos.
});


$app->post('/admin/login', function() { //rota que submete o formulario do arquivo de login.html da Administração

	//chama o metodo statico login da classe Usuario, e valida as informações vindos da seção.
	User::login($_POST["login"], $_POST["password"]);

	//redirecionar para a página admin.
	header("Location: /admin");

	//para a execução
	exit;
});

//rota criada para encerrar sessao do usuário, no final redireciona para tela de login.
//precisamos colocar a rota "/admin/logout" no arquivo header.hmtl da administração, no link sign out.
$app->get('/admin/logout', function() {

	//chama o método statico lougout da classe de Usuário.
	User::logout();

	//redireciona para tela de login da administração.
	header("Location: /admin/login");

	exit;
});

//rota para alterar a senha de usuário da administração. //devemos colocar o link forgot no arquivo login.html.
$app->get("/admin/forgot", function() {

	$page = new PageAdmin([ //Nesse momento de criação da página, o construtor é acionado e não cria o header e o footer, por se tratar da tela de login.
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");
});


//rota para enviar o email via post. Precisamos pegar o email que o usuário digitou no formulário. (Enviou via post)
$app->post("/admin/forgot", function() {

	//$_POST["email"]pega o email digitado no campo email da pagina /admin/forgot.
	//User::getForgot precisamos criar o método que faça todas as verificações.
	$user = User::getForgot($_POST["email"]);

	//forgot realizado com sucesso, vamos redirecionar para rota /admin/forgot/sent, avisando que foi enviado com sucesso.
	header("Location: /admin/forgot/sent");

	exit;
});

//rota redirecionada com sucesso da rota ("/admin/forgot"), para para o usuário que o forgot foi realizado com sucesso.
$app->get("/admin/forgot/sent", function() {

	$page = new PageAdmin([//Nesse momento de criação da página, o construtor é acionado e não cria o header e o footer, por se tratar da tela de login.
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent"); //carrega o template para o usuário que o email foi enviado com sucesso.
});

//quando o usuário dentro de seu email clica no link para redefinir a senha, essa rota é invocada.
$app->get("/admin/forgot/reset", function() {

	//precisamos criar o metodo para validar o codigo, e verifica de qual usuário é esse código.
	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([//Nesse momento de criação da página, o construtor é acionado e não cria o header e o footer, por se tratar da tela de login.
		"header"=>false,
		"footer"=>false
	]);

	//verificando na página ("forgot-reset.html"), ela precisa da variáveis ("{$name}, {$code}").
	//carrega o template para o usuário informar a nova senha.
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"] //vai precisar validar na outra página. observe que é o mesmo codigo criptografado, pois um hacker pode invadir a segunda página e não a primeira.
	)); 
});


//rota responsável por alterar a senha no banco de dados.
$app->post("/admin/forgot/reset", function() {

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

	$page = new PageAdmin([//Nesse momento de criação da página, o construtor é acionado e não cria o header e o footer, por se tratar da tela de login.
		"header"=>false,
		"footer"=>false
	]);
	
	//carrega o template para informar o usuário que a senha foi alterada com sucesso.
	$page->setTpl("forgot-reset-success"); 
});


?>