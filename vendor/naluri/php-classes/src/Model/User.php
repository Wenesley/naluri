<?php

	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.
	use \Naluri\Mailer;

	//Ao invés de criar em cada classe metodos get e set, foi decidido criar uma classe capaz de gerar esses métodos mágicos, por isso a classe herda de Model.
	class User extends Model
	{		
		const SESSION = "User"; //Constante do tipo SESSION para armazer os dados do usuário.
		const SECRET = "NaluriPhp7password"; //chave para criptografar e descriptografar, no minimo 16 caracteres.
		const SECRET_IV = "NaluriPhp7password_IV";

		//método que autentica login e senha do usuário.
		public static function login($login, $password)
		{	
			//cria o objeto sql
			$sql = new Sql();

			//variável que recebe o resultado da consulta direto do banco de dados.
			$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
				":LOGIN"=>$login				
			));

			//verifica se não trouxe resultado do banco de dados, se não trouxe executa a Exception.
			if(count($results) === 0)
			{
				throw new \Exception("Usuário inexistente ou senha inválida.");
				
			}

			//passou pelo if, ou seja trouxe resultado que será armazenado na variável $data.
			$data = $results[0];

			//método password_verify, faz verificação da senha, essa função simplesmente retorna valor boleano.
			if(password_verify($password, $data["despassword"]) === true) {

				$user = new User();

				//chama o método setValues responsável por criar todos os campos setters gertters do usuário.
				$user->setValues($data);

				//para funcionar um login, é preciso criar uma sessao, então os dados precisa está em uma sessao, precisamos ter uma sessao com algum nome. Quando o usuário estiver navegando pelo site e a sessao exitir então estará logado, caso contrario será redirecionado para fazer o login.

				//pega os velores, assim como setValues seta, os getValues pega os valoes em formato de array() e atribui à sessao do usuario.
				$_SESSION[User::SESSION] = $user->getValues();

				//já temos nosso objeto usuário carregado, então precisamos retornar esse usuário, caso alguém precise dele.
				return $user;

			} else {

				throw new \Exception("Usuário inexistente ou senha inválida.");
			}


		}

		//método que verifica se o usuário está logado.
		public static function verifyLogin($inadmin = true)
		{
			
			if (
				!isset($_SESSION[User::SESSION]) //verifica se ela existe.
				|| 
				!$_SESSION[User::SESSION] //verifica se não está vazia.
				|| 
				!(int)$_SESSION[User::SESSION]["iduser"] > 0 //verifica se o idusuario na sessao é maior que 0.
				|| 
				(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin //verifica se o usuário é funcionário ou cliente.
				) 
			{	
				
				//caso não esteja logado redireciona para tela de login.
				header("Location: /admin/login");

				exit;
				
			}

		}

		//método responsável para encerrar a sessao do usuário, delogar.
		public static function logout()
		{
			//atribui o valor nulo para a sessáo do usuário.
			$_SESSION[User::SESSION] = NULL;

		}

		//método responsável por listar todos os usuários do banco de dados.
		public static function listAll()
		{
			//cria objeto Sql().
			$sql = new Sql();

			//precisamos juntar as tabelas users e persons para trazer todos os dados. Retorna para a chamada da rota.
			return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

		}

		//método para salvar o usuário.
		public function save() 
		{
			$sql = new Sql();
			
			//Iremos chamar um procedure, pois ela vai inserir uma pessoa primeiro, pois precisamos saber qual o id dessa pessoa para poder inserir na tabela de users, pq ele precisa do idpessoa conforme estrutura da tabela no banco de dados. Vamos pegar o id do usuario que retornou, fazer um select com todos os dados do banco de dados que estão lá agora, juntar tudo e trazer de volta. Procedure é mais rápido do que o código php que iriamos fazer.
			$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
				":desperson"=>$this->getdesperson(), 
				":deslogin"=>$this->getdeslogin(),   //pegando os datos que estão no nosso objeto.
				":despassword"=>$this->getdespassword(),//todos esses getters foram gerados pelo getValues da classe Model.
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
			));

			//setta o resultado no próprio objeto, caso queira utilizar posteriormente.
			$this->setValues($results[0]);
		}

		//método responsável por buscar dados do usuario pelo id, salvar na variável $results e setar o próprio objeto com o método setValues, para ser utilizado por quem requisitar, nesse caso a rota que preenche os campos da pagina users-update.html para alteração.
		public function get($iduser)
		{

			$sql = new Sql();

			$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
				"iduser"=>$iduser
			));

			//seta o resultado da consulta no próprio objeto.
			$this->setValues($results[0]);
		}

		public function update()
		{

			$sql = new Sql();
			
			//Iremos chamar um procedure, pois ela vai inserir uma pessoa primeiro, pois precisamos saber qual o id dessa pessoa para poder inserir na tabela de users, pq ele precisa do idpessoa conforme estrutura da tabela no banco de dados. Vamos pegar o id do usuario que retornou, fazer um select com todos os dados do banco de dados que estão lá agora, juntar tudo e trazer de volta. Procedure é mais rápido do que o código php que iriamos fazer.
			$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
				":iduser"=>$this->getiduser(),
				":desperson"=>$this->getdesperson(), 
				":deslogin"=>$this->getdeslogin(),   //pegando os datos que estão no nosso objeto.
				":despassword"=>$this->getdespassword(),//todos esses getters foram gerados pelo getValues da classe Model.
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
			));

			//setta o resultado no próprio objeto, caso queira utilizar posteriormente.
			$this->setValues($results[0]);

		}

		public function delete()
		{

			$sql = new Sql();

			$sql->query("CALL sp_users_delete(:iduser)", array(
				"iduser"=>$this->getiduser()
			));
		}

		//método statico que fazer todas as validações.
		public static function getForgot($email)
		{

			$sql = new Sql();

			//busca todos os dados da tabela persons e users pelo email digitado no formulario, pois precisaremos do id do usuário.
			$results = $sql->select("SELECT * FROM tb_persons a
				INNER JOIN tb_users b
				USING(idperson) WHERE a.desemail = :email;
				", array(
					":email"=>$email
				));			

			if(count($results) === 0)
			{	
				//caso não encontre o email no banco, é gerado mensagem na tela do usuário.
				throw new \Exception("Não foi possível recuperar a senha.");
				
			} 
			else
			{	
				//atribui à variável $data o resultado da busca dos dados das tabelas persons e users pelo email digitado.
				$data = $results[0];

				//chama a procedure no banco de dados, responsável por inserir o iduser e desip na tabela tb_userspasswordsrecoveries.
				$resultsRecovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(                       
					":iduser"=>$data["iduser"],
					":desip"=>$_SERVER["REMOTE_ADDR"] //constante responsável por pegar o ip do usuário que solicitou redefinição de senha.
				));
			}

			if(count($resultsRecovery) === 0)
			{	
				//caso tenha gerado erro ao chamar e ou executar a procedure spuserspasswordsrecoveries_create.
				throw new \Exception("Não foi possível recuperar a senha.");				
			} 
			else
			{
				//se conseguiu executar a chamada da procedure entra nesse bloco e pega o resultado da consulta, pois essa consulta retorna o idrecovery da tabela tb_userspasswordsrecoveries.
				$dataRecovery = $resultsRecovery[0];

				//precisamos gerar um código para criptografar o idrecovery para enviar como um link para o email do usuário, para que outras pessoas não tentem utilitar esse número, ou a sequencia de outros números.
				//para criptografar vamos ter alguns parametros, e então transformaremos tudo em base64 para transformar o código gerado em texto.
				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

				$code = base64_encode($code);

				//montar o link, endereço que vai receber esse código, e esse link vai ser enviado por email.
				// a rota é http://www.naluri.com.br/admin/forgot/reset (link para passar o código e validar), como vamos passar esse código via get ("?") qual código a variável code (code=#code);
				$link = "http://www.naluri.com.br/admin/forgot/reset?code=$code";

				$subject = "Redefinir senha da Naluri";
				//o que vamos fazer agora? precisamos enviar por email. Vamos utilizar a classe PhpMailer. PhpMailer é uma classe que já vem pronta, parametrizada, precisamos apenas preencher, e se possível utilizar um template. Para facilitar será criada uma classe como se fosse uma Factory, uma classe que vai criar a configuração do PhpMailer.
				//Vamos criar o email, pois a classe Mailer já está pronta.
				//O construtor recebe como parametros, o email, o nome do destinatário, o assunto, o nome do template, e por ultimo os dados para envio.
				$mailer = new Mailer($data["desemail"], $data["desperson"], $subject, "forgot", array(
					"name"=>$data["desperson"], //foi ferificado na página forgot.html, quais as variáveis são requeridas. ({$..})
					"link"=>$link
				));

				$mailer->send(); //envia de fato o email para o destinatário.

				return $data; //retorna todos os dados das tabelas users e persons, caso queira utilizá-los.				
			}			

		}

		//médoto responsável por verificar de que usuário pertence esse código e validá-lo.
		//fazer o processo inverso da criptografia.
		public static function validForgotDecrypt($code)
		{
			//ultimo processa da criptografia foi tranformar todo o codigo para base64, agora então utilizamos decode para descriptografar o mesmo codigo.
			$code = base64_decode($code);

			//para criptografar foi utlilizar o openssl_encrypt agora para descriptografar usamos "openssl_decrypt".
			//passamos os mesmos parametros de utilizamos para criptografar. Após descriptografar armazenamos o resultado, ou seja o código na variável idrecovery.
			$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

			$sql = new Sql();

			//agora precisamos ir no banco de dados fazer a verificação. Esse id é válido, está no tempo, dentro de uma hora para ser utilizado, já foi utilizado? Vamos fazer uma query que vai verificar, fazer essa inteligênica.
			//pegamos os dados da tabela tb_userspasswordsrecoveries, fazemos uma junçao com as tabelas tb_users e tb_persons pq precisamos do nome da pessoa para enviar para o template. Trazemos esses dados somente se a.idrecovery que foi salvo na tabela tb_userspasswordsrecoveries é o mesmo $idrecovery, verificamos também se campo a.dtrecovery que por padrão é NULL da tabela tb_userspasswordsrecoveries permanece NULL, pois assim tempos certeza que o código não foi utilizado, também é verifica a data que foi gerado "userspasswordsrecoveries", e somamos + 1 hora através da função mysql DATE_ADD a coluna a.dtregister da tabela tb_userspasswordsrecoveries, se a soma da data e hora da coluna + 1 hora, se for maior que a data e a hora de agora, então passou do tempo de redefinir a senha.
			$results = $sql->select("
				SELECT * FROM tb_userspasswordsrecoveries a
				INNER JOIN tb_users b USING(iduser)
				INNER JOIN tb_persons c USING(idperson)
				WHERE a.idrecovery = :idrecovery
				AND a.dtrecovery IS NULL
				AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
			", array(
				":idrecovery"=>$idrecovery
		));

			if(count($results) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha.");
			}
			else 
			{
				//Aqui foi encontrado o registro no banco de dados. Vamos devolver os deados desse usuário.
				return $results[0];
			}

		}

		public static function setForgotUsed($idrecovery)
		{

			$sql = new Sql();

			$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
				":idrecovery"=>$idrecovery
			));
		}

		public function setPassword($password)
		{

			$sql = new Sql();

			$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
				":password"=>$password,
				":iduser"=>$this->getiduser()
			));
		}

	}

?>