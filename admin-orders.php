<?php

use \Naluri\PageAdmin;
use \Naluri\Model\User;
use \Naluri\Model\Order;
use \Naluri\Model\OrderStatus;

//rota responsável por excluir um pedido.
$app->get("/admin/orders/:idorder/delete", function($idorder) {

    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);

    $order->delete();

    header("Location: /admin/orders");
    exit;

});


//rota responsável por carregar o template, e listar os status.
$app->get("/admin/orders/:idorder/status", function($idorder) {

    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);    

    $page = new PageAdmin();

    $page->setTpl("order-status", [
        'order'=>$order->getValues(),
        'status'=>OrderStatus::listAll(),
        'msgSuccess'=>Order::getSuccess(),
        'msgError'=>Order::getError()
    ]);
});

//rota responsável por enviar para o servidor BD.
$app->post("/admin/orders/:idorder/status", function($idorder) {

    User::verifyLogin();

    if(!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0)
    {
        Order::setError("Informe o status atual.");
        header("Location: /admin/orders/".$idorder."/status");
        exit;
    }

    $order = new Order();

    $order->get((int)$idorder); 

    $order->setidstatus((int)$_POST['idstatus']);   

    $order->save();

    Order::setSuccess("Status atualizado com sucesso.");

    header("Location: /admin/orders/".$idorder."/status");
    exit;
});


$app->get("/admin/orders/:idorder", function($idorder){

    User::verifyLogin();

    $order = new Order();

    $order->get((int)$idorder);

    $cart = $order->getCart();

    $page = new PageAdmin();

    $page->setTpl("order", [
        'order'=>$order->getValues(),
        'cart'=>$cart->getValues(),
        'products'=>$cart->getProducts()
    ]);
});


//rota responsável por listar os pedidos e carregar o template orders.html.
$app->get("/admin/orders", function(){

    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("orders", [
        'orders'=>Order::listAll()
    ]);

});


?>