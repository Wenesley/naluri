<?php
	
	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.
	

	class Order extends Model
	{

		public function save()
		{

			$sql = new Sql();

			$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
				':idorder'=>$this->getidorder(),
				':idcart'=>$this->getidcart(),
				':iduser'=>$this->getiduser(),
				':idstatus'=>$this->getidstatus(),
				':idaddress'=>$this->getidaddress(),
				':vltotal'=>$this->getvltotal()
			]);
			

			if (count($results) > 0) {
				$this->setValues($results[0]);				
			}
			
		}


		public function get($idorder)
		{

			$sql = new Sql();

			//ON é utilizado quando eu quero dizer especificamente de qual tabela anteriores eu quero...
			$results = $sql->select("
				SELECT * 
				FROM tb_orders a
				INNER JOIN tb_ordersstatus b USING(idstatus)
				INNER JOIN tb_carts c USING(idcart)
				INNER JOIN tb_users d ON d.iduser = a.iduser
				INNER JOIN tb_addresses e USING(idaddress)
				INNER JOIN tb_persons f ON f.idperson = d.idperson
				WHERE a.idorder = :idorder
			", [

				':idorder'=>$idorder
			]);

			if (count($results) > 0) {
				$this->setValues($results[0]);
			}
		}

	}


?>