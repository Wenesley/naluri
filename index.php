<?php 
session_start(); //Inicia o uso de SESSOES na aplicação.

require_once("vendor/autoload.php"); // sempre tem que ter. Tras as dependências do projeto. (composer)

use \Slim\Slim; //são namespaces

$app = new Slim(); //Recebe as rotas, por causa do seo, ranqueamento de sites google, são utilizado rotas. (url)

$app->config('debug', true);

require_once("site.php");
require_once("admin.php");
require_once("admin-user.php");
require_once("admin-categories.php");
require_once("admin-products.php");

$app->run(); //Tudo carregado, vamos executar.


?>