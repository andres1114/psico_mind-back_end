<?php

include_once "com/db_connection.php";
use TwitterEngine\TwitterEngine;

$pdo_sqlite_db = pdoCreateConnection(array('db_type' => "sqlite", 'db_host' => realpath(__DIR__).'\\..\\db\\sentiments.sqlite3', 'db_user' => "root", 'db_pass' => "", 'db_name' => ""));
$json_response = Array();

ob_start();

try {
    if (isset($_POST['jsondata'])) {
        $json_data = json_decode($_POST['jsondata']);
    } else {
        $json_data = json_decode(file_get_contents('php://input'));
    }

    switch ($json_data->action) {
        case "fetchMatchingUser":
            $response = new \stdClass();
            $response->rrss = new \stdClass();

            for ($x = 0; $x < sizeof($json_data->rrssList); $x++) {
                $engineClass = null;
                switch ($json_data->rrssList[$x]) {
                    case 'twitter':
                        $response->rrss->twitter = new \stdClass();

                        $engineClass = new TwitterEngine;
                        $response->rrss->twitter->users = $engineClass->fetchMatchingTwitterAccounts($json_data->rrssUserName);
                        break;
                }
            }

            $json_response["response"] = $response;
            break;
        default:
            throw new RuntimeException("Caught exception: PHP error, action header '" . $json_data->action . "' not found");
            break;
    }


} catch (Exception $e) {
    $json_response["statusCode"] = 500;
    $json_response["errorMessage"] = $e->getMessage();
}

echo json_encode($json_response);