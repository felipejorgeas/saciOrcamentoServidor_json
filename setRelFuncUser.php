<?php

define('WService_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require_once WService_DIR . 'lib/define.inc.php';
require_once WService_DIR . 'lib/function.inc.php';
require_once WService_DIR . 'lib/nusoap/nusoap.php';
require_once WService_DIR . 'classes/XML2Array.class.php';

$telaLogin = true;
$telaRelacionamento = false;

if(isset($_POST['confirm']) && (empty($_POST['usuario']) || empty($_POST['senha']))){
  $msg = 'Favor informar um usu�rio e senha de diretor ou gerente';
}

else if(isset($_POST['confirm']) && (!empty($_POST['usuario']) && !empty($_POST['senha']))){
  /* obtendo algumas configuracoes do sistema */
  $conf = getConfig();
  $ws = sprintf("%s/funcionariows.php", $conf['SISTEMA']['saciWS']);

  /* variaveis recebidas na requisicao */
  $apelido = $_POST['usuario'];
  $senha = $_POST['senha'];

  // url de ws
  $client = new nusoap_client($ws);
  $client->useHTTPPersistentConnection();

  // serial do cliente
  $serail_number_cliente = readSerialNumber();

  $dados = sprintf("<dados>\n\t<apelido>%s</apelido>\n\t<senha>%s</senha>\n</dados>", $apelido, $senha);

  // monta os parametros a serem enviados
  $params = array(
      'crypt' => $serail_number_cliente,
      'dados' => $dados
  );

  // realiza a chamada de um metodo do ws passando os paramentros
  $result = $client->call('listar', $params);
  $res = XML2Array::createArray($result);

  if ($res['resultado']['sucesso'] && isset($res['resultado']['dados']['funcionario'])) {
    $funcionario = $res['resultado']['dados']['funcionario'];

    // verifica o nivel de permissao do usuario
    // 0 - usuario sem permissao para usar o app
    // 1 - usuario com permissao para usar o app
    // 2 - usuario com permissao para usar o app e alterar configuracoes
    switch($funcionario['codigo_cargo']){
      case EMPTYPE_GERENTE:
        $permissao = true;
        break;
      case EMPTYPE_DIRETOR:
        $permissao = true;
        break;
      default:
        $permissao = false;
    }

    if(!$permissao){
      $msg = 'O funcion�rio informado deve ser um diretor ou gerente';
    }

    else{

      $telaLogin = false;
      $telaRelacionamento = true;

      if(!empty($_POST['func']) && !empty($_POST['user'])){

        /* obtendo algumas configuracoes do sistema */
        $relfuncuser = getConfig('RELACAO_FUNC_USER', FILE_REL_FUNC_USER);

        setConfigRelFuncUser($_POST['func'], $_POST['user'], 'RELACAO_FUNC_USER');

        $msg = 'Informa��es gravadas com sucesso<br><br>';
        $msg .= sprintf('Funcion�rio: %d <> Usu�rio: %d', $_POST['func'], $_POST['user']);
      }
    }
  }
  else{
    $msg = 'Funcion�rio n�o encontrado';
  }
}

if($telaLogin){

?>

    <!DOCTYPE html>
    <html>
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>saciOr�amento</title>
      </head>
      </head>
      <body>
        <h1>Login de acesso</h1>
        <form method='post'>
          <input type='hidden' name='confirm' value='1'/>
          <p>Apelido funcion�rio: <input type='text' name='usuario' value='' /></p>
          <p>Senha funcion�rio: <input type='text' name='senha' value='' /></p>
          <p><button type='submit'>Acessar</button></p>
        </form>
        <p><?=$msg?></p>
      </body>
    </html>
<?
}

else if($telaRelacionamento){

?>

    <!DOCTYPE html>
    <html>
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>saciOr�amento</title>
      </head>
      </head>
      <body>
        <h1>Relacionamento entre funcion�rio e usu�rio</h1>
        <form method='post'>
          <input type='hidden' name='confirm' value='1'/>
          <input type='hidden' name='usuario' value='<?=$_POST['usuario']?>'/>
          <input type='hidden' name='senha' value='<?=$_POST['senha']?>'/>
          <p>C�d. Funcion�rio: <input type='text' name='func' value='' /></p>
          <p>C�d. Usu�rio: <input type='text' name='user' value='' /></p>
          <p><button type='submit'>Gravar</button></p>
        </form>
        <p><?=$msg?></p>
      </body>
    </html>
<?
}
?>