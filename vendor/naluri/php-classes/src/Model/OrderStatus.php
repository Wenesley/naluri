<?php
	
	namespace Naluri\Model;

	use \Naluri\DB\Sql; //a primeira contrabarra se dá pq a tem que voltar na raiz do projeto.
	use \Naluri\Model; //classe responsável por gerar os métodos getters e setters automáticamente.
	

	class OrderStatus extends Model
	{

		const EM_ABERTO = 1;
		const AGUARDANDO_PAGAMENTO = 2;
		const PAGO = 3;
		const ENTREGUE = 4;
	}


?>