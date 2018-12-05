<?php
session_start();
include_once 'lib_bitrix24.php';

$dealID=$_GET["deal"];
$userID=$_GET["user"];

$code=$_SESSION["code"];
$server_domain=$_SESSION["server_domain"];
$client_endpoint=$_SESSION["client_endpoint"];
$access_token=$_SESSION["access_token"];

echo "\nСделка для изменения id=".$dealID." и новый пользователь ID = ".$userID;
//echo "\ncode=".$code." и server_domain = ".$server_domain;
//echo "\nclient_endpoint=".$client_endpoint." и access_token = ".$access_token; 
echo "\n Найдем все текущие задачи для этой сделки через crm.activity.list"
. " и поменяем ответсвтенного для всех задач и сделок в цикле:";

/*
//get list of tasks
$arListTasksB24 = executeREST($client_endpoint, 'task.item.list', 
        array(
            'ORDER' => array('RESPONSIBLE_ID' => '1'),
            'FILTER' => array(
                'RESPONSIBLE_ID' => $userID,
                //'UF_CRM_TASK' => 'D_'.$dealID
        )),$access_token);

foreach($arListTasksB24["result"] as $arAllTasks){
        echo "\n TITLE=".$arAllTasks['TITLE']." ID=".$arAllTasks['ID'];
}
//print_r($arListTasksB24);
echo "\n ";
*/
    
//get list of activity of crm
$arAllB24crmActivity = executeREST($client_endpoint, 'crm.activity.list', 
        array(
            'ORDER' => array('ID' => 'DESC'),
            'FILTER' => array(
                'PROVIDER_ID' => 'TASKS',
                'PROVIDER_TYPE_ID' => 'TASK',
                //'RESPONSIBLE_ID' => $userID,
                'OWNER_TYPE_ID'  => '2',//тип сделка
                'OWNER_ID' => $dealID
            )
           ),$access_token);
//print_r($arAllB24crmActivity);
foreach($arAllB24crmActivity["result"] as $arAllTasks){
        $taskID=$arAllTasks['ASSOCIATED_ENTITY_ID'];
        echo "\n TITLE=".$arAllTasks['SUBJECT'].
                ' task ID='.$taskID.
                ' RESPONSIBLE_ID='.$arAllTasks['RESPONSIBLE_ID'];

        //////update RESPONSIBLE_ID for current task
        //print_r(
                executeREST($client_endpoint, 'task.item.delegate', 
                array(
                    'TASKID' => $taskID,
                    'USERID' => $userID
                ),$access_token)//)
        ;
}

//update current deal with new responsible user
$arUPDATEDealB24 = executeREST($client_endpoint, 'crm.deal.update', array(
                    'ID' => $dealID,
                    'fields' => 
                    array(
                           'ASSIGNED_BY_ID' =>   $userID,
                           //'STAGE_ID'    => 'NEW',
                           //'DISABLE_USER_FIELD_CHECK' => true
                    )),$access_token);
//print_r($arUPDATEDealB24);


echo "\n Все данные обновлены";

//////get deals info about fields
//$arAllB24DealsFields = executeREST($client_endpoint, 'crm.deal.fields', array(),$access_token);

//get list of custom fields
//$arAllB24getmanifest = executeREST($client_endpoint, 'task.item.getmanifest', array(),$access_token);
//print_r($arAllB24getmanifest);

