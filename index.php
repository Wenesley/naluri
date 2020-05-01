<?php 

require_once("vendor/autoload.php"); // sempre tem que ter. Tras as dependências do projeto. (composer)

use \Slim\Slim; //são namespaces
use \Naluri\Page; //são namespaces
use \Naluri\PageAdmin; //são namespaces

$app = new Slim(); //Recebe as rotas, por causa do seo, ranqueamento de sites google, são utilizado rotas. (url)

$app->config('debug', true);

$app->get('/', function() { //qual o link da página index da loja. Raiz principal.
    
	$page = new Page(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index"); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});


$app->get('/admin', function() { //qual o link da página principal da Administração.
    
	$page = new PageAdmin(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index"); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});

$app->run(); //Tudo carregado, vamos executar.

 ?>