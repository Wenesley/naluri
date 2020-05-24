<?php

use \Naluri\PageAdmin;
use \Naluri\Model\User;


$app->get("/admin/users/:iduser/password", function($iduser) {

    User::verifyLogin();

    $user = new User();

    $user->get((int)$iduser);

    $page = new PageAdmin();

    $page->setTpl("users-password", [
        'user'=>$user->getValues(),
        'msgError'=>$user->getError(),
        'msgSuccess'=>$user->getSuccess()
    ]);
});


$app->post("/admin/users/:iduser/password", function($iduser) {

    User::verifyLogin();

    if(!isset($_POST['despassword']) || $_POST['despassword'] === '') {
        User::setError("Preencha a nova senha.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

     if(!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === '') {
        User::setError("Preencha a confirmaação da nova senha.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

     if($_POST['despassword'] !== $_POST['despassword-confirm']) {
        User::setError("Preencha corretamente as senhas.");
        header("Location: /admin/users/$iduser/password");
        exit;
    }

    $user = new User();

    $user->get((int)$iduser);

    $user->setPassword(User::getPassWordHash($_POST['despassword']));

    User::setSuccess("Senha alterada com sucesso.");
    header("Location: /admin/users/$iduser/password");
    exit;


});


//rota para listar todos os usuários. Teremos uma tabela.
//queremos que tenha o header e o footer, e o usuário precisa está logado.
$app->get('/admin/users', function() {

	//na rota da administração, é necessário está logado, caso contrário redirecionar para tela de login.
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : "";

    //qual é a página atual?
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if($search != '') {

        //rotina que lista todos os usuários do banco de dados, método listAll static na classe Users.
        $pagination = User::getPageSearch($search, $page);

    } else {

        //rotina que lista todos os usuários do banco de dados, método listAll static na classe Users.
        $pagination = User::getPage($page);
    }

    $pages = [];

    for ($x = 0; $x < $pagination['pages']; $x++){

        array_push($pages, [
            'href'=>"/admin/users?".http_build_query([
                'page'=>$x+1,
                'search'=>$search
            ]),
            'text'=>$x+1

        ]);
    }

	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.	

	$page->setTpl("users", array(

		"users"=>$pagination['data'], //como projetado no setTpl, o segundo parametro passa os dados da lista para o template.
        "search"=>$search,
        "pages"=>$pages
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
    //$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
 	//	"cost"=>12
 	//]);

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

?>