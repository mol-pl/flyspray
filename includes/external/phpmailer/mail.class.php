<?
/*
	Klasa pośrednicząca do wysyłania maili
	
	Korzysta z PHP mailera stosując składnię podobną do funkcji mail().
*/

define ('PHPMAILER_DIR', rtrim(dirname(__FILE__), '/').'/phpmailer_x17');
//define ('PHPMAILER_DIR', './_inc/phpmailer_x17');
require_once(PHPMAILER_DIR.'/class.phpmailer.php');

class smpMail
{
	var $mailer;
	var $enc;
	var $strDomain;
	var $arrInternalAuth;
	var $arrExtenrnalAuth;

	//
	// Debug setup
	//
	// mailer SMTP debug
	var $SMTPDebug = false;
	// self debug
	var $debug = false;
	// show body of mail when debugging
	var $debugShowBody = false;
	// disable sending actual e-mails
	var $sendDisabled = false;

	//! @brief parse e-mail address to add a domain if needed
	function mailParse($pv_strAddr)
	{
		if (strpos($pv_strAddr, '@')>0)
		{
			return $pv_strAddr;
		}
		return $pv_strAddr.'@'.$this->strDomain;
	}
	
	/*!
		@brief constructor
		
		@param pv_arrInternalAuth	internal mails authorization config
			array(
				'SMTPAuth' => false,
				'Sender' => 'some-return-path',
			)
		@param pv_arrExtenrnalAuth		external mails authorization config
			array(
				'SMTPAuth' => true,
				'Username' => 'user',
				'Password' => 'pass',
				'Sender' => 'user-mail',
				'ReplyTo' => 'user-mail',
			)
		@param pv_strDomain			mail server domain (and host)
	*/
	function smpMail(
		$pv_arrInternalAuth=array(
			'SMTPAuth' => false,
			'Sender' => 'maciej',
		)
		, $pv_arrExtenrnalAuth=array(
			'SMTPAuth' => true,
			'Username' => 'www397518trvw29',
			'Password' => 'RetroPikot123',
			'Sender' => 'www',
			'ReplyTo' => 'nie.odpowiadaj.mailem',
		)
		, $pv_strDomain='mol.com.pl'
		, $pv_strSMTPHost='192.168.0.1'
	)
	{
		//
		// ustawienia zmiennych ogólnych
		$this->enc = 'UTF-8';
		$this->strDomain = $pv_strDomain;
		$this->arrInternalAuth = $pv_arrInternalAuth;
		$this->arrExtenrnalAuth = $pv_arrExtenrnalAuth;

		//
		// Mailer
		//
		$this->mailer = new PHPMailer();
		
		//
		// Ustawienia podstawowe
		$this->mailer->PluginDir = PHPMAILER_DIR.'/';
		$this->mailer->SetLanguage('pl', PHPMAILER_DIR.'/language/');

		//
		// wysyłający i do odbijania
		//$this->mailer->Sender = $this->mailParse($pv_strReturnAddr);
		
		//
		// dane dla SMTP
		$this->mailer->IsSMTP();
		$this->mailer->Host = $pv_strSMTPHost;
		//$this->mailer->SMTPAuth = false;
		/**
		$this->mailer->SMTPAuth = true;
		$this->mailer->Username = '...';
		$this->mailer->Password = '...';
		/**
		$this->mailer->SMTPAuth = true;
		$this->mailer->Username = 'Maciej';
		$this->mailer->Password = '';
		/**
		$this->mailer->IsSMTP();
		$this->mailer->SMTPAuth   = true;                  // enable SMTP authentication
		$this->mailer->SMTPSecure = "ssl";                 // sets the prefix to the servier
		$this->mailer->Port       = 587;                   // set the SMTP port

		$this->mailer->Username = '...';
		$this->mailer->Password = '...';
		/**/
	}

	//
	// Czyszczenie poprzednich, nie domyślnych ustawień
	//
	function ClearPrevious()
	{
		$this->ClearCustomHeaders();
		$this->mailer->ClearAddresses();
	}

	//
	// Funkcje operujące na dodatkowych nagłówkach
	//
	function AddCustomHeader($name, $value)
	{
		$this->mailer->CustomHeader[] = array($name, $value);
	}
	function ClearCustomHeaders()
	{
		$this->mailer->CustomHeader = array();
	}

	//
	// Funkcja umożliwiająca wysłanie maila przez restrykcyjny serwer pocztowy
	//
	function send($od, $do, $temat, $tresc, $html=false)
	{
		// debug setup
		$this->mailer->SMTPDebug = $this->SMTPDebug;

		// wyczyść adresy
		$this->mailer->ClearAllRecipients();
		$this->mailer->ClearReplyTos();
		
		//
		$isExternal = false;
		$reIntDomain = '#@'.strtr($this->strDomain, array('.'=>'\.')).'$#';
		
		//
		// przygotowanie "do"
		$do_arr = explode(',', $do);
		foreach($do_arr as $d)
		{
			$d = $this->emailstr2array($d);
			$this->mailer->AddAddress ($d['mail'],$d['name']);
			if (!preg_match($reIntDomain, $d['mail']))
			{
				$isExternal = true;
			}
		}

		//
		// przygotwanie "od"
		$od = $this->emailstr2array($od);
		$this->mailer->From = $od['mail']; // tylko adres!
		$this->mailer->FromName = $od['name'];	// nazwa dodawana do adresu
		
		//
		// przygotowanie trybu autoryzacji i ew. korekta od
		$arrMailAuth = $this->arrInternalAuth;
		if ($isExternal)
		{
			// autoryzacja
			$arrMailAuth = $this->arrExtenrnalAuth;
			
			// korekta from
			if ($this->mailParse($arrMailAuth['Sender']) != $od['mail'])
			{
				if ($this->debug)
				{
					trigger_error("From [{$od['mail']}] ignored (To is external)", E_USER_NOTICE);
				}
				$this->mailer->AddReplyTo($this->mailer->From, $this->mailer->FromName);
				$this->mailer->FromName = '';
				$this->mailer->From = $this->mailParse($arrMailAuth['Sender']);
			}
		}
		// ustawienia autoryzacji i wysyłającego (Return-Path)
		$this->mailer->Sender = $this->mailParse($arrMailAuth['Sender']);
		if (!empty($arrMailAuth['ReplyTo']))
		{
			$this->mailer->ReplyTo = $this->mailParse($arrMailAuth['ReplyTo']);
		}
		$this->mailer->SMTPAuth = false;
		if ($arrMailAuth['SMTPAuth'])
		{
			$this->mailer->SMTPAuth = true;
			$this->mailer->Username = $arrMailAuth['Username'];
			$this->mailer->Password = $arrMailAuth['Password'];
		}
	
		//
		// typ zawartości
		$this->mailer->CharSet = $this->enc;
		$this->mailer->IsHTML($html);
		
		//
		// ostatnie przygotowania...
		$this->mailer->Subject = $temat;
		
		if ($this->debug)
		{
			echo "<h2>".($isExternal?'External':'Internal').': '.htmlspecialchars($temat)."</h2>";
			echo "<table border='1'>";
			echo "<tr>";
				echo "<th>orig</th>";
				echo "<th>sent</th>";
			echo "</tr><tr>";
				echo "<td>od: ".htmlspecialchars(var_export($od, true))."</td>";
				echo "<td>";
				echo "FromName: ".htmlspecialchars(var_export($this->mailer->FromName, true));
				echo "<br />From: ".htmlspecialchars(var_export($this->mailer->From, true));
				echo "<br />Sender: ".htmlspecialchars(var_export($this->mailer->Sender, true));
				echo "<br />replyto: ".htmlspecialchars(var_export($this->mailer->ReplyTo, true));
				echo "<br />CC: ".htmlspecialchars(var_export($this->mailer->cc, true));
				echo "<br />BCC: ".htmlspecialchars(var_export($this->mailer->bcc, true));
				echo "</td>";
			echo "</tr><tr>";
				echo "<td>do: ".htmlspecialchars(var_export($do, true))."</td>";
				echo "<td>to: ".htmlspecialchars(var_export($this->mailer->to, true))."</td>";
			echo "</tr><tr>";
				echo "<td>temat: ".htmlspecialchars(var_export($temat, true))."</td>";
				echo "<td>Subject: ".htmlspecialchars(var_export($this->mailer->Subject, true))."</td>";
			if ($this->debugShowBody)
			{
				echo "</tr><tr>";
					echo "<td>tresc: ".htmlspecialchars(var_export($tresc, true))."</td>";
					echo "<td>Body: ".htmlspecialchars(var_export($this->mailer->Body, true))."</td>";
			}
			echo "</tr>";
			echo "</table>";
			echo "</ul>";
			echo "<pre>";

		}

		$this->mailer->Body = $tresc;
		//$this->mailer->AltBody = strip_tags($tresc);	// tekst alternatywny (dla tych bez HTML), w tej postaci ryzykowne...
		
		//
		// wysyłka i zwrot zwrotu
		if ($this->sendDisabled)
		{
			$ret=true;
			trigger_error("PHPmailer: mails temprarly disabled; mail(od: $od, do: $do, temat: $temat, tresc: $tresc)", E_USER_NOTICE);
		}
		else
		{
			$ret = $this->mailer->Send();
		}
		if (!$ret)
		{
			trigger_error('PHPmailer: '.$this->mailer->ErrorInfo, E_USER_WARNING);
		}

		if ($this->debug)
		{
			echo "</pre>";
		}
		return $ret;
	}

	//
	// Użytki
	//
	
	function strencode($str)
	{
		return '=?'. $this->enc .'?B?'.base64_encode($str).'?=';
	}

	function emailstr2array($str)
	{
		$ret=array();
		if (strstr($str, '<'))
		{
			preg_match('/([^<]*)<([^>]*)>/', $str, $m);
			$ret['name']=$m[1];
			$ret['mail']=$m[2];
		}
		else
		{
			$ret['name']='';
			$ret['mail']=$str;
		}
		return $ret;
	}
}
/**
$nmail = new smpMail();

$nmail->send('Maciej Kędzierski <maciej@mol.com.pl>', 'Maciej Jaros <egil@wp.pl>, maciej@mol.com.pl, Nux <eccenux@tlen.pl>', 'Ad. Dzisiaj...', 'Cześć. Słuchaj zapomniałem Ci powiedzieć, że dzisiaj chyba nie dam rady wpaść. Może jutro się jakoś spikniemy, albo coś.'.chr(13).'Pozdrowienia, Maciek'.chr(13).'PS: Sorki za b/t ;).', false) or die('Błąd wysyłania');
/**/
?>