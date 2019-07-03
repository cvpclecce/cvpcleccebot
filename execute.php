<?php
function conv_date ($data,$inverti=false)
{
  if($inverti)
  {
  list ($g, $m, $y) = explode ("-", $data);
  return "$y-$m-$g";
  }
  else
  {
  list ($y, $m, $d) = explode ("-", $data);
  return "$d-$m-$y";
  }
}

function se_iscritto ($chatid,$tpreturn='NOME')
{
	$chatid = trim($chatid);
	$urlCheck = 'http://www.protezionecivilecasarano.org/gestnew/api/check_reg.php?par='.$chatid;
	$json = file_get_contents($urlCheck);
	$obj = json_decode($json,true);
	$array = $obj[1];
	$cont = $obj[0]["conteggio"];
	$array2 = $array["name_array"];
	$data = $array2[0];
	$nome = $data["nome"];
	$cognome = $data["cognome"];
	
	if($cont == 0)
		return 'NO';
	else
	{
		if($tpreturn == 'NOMECOGNOME')
			return $nome.' '.$cognome;
		if($tpreturn == 'NOME')
			return $nome;
		if($tpreturn == 'COGNOME')
			return $cognome;
	}
	
}

function trova_parola($parola,$descrizione){ // Parola: la parola da cercare | Descrizione: frase in cui cercare
	$descrizione = preg_replace("/\W/", " ", $descrizione); // elimino caratteri speciali
	$des_cerca=explode(" ",$descrizione); // esplodo le singole parolo
	$risultato = count($des_cerca); // conto il totale delle parole esplose
	@$_ritono = false;
	for($i=0; $i<=$risultato; $i++){ // ciclo per fare controllo
		if(@$des_cerca[$i]==@$parola){
			@$_ritono = true; // se la trovo chiudo ciclo e ritorno l'ok
			break;
		}		
	}	
	return $_ritono;
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if(!$update)
{
  exit;
}

header("Content-Type: application/json");

$message = isset($update['message']) ? $update['message'] : "";
$messageId = isset($message['message_id']) ? $message['message_id'] : "";
$chatId = isset($message['chat']['id']) ? $message['chat']['id'] : "";
$senderId = isset($message['chat']['id']) ? $message['chat']['id'] : "";
$firstname = isset($message['chat']['first_name']) ? $message['chat']['first_name'] : "";
$lastname = isset($message['chat']['last_name']) ? $message['chat']['last_name'] : "";
$username = isset($message['chat']['username']) ? $message['chat']['username'] : "";
$date = isset($message['date']) ? $message['date'] : "";
$domanda = isset($message['text']) ? $message['text'] : "";

$domanda = trim($domanda);
$domandaL = trim(strtolower($domanda));
//$risposta = strtolower($domanda);
$risposta = trim('I tecnici sono a lavoro per migliorarmi in modo da farmi rispondere prima e più efficacemente alle tue domande o comandi, dovrai avere pazienza se ancora non capisco tutto quello che mi chiedi. Ti posso fornire la lista dei comandi se mi chiedi "aiuto"');

$lat = 0;
$lon = 0;

date_default_timezone_set('UTC+2');

//---- STAMPA ORARIO
if($domandaL == 'che ore sono?' or $domandaL == 'mi dici l\'orario?' or $domandaL == 'sai dirmi l\'orario?' or $domandaL == 'ore?' or $domandaL == 'mi dici l\'ora?' or $domandaL == 'sai dirmi l\'ora?')
	$risposta = trim(date("H:i:s"));


//---- KEY ISCRIZIONE
if(substr($domandaL,0,10) == 'iscrizione' or substr($domandaL,0,11) == '/iscrizione')
{
	if(substr($domandaL,0,1) == '/')
		$codice = substr($domandaL,12);
	else
		$codice = substr($domandaL,11);
	
	$codsocio = trim($codice);
	$urlUser = 'http://www.cvpc.lecce.it/augusto/api/readuser.php?c='.$codice;
	$json = file_get_contents($urlUser);
	$obj = json_decode($json,true);
	$array = $obj[1];
	$array2 = $array["name_array"];
	$data = $array2[0];
	$nome = $data["nome"];
	$cognome = $data["cognome"];
	$idcord = $data["idcord"];
	
	
	if($nome == '' or $cognome == '')
	{
		switch($idcord)
		{
			case '1': $cord = 'Coordinamento di Lecce'; break;
			case '6': $cord = 'Coordinamento di Taranto'; break;
		}
		
		$risposta = trim('Mi dispiace non ti ho riconosciuto. Ho letto bene il tuo codice per Telegram? Mi risulta >>'.$codice.'<<');
	}
	else
	{
		$risposta = trim('Ciao, ti ho riconosciuto, sei proprio '.$nome.' '.$cognome.' del '.$cord.'! D\'ora in poi saprò come chiamarti quando servira.
		
		Se lo volessi comunicare direttamente in segreteria il tuo codice telegram è '.$chatId);
		$urlUserAppr = 'http://www.cvpc.lecce.it/augusto/api/reguser.php?c='.$codice.'&chatid='.$chatId;
		$json = file_get_contents($urlUserAppr);
	}
	
	if($codsocio == '')
		$risposta = trim('Non posso riconoscerti se non mi fornisci il tuo codice tessera');
}



//---- KEY FRASE
if((trova_parola('dimmi',$domandaL) or trova_parola('dirmi',$domandaL)) and trova_parola('frase',$domandaL))
{
	$urlUser = 'http://www.protezionecivilecasarano.org/gestnew/api/read_frase.php';
	$json = file_get_contents($urlUser);
	$obj = json_decode($json,true);
	$frase = $obj["result"];
		
	$parameters = array('chat_id' => $chatId, "text" => $frase, "parse_mode" => "Markdown");
	$parameters["method"] = "sendMessage";
	echo json_encode($parameters);
	exit;
	
}

//---- KEY CIAO
if($domandaL=='ciao' or $domandaL=='salve' 
or $domandaL=='buongiorno' or $domandaL=='buon giorno'
or $domandaL=='buonasera' or $domandaL=='buona sera'
or $domandaL=='buonpomeriggio' or $domandaL=='buon pomeriggio')
{
	$risposta = "Ciao! Come posso esserti utile?";	
}


$parameters = array('chat_id' => $chatId, "text" => $risposta);
$parameters["method"] = "sendMessage";
echo json_encode($parameters);
