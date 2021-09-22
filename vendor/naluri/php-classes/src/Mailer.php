<?php 

namespace Naluri;

use Rain\Tpl;//vamos fazer renderização de template html, por isso utilizamos o Rain\Tpl.

//classe para enviar email
class Mailer {
	
	const USERNAME = "";
	const PASSWORD = "";
	const NAME_FROM = ""; //nome do remetente.

	private $mail;

	//construtor que recebe qual endereço que vamos mandar, qual o nome do destinatário, qual o assunto, o nome do arquivo de templante que vamos mandar pelo Rain\Tpl, e os dados, as variáveis que vamos mandar para o template, que por padrão é um array vazio.
	public function __construct($toAddress, $toName, $subject, $tplName, $data = array())
	{

		$config = array(
			"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/email/", //onde vamos salvar o template.
			"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/", //para tornar mais rápido a navegação, caso não tenha mudança...
			"debug"         => false
	    );

		Tpl::configure( $config );

		$tpl = new Tpl;

		//passando os dados (variáveis) para o template.
		foreach ($data as $key => $value) {
			$tpl->assign($key, $value);
		}

		$html = $tpl->draw($tplName, true); // o segundo parametro (true), para não jogar na tela, e sim atribuir o template para a variável ($html).

		$this->mail = new \PHPMailer;

		/*Codigo pesquisado no google pois o estava gerando 
			erro de certificado ssl para o gmail*/
		$this->mail->SMTPOptions = array('ssl'=> array('verify_peer'=> false,
			'verify_peer_name'=>false, 'allow_self_signed'=>true )); 

		//Tell PHPMailer to use SMTP
		$this->mail->isSMTP();

		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$this->mail->SMTPDebug = 0;

		//Ask for HTML-friendly debug output
		$this->mail->Debugoutput = 'html';

		//Set the hostname of the mail server
		$this->mail->Host = 'smtp.gmail.com';
		// use
		// $this->mail->Host = gethostbyname('smtp.gmail.com');
		// if your network does not support SMTP over IPv6

		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$this->mail->Port = 587;

		//Set the encryption system to use - ssl (deprecated) or tls
		$this->mail->SMTPSecure = 'tls';

		//Whether to use SMTP authentication
		$this->mail->SMTPAuth = true;

		//Username to use for SMTP authentication - use full email address for gmail
		$this->mail->Username = Mailer::USERNAME;

		//Password to use for SMTP authentication
		$this->mail->Password = Mailer::PASSWORD;

		//Set who the message is to be sent from
		$this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);

		//Set an alternative reply-to address
		//$this->mail->addReplyTo('replyto@example.com', 'First Last');

		//Set who the message is to be sent to
		$this->mail->addAddress($toAddress, $toName);

		//Set the subject line
		$this->mail->Subject = $subject;

		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$this->mail->msgHTML($html); //envia o templete para o email.

		//Replace the plain text body with one created manually
		$this->mail->AltBody = 'This is a plain-text message body';

		//Attach an image file
		//$mail->addAttachment('images/phpmailer_mini.png');

	}

	//método que envia o emai, separado para que podemos enviar somente quando quisermos.
	public function send()
	{

		return $this->mail->send();

	}

}

 ?>
