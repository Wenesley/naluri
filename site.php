<?php

use \Naluri\Page;

$app->get('/', function() { //qual o link da página index da loja. Raiz principal.
    
	$page = new Page(); //Nesse momento de criação da página, o construtor é acionado e cria o header.html.

	$page->setTpl("index"); //quando chamamos o metodo setTpl e passamos o template, nesse caso ele chama a página index.html e adiciona o corpo da página. quando seguir para próxima linha após a chamda encerra a execução, ou seja limpa a memória do seu site, e automaticamente o método destruct é acionado, o arquivo footer.html e inserido no template.
});


?>