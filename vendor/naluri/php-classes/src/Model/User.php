<?php

	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.

	//Ao invés de criar em cada classe metodos get e set, foi decidido criar uma classe capaz de gerar esses métodos mágicos, por isso a classe herda de Model.
	class User extends Model
	{
		//Constante do tipo SESSION para armazer os dados do usuário.
		const SESSION = "User";

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

	}

?>