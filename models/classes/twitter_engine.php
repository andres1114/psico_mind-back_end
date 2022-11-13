<?php

namespace TwitterEngine;

foreach (glob(realpath(__DIR__)."\\..\\..\\com\\twitter\\*.php") as $filename) {
    include $filename;
}
foreach (glob(realpath(__DIR__)."\\..\\..\\com\\twitter\\Enum\\*.php") as $filename) {
    include $filename;
}

use Noweh\TwitterApi\Client;
use Noweh\TwitterApi\Enum\Modes;

class TwitterEngine {
    private $envData;
    private $twitterClient;

    public function __construct() {
        $this->envData = new \stdClass();
        $this->envData->twitterApiKey = "";
        $this->envData->twitterApiKeySecret = "";
        $this->envData->twitterBearerToken = "";

        $this->loadEnvData();
        $this->createTwitterClient();
    }

    private function createTwitterClient() {
         try {
             $settings = [
                 'account_id' => $this->envData->twitterAppId,
                 'consumer_key' => $this->envData->twitterApiKey,
                 'consumer_secret' => $this->envData->twitterApiKeySecret,
                 'bearer_token' => $this->envData->twitterBearerToken,
                 'access_token' => 'ACCESS_TOKEN',
                 'access_token_secret' => 'ACCESS_TOKEN_SECRET'
             ];

             $this->twitterClient = new Client($settings);
         } catch (Exceotion $e) {
             throw new Exception('ERR_CANT_CREATE_TWITTER_CLIENT: there was an error trying to create the twitter client: '.$e->getMessage());
         }

    }

    private function loadEnvData() {
        try {
            $jsonFileContents = file_get_contents(realpath(__DIR__)."\\..\\..\\env.json");

            $stdClasJsonObject = json_decode($jsonFileContents, true);

            $this->envData->twitterAppId = $stdClasJsonObject['twitter']['appId'];
            $this->envData->twitterApiKey = $stdClasJsonObject['twitter']['apiToken'];
            $this->envData->twitterApiKeySecret = $stdClasJsonObject['twitter']['apiKeySecret'];
            $this->envData->twitterBearerToken = $stdClasJsonObject['twitter']['bearerToken'];
        } catch (Exception $e) {
            throw new Exception('ERR_CANT_LOAD_ENV_JSON: there was an error trying to load the env json data: '.$e->getMessage());
        }
    }

    public function fetchMatchingTwitterAccounts($name) {
        $response = $this->twitterClient->userSearch()->findByIdOrUsername($name, Modes::username)->performRequest();
        return $response;
    }
}