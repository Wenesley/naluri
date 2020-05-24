<?php

use \Naluri\PageAdmin;
use \Naluri\Model\User;
use \Naluri\Model\Product;

$app->get("/admin/products", function() {

	User::verifyLogin();

	//$products = Product::listAll(); //antes do search.

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

    //qual é a página atual?
    $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

    if($search != '') {

        //rotina que lista todos os usuários do banco de dados, método listAll static na classe Users.
        $pagination = Product::getPageSearch($search, $page, 1);

    } else {

        //rotina que lista todos os usuários do banco de dados, método listAll static na classe Users.
        $pagination = Product::getPage($page, 1);
    }

    $pages = [];

    for ($x = 0; $x < $pagination['pages']; $x++){

        array_push($pages, [
            'href'=>"/admin/products?".http_build_query([
                'page'=>$x+1,
                'search'=>$search
            ]),
            'text'=>$x+1

        ]);
    }

	$page = new PageAdmin();

	$page->setTpl("products", [
		//"products"=>$products //antes do search
		"products"=>$pagination['data'], //como projetado no setTpl, o segundo parametro passa os dados da lista para o template.
        "search"=>$search,
        "pages"=>$pages
	]);
});


$app->get("/admin/products/create", function() {

	User::verifyLogin();	

	$page = new PageAdmin();

	$page->setTpl("products-create");
});


$app->post("/admin/products/create", function() {

	User::verifyLogin();	

	$product = new Product();

	$product->setValues($_POST);

	$product->save();

	header("Location: /admin/products");

	exit;
});


$app->get("/admin/products/:idproduct", function($idproduct) {

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);	

	$page = new PageAdmin();

	//passar os dados dos meus produtos para o template.
	$page->setTpl("products-update", [
		"product"=> $product->getValues()
	]);
});

$app->post("/admin/products/:idproduct", function($idproduct) {

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);	

	$product->setValues($_POST);

	$product->save();

	$product->setPhoto($_FILES["file"]);

	header("Location: /admin/products");

	exit;
});

$app->get("/admin/products/:idproduct/delete", function($idproduct) {

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);	

	$product->delete($product);

	header("Location: /admin/products");

	exit;	
});


?>