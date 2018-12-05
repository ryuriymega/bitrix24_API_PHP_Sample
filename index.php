<?php
session_start();
include_once 'lib_bitrix24.php';

function redirect($url, $code = 302)
{
   header('Location: '.$url, true, $code);
    exit();
}

define('APP_ID', 'local.5bd3b42acdb961.XXXXXXXX'); // take it from Bitrix24 after adding a new application
define('APP_SECRET_CODE', 'XXXXXXXXXXXXXXXXXXXXXXX'); // take it from Bitrix24 after adding a new application
define('APP_REG_URL', 'https://megasellers.sell.systems/bitrix24/index.php'); // the same URL you should set when adding a new application in Bitrix24

$bodyText='';

$domain = isset($_REQUEST['portal']) ? $_REQUEST['portal'] : ( isset($_REQUEST['domain']) ? $_REQUEST['domain'] : 'empty');

$step = 0;

if (isset($_REQUEST['portal'])) $step = 1;
if (isset($_REQUEST['code']))$step = 2;


$btokenRefreshed = null;

$arScope = array('user','crm','task');
switch ($step) {
    case 1:
        // we need to get the first authorization code from Bitrix24 where our application is _already_ installed
        requestCode($domain);
        break;

    case 2:
        //we've got the first authorization code and use it to get an access_token and a refresh_token (if you need it later)
        //echo "step 2 (getting an authorization code):<pre>";
        //print_r($_REQUEST);
        $bodyText=$bodyText."</pre><br/>";

        $code=$_REQUEST['code'];
        $server_domain=$_REQUEST['server_domain'];        
        $_SESSION['code']=$code;
        $_SESSION['server_domain']=$server_domain;
        
        $arAccessParams = requestAccessToken($code, $server_domain);
        
        $client_endpoint=$arAccessParams['client_endpoint'];
        $access_token=$arAccessParams['access_token'];        
        $_SESSION['client_endpoint']=$client_endpoint;
        $_SESSION['access_token']=$access_token;
        
        //echo "step 3 (getting an access token):<pre>";
        //print_r($arAccessParams);
        $bodyText=$bodyText."</pre><br/>";

        //get user info
        $arCurrentB24User = executeREST($client_endpoint, 'user.current', array(),$access_token);
        
        //get deals info
        $arAllB24Deals = executeREST($client_endpoint, 'crm.deal.list', array(),$access_token);
        
        //get users info
        $arAllB24Users = executeREST($client_endpoint, 'user.get', array(),$access_token);
                    
        break;
    default:
        break;
}


if($step == 0) {
    $bodyText=$bodyText.'Bitrix24:<br/>'.
    '<form action="https://megasellers.sell.systems/bitrix24/index.php" method="post" name="authForm">'.
        '<input type="text" style="margin-top:10px;" class="ui-autocomplete-input" name="portal" value="b24-k4by72.bitrix24.ru"><br>'.
        //'<input type="text" name="APP_ID" style="width:350px" placeholder="APP_ID"><br>'.
        //'<input type="text" name="APP_SECRET_CODE" style="width:350px" placeholder="APP_SECRET_CODE"><br>'.
        '<input type="text" style="margin-top:10px;" name="APP_REG_URL" style="width:350px" value="https://megasellers.sell.systems/bitrix24/index.php"><br>'.
        '<input type="submit" style="margin-top:10px;" class="ui-button ui-widget ui-corner-all" value="Перейти в панель управления.">'.
    '</form>';
}
elseif ($step == 2) {
     $bodyText=$bodyText.
     '<script>'.
       '$( function() {'.
        '$( "input" ).checkboxradio();'.
    '} );</script>';
    $bodyText=$bodyText.
        '<div class="widget">'.
        '<h1>Пользователь: '.
            $arCurrentB24User["result"]["NAME"] . " " . $arCurrentB24User["result"]["LAST_NAME"].
        '</h1>';
    
    $bodyText=$bodyText.
        '<h2>Список доступных пользователей:</h2>';
    $bodyText=$bodyText.'<fieldset>'.
        '<label for="select_user">Выберите нового пользователя: </label>'.
        '<select name="select_user" id="select_user">';            
    foreach($arAllB24Users["result"] as $allUsers){
        $bodyText=$bodyText.
                '<option value='.$allUsers["ID"].'>'.$allUsers["NAME"].$allUsers["LAST_NAME"].'</option>';
    }
    $bodyText=$bodyText.'</select>';
    
    //print_r($arCurrentB24User);
    //print_r($arAllB24Deals);
    $bodyText=$bodyText.
        '<h2>Операции со сделками:</h2>'.
        '<fieldset style="width: 576px;">'.
        '<legend>Найденные сделки:</legend>';
    $countOfDeals=1;
    foreach($arAllB24Deals["result"] as $allDeals){
        $bodyText=$bodyText.
            '<label for="checkbox-nested-'.$countOfDeals.'">id='.$allDeals['ID'].'</label>'.
            '<input type="checkbox" name="checkbox-nested-'.$countOfDeals.'" id="checkbox-nested-'.$countOfDeals.'">';
            
        $bodyText=$bodyText."<button id=\"opener\" ".
                "class=\"ui-button ui-widget ui-corner-all\" ".
                "onclick=\""
                . "changeDealsTasksUser(".
                    $allDeals["ID"].
                ");\">Изменить пользователя для сделки : [".$allDeals["TITLE"]."]</button><br>";
        
        $countOfDeals++;
    }
    $bodyText=$bodyText.'</fieldset>';
    $bodyText=$bodyText.'</div>';
    $bodyText=$bodyText.'<textarea style="width:600px;height:200px;" id="txtResult">Результат выполнения операции:</textarea>';
}



function requestCode ($domain) {
    $url = 'https://' . $domain . '/oauth/authorize/' .
        '?client_id=' . urlencode(APP_ID);
    redirect($url);
}

function requestAccessToken ($code, $server_domain) {
    $url = 'https://' . $server_domain . '/oauth/token/?' .
        'grant_type=authorization_code'.
        '&client_id='.urlencode(APP_ID).
        '&client_secret='.urlencode(APP_SECRET_CODE).
        '&code='.urlencode($code);
    return executeHTTPRequest($url);
}
?>




<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bitrix 24 demo</title>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://jqueryui.com/jquery-wp-content/themes/jqueryui.com/style.css">
  
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>  
  
  <style>
    fieldset1 {
      border: 0;
    }
    label1 {
      display: block;
      margin: 30px 0 0 0;
    }
    .overflow {
      height: 200px;
    }
  </style>
  
  <script>
  
  $( function() {
    $( ".widget .widget a, .widget button" ).button();
    $( "button, a" ).click( function( event ) {
      event.preventDefault();
    } );
    
     $( "#select_user" ).selectmenu();
  } );
  </script>
  <script>
function changeDealsTasksUser(dealID){
    userID=document.getElementById("select_user").value;
    if (window.XMLHttpRequest)
          {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
          }
        else
          {// code for IE6, IE5
                xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
          }
         xmlhttp.onreadystatechange=function(){
                if(xmlhttp.readyState==4 && xmlhttp.status==200){
                        ///return response when loaded
                        var responseStr=xmlhttp.responseText;
                        //showCustomAlert(responseStr);
                        window.alert("Операция выполнена для сделки с ID="+dealID);                       
                        document.getElementById("txtResult").innerHTML="Результат выполнения операции:"+responseStr;
                }
         }
        ///execute script
        xmlhttp.open("POST",
            "changeDealsAndTasksUsers.php?deal="+dealID+
                "&user="+userID
        ,true);
        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xmlhttp.send();
}
</script>


  </head>
<body>
    
    <?php echo $bodyText;?>    
    
</body>
</html>
