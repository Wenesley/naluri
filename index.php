<?php 
session_start(); //Inicia o uso de SESSOES na aplicação.

require_once("vendor/autoload.php"); // sempre tem que ter. Tras as dependências do projeto. (composer)

use \Slim\Slim; //são namespaces
use \Naluri\Page; //são namespaces
use \Naluri\PageAdmin; //são namespaces
use \Naluri\Model\User;
use \Naluri\Model\Category;

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
$app->get("/categories/:idcategory", function($idcategory) {

	$category = new Category();

	$category->get((int)$idcategory);

	//nesse caso não chamamos uma página, mas um template. Então só precisamos do page.
	$page = new Page();

	//chamar somete o template dessa categoria, e vamos passar os dados dessa categoria, para mostrar lá dentro.
	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'procucts'=>[]
	]);

});


$app->run(); //Tudo carregado, vamos executar.

 ?>