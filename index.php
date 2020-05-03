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

//rota para listar todos os usuários. Teremos uma tabela.
//queremos que tenha o header e o footer, e o usuário precisa está logado.
$app->get('/admin/users', function() {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    //rotina que lista todos os usuários do banco de dados, método listAll static na classe Users.
    $users = User::listAll();

	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.	

	$page->setTpl("users", array(

		"users"=>$users //como projetado no setTpl, o segundo parametro passa os dados da lista para o template.

	));//quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página users.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.


});


//rota para pega os valores usuários. Para salvar de fato é preciso criaar a rota com método "post".
//queremos que tenha o header e o footer, e o usuário precisa está logado.
$app->get('/admin/users/create', function() {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("users-create");//quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página users-create.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.


});

//rota que exclui os dados do usuário pelo iduser passado por parametro na função.
//cuidado com a ordem da posição, "/admin/users/:iduser/delete" com "/admin/users/:iduser", o slim faz confusão.
$app->get('/admin/users/:iduser/delete', function($iduser) {
	
	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    //cria um novo usuario para verificar se ainda existe.
    $user = new User();

    //busca no banco para verificar se existe.
    $user->get((int)$iduser);

    //método para deletar usuário do banco.
    $user->delete();

    header("Location: /admin/users");

    exit;

});


//rota para atualizar/update o usuário pelo iduser passado. 
//rota para buscar os dados do usuário no banco de dados e preencher os campos da página users-updade.html. 
//acesando a rota "/admin/users/:iduser", vc está solicitando os dados de um usuário específico, que estamos recebendo como paramentro na funcao.
//queremos que tenha o header e o footer, e o usuário precisa está logado.
$app->get("/admin/users/:iduser", function($iduser) { //o valor que recebemos no parametro é atribuido para rota "/admin/users/:iduser".

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    //cria um usuario.
    $user = new User();

    //pega o id do usuário. Temos que criar o método get na classe usuario.
    $user->get((int)$iduser);

	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("users-update", array(
		"user"=>$user->getValues() //segundo parametro pega todos os dados do objeto.

	));//quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página users-update.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});

//rota que recebe os dados de preenchimento do formulario de cadastro e salva no banco de dados.
$app->post("/admin/users/create", function() {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    //cria um novo usuário.
    $user = new User();

    //verifica se na página users-create.html, se o campo inadmin foi definido, caso não seja marcado receve valor 0;
    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    //código para salvar a senha no banco de dados criptografada.
    $_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
 		"cost"=>12
 	]);

    //pega os dados digitados na págnia users-create.html e atribui os valores para o método setValues responsável por criar setar as variaveis do objeto. Só falta criar um método para salvar no banco de dados.
    $user->setValues($_POST);

    //chama o método save de User e salva o usuário no banco de dados.
    $user->save();

    //após salvar o usuário, a rota redireciona para a página lista de usuários.
    header("Location: /admin/users");

    exit;  

});

//rota que recebe os dados de que foram alterados da página users-update.html para salvar no banco de dados.
$app->post("/admin/users/:iduser", function($iduser) {
	
	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    $user = new User();

    //verifica se na página users-create.html, se o campo inadmin foi definido, caso não seja marcado receve valor 0;
    $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;

    //busca os dados do usupario no banco de dados.
    $user->get((int)$iduser);

    //pega os dados digitados na págnia users-create.html e atribui os valores para o método setValues responsável por criar setar as variaveis do objeto. Só falta criar um método para salvar no banco de dados.
    $user->setValues($_POST);

    //metodo para alterar os dados no banco de dados.
    $user->update();

    header("Location: /admin/users");

    exit;

});


$app->run(); //Tudo carregado, vamos executar.

 ?>