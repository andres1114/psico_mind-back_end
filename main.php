<?php

include_once "com/db_connection.php";
include_once "models/classes/twitter_engine.php";

use TwitterEngine\TwitterEngine;

$json_response = Array();

ob_start();

try {
    if (isset($_POST['jsondata'])) {
        $json_data = json_decode($_POST['jsondata']);
    } else {
        $json_data = json_decode(file_get_contents('php://input'));
    }

    switch ($json_data->action) {
        case 'fetchMatchingUser':
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
        case 'findTweetsAndPerformSentimentAnalysis':
            $pdo_sqlite_db = pdoCreateConnection(array('db_type' => "sqlite", 'db_host' => realpath(__DIR__).'\\..\\db\\sentiments.sqlite3', 'db_user' => "root", 'db_pass' => "", 'db_name' => ""));

            switch ($json_data->rrss) {
                case 'twitter':
                    $sentimentsPythonScriptName = "main.py";
                    $sentimentsPythonScriptPath = realpath(__DIR__)."\\..\\python\\";
                    $sentimentsPythonScriptArgs = "save_logs";

                    $response = new \stdClass();
                    $response->rrss = new \stdClass();
                    $response->rrss->twitter = new \stdClass();

                    $engineClass = new TwitterEngine;
                    $response->rrss->twitter->tweets = $engineClass->fetchPostsByUserId([$json_data->userId], $json_data->postLimitNumber);

                    if ($response->rrss->twitter->tweets->meta->result_count > 0) {
                        $query_args = array(
                            "runid" => $json_data->runId
                        );
                        $query = "INSERT INTO run (insert_date, update_date, value, status) VALUES (DateTime('now'),DateTime('now'),:runid,'ready')";
                        $query_data = pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_01");
                        $runDbId = $query_data[3];

                        for ($x = 0; $x < sizeof($response->rrss->twitter->tweets->data); $x++) {
                            $query_args = array(
                                "rrssid" => 1,
                                "tweettext" => $response->rrss->twitter->tweets->data[$x]->text,
                                "statusenumid" => 1,
                                "runid" => $runDbId
                            );
                            $query = "INSERT INTO sentiment_queue (id_rrss, fecha_insert_sentiment_queue, fecha_update_sentiment_queue, texto_evaluacion_sentiment_queue, estado_id_sentiment_queue, run_id) VALUES (:rrssid, DateTime('now'),DateTime('now'), :tweettext, :statusenumid, :runid)";
                            pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_02");
                        }

                        #$cmd = "python ".$sentimentsPythonScriptPath.$sentimentsPythonScriptName." ".$sentimentsPythonScriptArgs;
                        $cmd = "C:\Users\Andrés\AppData\Local\Programs\Python\Python310\python.exe ".$sentimentsPythonScriptPath.$sentimentsPythonScriptName." ".$sentimentsPythonScriptArgs;
                        pclose(popen($cmd, 'r'));
                        $json_response["cmd"] = $cmd;
                    }

                    break;
            }

            $json_response["response"] = $response;
            break;
        case "showPerformedTweetSentimentAnalysis":
            $pdo_sqlite_db = pdoCreateConnection(array('db_type' => "sqlite", 'db_host' => realpath(__DIR__).'\\..\\db\\sentiments.sqlite3', 'db_user' => "root", 'db_pass' => "", 'db_name' => ""));
            $sentimentsPythonScriptName = "main.py";
            $sentimentsPythonScriptPath = realpath(__DIR__)."\\..\\python\\";
            $sentimentsPythonScriptArgs = "save_logs";

            $response = new \stdClass();

            $query_args = array(
                "runvalue" => $json_data->runid
            );
            $query = "SELECT * FROM sentiment_queue INNER JOIN run ON sentiment_queue.run_id = run.id WHERE sentiment_queue.estado_id_sentiment_queue = 1 AND run.value = :runvalue";
            $query_data_1 = pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_03");

            $query_args = array(
                "runvalue" => $json_data->runid
            );
            $query = "SELECT * FROM sentiment_queue INNER JOIN run ON sentiment_queue.run_id = run.id WHERE sentiment_queue.estado_id_sentiment_queue = 2 AND run.value = :runvalue";
            $query_data_2 = pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_04");

            $response->posts = Array();

            $sentimentPositiveCounter = 0;
            $sentimentNeutraleCounter = 0;
            $sentimentNegativeCounter = 0;
            $sentimentPositiveAverage = 0;
            $sentimentNeutralAverage = 0;
            $sentimentNegativeAverage = 0;
            $sentimentTotal = $query_data_1[1] + $query_data_2[1];

            for ($x = 0; $x < $query_data_2[1]; $x++) {
                $tempPostObject = new \stdClass();
                $tempPostObject->postData = new \stdClass();
                $tempPostObject->postData->content = $query_data_2[0][$x]["texto_evaluacion_sentiment_queue"];
                $tempPostObject->postData->postSentiment = $query_data_2[0][$x]["puntaje_sentiment_queue"];

                if ($tempPostObject->postData->postSentiment >= 0.3 && $tempPostObject->postData->postSentiment <= 1) {
                    $sentimentPositiveCounter++;
                    $tempPostObject->postData->postSentimentEvaluation = "positive";
                }
                if ($tempPostObject->postData->postSentiment >= -0.3 && $tempPostObject->postData->postSentiment <= 0.3) {
                    $sentimentNeutraleCounter++;
                    $tempPostObject->postData->postSentimentEvaluation = "neutral";
                }
                if ($tempPostObject->postData->postSentiment <= -0.3 && $tempPostObject->postData->postSentiment >= -1) {
                    $sentimentNegativeCounter++;
                    $tempPostObject->postData->postSentimentEvaluation = "negative";
                }

                array_push($response->posts, $tempPostObject);
            }

            if ($sentimentTotal > 0) {
                $sentimentPositiveAverage = ($sentimentPositiveCounter / $sentimentTotal);
                $sentimentNeutralAverage = ($sentimentNeutraleCounter / $sentimentTotal);
                $sentimentNegativeAverage = ($sentimentNegativeCounter / $sentimentTotal);
            }

            $response->sentimentData = new \stdClass();
            $response->sentimentData->numberOfPostivePosts = $sentimentPositiveCounter;
            $response->sentimentData->numberOfNeutralPosts = $sentimentNeutraleCounter;
            $response->sentimentData->numberOfNegativePosts = $sentimentNegativeCounter;
            $response->sentimentData->averagePositivePosts = $sentimentPositiveAverage;
            $response->sentimentData->averageNeutralPosts = $sentimentNeutralAverage;
            $response->sentimentData->averageNegativePosts = $sentimentNegativeAverage;
            $response->sentimentData->totalPosts = $sentimentTotal;

            if ($sentimentPositiveAverage > $sentimentNeutralAverage && $sentimentPositiveAverage > $sentimentNegativeAverage) {
                $response->sentimentData->evaluation = "positive";
            }
            if ($sentimentNeutralAverage >= $sentimentPositiveAverage && $sentimentNeutralAverage >= $sentimentNegativeAverage) {
                $response->sentimentData->evaluation = "neutral";
            }
            if ($sentimentNegativeAverage > $sentimentNeutralAverage && $sentimentNegativeAverage > $sentimentPositiveAverage) {
                $response->sentimentData->evaluation = "negative";
            }

            $response->runData = new \stdClass();
            $response->runData->pendingPosts = $query_data_1[1];
            $response->runData->completedPosts = $query_data_2[1];

            if ($response->runData->pendingPosts > 0) {
                #$cmd = "python ".$sentimentsPythonScriptPath.$sentimentsPythonScriptName." ".$sentimentsPythonScriptArgs;
                $cmd = "C:\Users\Andrés\AppData\Local\Programs\Python\Python310\python.exe ".$sentimentsPythonScriptPath.$sentimentsPythonScriptName." ".$sentimentsPythonScriptArgs;
                pclose(popen($cmd, 'r'));
                $json_response["cmd"] = $cmd;
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