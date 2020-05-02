<?php 
session_start(); //Inicia o uso de SESSOES na aplicação.

require_once("vendor/autoload.php"); // sempre tem que ter. Tras as dependências do projeto. (composer)

use \Slim\Slim; //são namespaces
use \Naluri\Page; //são namespaces
use \Naluri\PageAdmin; //são namespaces
use \Naluri\Model\User;

$app = new Slim(); //Recebe as rotas, por causa do seo, ranqueamento de sites google, são utilizado rotas. (url)

$app->config('debug', true);

$app->get('/', function() { //qual o link da página index da loja. Raiz principal.
    
	$page = new Page(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index"); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});


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

$app->run(); //Tudo carregado, vamos executar.

 ?>