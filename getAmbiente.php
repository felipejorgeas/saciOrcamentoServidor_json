<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/define.inc.php';
require_once WService_DIR . 'lib/function.inc.php';
require_once WService_DIR . 'lib/nusoap/nusoap.php';
require_once WService_DIR . 'classes/XML2Array.class.php';
require_once WService_DIR . 'classes/Log.class.php';

/* LOG */
$log = new Log();

/* obtendo algumas configuracoes do sistema */
$conf = getConfig();
$ws = sprintf("%s/ambientews.php", $conf['SISTEMA']['saciWS']);

/* variaveis recebidas na requisicao
 * {Array}: dados(wscallback)
 */
$dados = $_REQUEST['dados'];
$wscallback = $dados['wscallback'];

/* variaveis de retorno do ws */
$wsstatus = 1;
$wsresult = array();

// url de ws
$client = new nusoap_client($ws);
$client->useHTTPPersistentConnection();

// serial do cliente
$serail_number_cliente = readSerialNumber();

$dados = "<dados></dados>";

// grava log
$log->addLog(ACAO_REQUISICAO, "getAmbiente", $dados, SEPARADOR_INICIO);

// monta os parametros a serem enviados
$params = array(
    'crypt' => $serail_number_cliente,
    'dados' => $dados
);

// realiza a chamada de um metodo do ws passando os paramentros
$result = $client->call('listar', $params);
$res = XML2Array::createArray($result);

$wsstatus = 1;

// ambiente padrao
$wsresult[] = array(
    'codigo' => 0,
    'nome' => 'Sem Ambiente'
);

if ($res['resultado']['sucesso'] && isset($res['resultado']['dados']['ambiente'])) {
  $ambientes = array();

  if(key_exists("0", $res['resultado']['dados']['ambiente']))
    $ambientes = $res['resultado']['dados']['ambiente'];
  else
    $ambientes[] = $res['resultado']['dados']['ambiente'];

  foreach($ambientes as $ambiente){
    $wsresult[] = array(
        'codigo' => $ambiente['codigo_ambiente'],
        'nome' => $ambiente['nome_ambiente']
    );
  }
}

// grava log
$log->addLog(ACAO_RETORNO, $wscallback, $wsresult, SEPARADOR_FIM);

/* retorna o resultado */
returnWS($wscallback, $wsstatus, $wsresult);
?>