<?php
// Set the namespace defined in your config file
namespace HealthPartners\Institute\FitbitDataSync;

// For Guzzle HTTP Client - Use REDcap provided
//include_once __DIR__ . '/vendor/autoload.php';

use REDCap;

class FitbitDataSync extends \ExternalModules\AbstractExternalModule
{
    private $fitbitClientId;
    private $fitbitClientSecret;
    private $fitbitAuthURL;
    private $fitbitTokenURL;
    private $fitbitAccessTokenField;
    private $fitbitRefreshTokenField;
    private $fitbitScope;
    private $fitbitRedirectURL;
    private $fitbitInviteURL;
    private $fitbitProjectSalt;
    private $fitbitRecordEncryptKey;
    private $fitbitSleepLogStoreField;
    private $fitbitSleepLogRepeatingForm;
    private $fitbitSleepLogDateField;

    public function __construct()
    {
        parent::__construct();
    }

    public function generateInviteURL()
    {
        $record_id_field = REDCap::getRecordIdField();
        $this->fitbitInviteURLField = $this->getProjectSetting("participant-auth-url-field");
        $this->fitbitAuthURL = $this->getProjectSetting("fitbit-auth-url");
        $this->fitbitClientId = $this->getProjectSetting("fitbit-client-id");
        $this->fitbitScope = $this->getProjectSetting("fitbit-scope");
        $this->fitbitRedirectURL =  $this->getProjectSetting("fitbit-redirect-url");
        $this->fitbitProjectSalt =  $this->getProjectSetting("fitbit-salt");
        $this->fitbitRecordEncryptKey = $this->getProjectSetting("participant-encrypt-key-field");
        $this->fitbitRefreshTokenField = $this->getProjectSetting("refresh-token-field");

        $filterLogic = "([" . $this->fitbitInviteURLField . "]" . " = ''" . ")  ";
        //echo "### : ". $filterLogic;
        if (isset($this->fitbitInviteURLField)) {
            $readParams = array('project_id' => $this->getProjectId(), 'fields' => array($record_id_field, $this->fitbitInviteURL), 'filterLogic' => $filterLogic);
            $data = REDCap::getData($readParams);
            //print_r($data);
            foreach ($data as $recordid => $event) {
                foreach ($event as $eventid => $curr_record) {
                    $url = "";
                    $uniqueOAuthState = "";
                    $uniqueOAuthState = md5($this->fitbitProjectSalt . NOW . $curr_record[$record_id_field]);
                    $url = $this->fitbitAuthURL . "?response_type=code&client_id=$this->fitbitClientId&scope=" . rawurlencode($this->fitbitScope) . "&redirect_uri=" . rawurlencode($this->fitbitRedirectURL) . "&state=" . $uniqueOAuthState;
                    if (!empty($url) && !empty($uniqueOAuthState)) {
                        $writeDataArray = array();
                        $writeDataArray[$curr_record[$record_id_field]][$eventid][$record_id_field] = $curr_record[$record_id_field];
                        $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitRecordEncryptKey] = $uniqueOAuthState;
                        $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitInviteURLField] = $url;
                        //print_r($writeDataArray);
                        $saveREDCapResponse = REDCap::saveData($this->getProjectId(), 'array', $writeDataArray, 'overwrite');
                        //print_r($saveREDCapResponse);
                    }
                }
            }
        }
        return TRUE;
    }

    /**
     * Used to get access token from Fitbit service through token url
     */

    public function getAccessToken($code, $state)
    {
        $record_id_field = REDCap::getRecordIdField();
        $this->fitbitClientId = $this->getProjectSetting("fitbit-client-id");
        $this->fitbitClientSecret = $this->getProjectSetting("fitbit-client-secret");
        $this->fitbitTokenURL = $this->getProjectSetting("fitbit-token-url");
        $this->fitbitRedirectURL =  $this->getProjectSetting("fitbit-redirect-url");
        $this->fitbitRecordEncryptKey = $this->getProjectSetting("participant-encrypt-key-field");
        $this->fitbitAccessTokenField = $this->getProjectSetting("access-token-field");
        $this->fitbitRefreshTokenField = $this->getProjectSetting("refresh-token-field");
        $basicToken = "";
        $basicToken = base64_encode($this->fitbitClientId . ":" . $this->fitbitClientSecret);

        if (isset($this->fitbitTokenURL) && !empty($basicToken) && !empty($code) && !empty($state)) {
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request('POST', $this->fitbitTokenURL, [
                    'form_params' => [
                        'code' => $code,
                        'grant_type' => 'authorization_code',
                        'client_id' => $this->fitbitClientId,
                        'state' => $state,
                        'redirect_uri' => $this->fitbitRedirectURL
                    ],
                    'headers' => [
                        'cache-control' => 'no-cache',
                        'content-type' => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic ' . $basicToken
                    ]
                ]);


                if ($response->getStatusCode() == 200) {

                    $result = json_decode($response->getBody()->getContents(), true);

                    if (isset($this->fitbitRecordEncryptKey) && isset($result['access_token']) && isset($result['refresh_token'])) {

                        $filterLogic = "[" . $this->fitbitRecordEncryptKey . "]" . " =  '$state'";

                        $readParams = array('project_id' => $this->getProjectId(), 'return_format' => 'array', 'fields' => array($record_id_field, $this->fitbitRecordEncryptKey), 'filterLogic' => $filterLogic);
                        //$recordIddataJSON = REDCap::getData($this->getProjectId(), 'json', NULL, NULL, NULL, FALSE, FALSE, FALSE);
                        //print_r($recordIddataJSON);
                        //$data = json_decode($recordIddataJSON, TRUE);
                        $data = REDCap::getData($readParams);
                        //print_r($data);
                        if (count($data) == 1) {
                            foreach ($data as $recordid => $event) {
                                foreach ($event as $eventid => $curr_record) {
                                    $writeDataArray = array();
                                    $writeDataArray[$curr_record[$record_id_field]][$eventid][$record_id_field] = $curr_record[$record_id_field];
                                    $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitAccessTokenField] = $result['access_token'];
                                    $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitRefreshTokenField] = $result['refresh_token'];
                                    $saveREDCapResponse = REDCap::saveData($this->getProjectId(), 'array', $writeDataArray, 'overwrite');
                                    //print_r($saveREDCapResponse);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Message: ' . $e->getMessage();
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }



    /**
     * Used to get sleep log data from Fitbit server and store to configured repleating form
     */
    public function getLastNightSleepLog()
    {
        $record_id_field = REDCap::getRecordIdField();
        $this->fitbitClientId = $this->getProjectSetting("fitbit-client-id");
        $this->fitbitClientSecret = $this->getProjectSetting("fitbit-client-secret");
        $this->fitbitTokenURL = $this->getProjectSetting("fitbit-token-url");
        $this->fitbitRedirectURL =  $this->getProjectSetting("fitbit-redirect-url");
        $this->fitbitAccessTokenField = $this->getProjectSetting("access-token-field");
        $this->fitbitRefreshTokenField = $this->getProjectSetting("refresh-token-field");
        $this->fitbitSleepLogStoreField = $this->getProjectSetting("sleeplog-store-field");
        $this->fitbitSleepLogRepeatingForm = $this->getProjectSetting("sleeplogrepeating-form");
        $this->fitbitSleepLogDateField = $this->getProjectSetting("sleeplog-date-field");

        if (
            isset($this->fitbitSleepLogStoreField) && !empty($this->fitbitSleepLogStoreField)
            && isset($this->fitbitSleepLogRepeatingForm) &&  !empty($this->fitbitSleepLogRepeatingForm)
            && isset($this->fitbitSleepLogDateField) && !empty($this->fitbitSleepLogDateField)
        ) {
            // everthing set so continue further
        } else {
            return FALSE;
        }

        $basicToken = "";
        $basicToken = base64_encode($this->fitbitClientId . ":" . $this->fitbitClientSecret);
        $filterLogic = "[" . $this->fitbitRefreshTokenField . "]" . " !=  ''";

        //Loop through all the participants who has refersh token in project database
        $readParams = array('project_id' => $this->getProjectId(), 'return_format' => 'array', 'fields' => array($record_id_field, $this->fitbitRefreshTokenField), 'filterLogic' => $filterLogic);
        $data = REDCap::getData($readParams);
        if (count($data) > 0) {
            foreach ($data as $recordid => $event) {
                foreach ($event as $eventid => $curr_record) {
                    //echo "##### :: " .  $curr_record[$this->fitbitRefreshTokenField];
                    try {
                        $client = new \GuzzleHttp\Client();
                        $result = [];
                        $response = $client->request('POST', $this->fitbitTokenURL, [
                            'form_params' => [
                                'refresh_token' => $curr_record[$this->fitbitRefreshTokenField],
                                'grant_type' => 'refresh_token'
                            ],
                            'headers' => [
                                'cache-control' => 'no-cache',
                                'content-type' => 'application/x-www-form-urlencoded',
                                'Authorization' => 'Basic ' . $basicToken
                            ]
                        ]);

                        if ($response->getStatusCode() == 200) {
                            $result = json_decode($response->getBody()->getContents(), true);
                            $writeDataArray = array();
                            $writeDataArray[$curr_record[$record_id_field]][$eventid][$record_id_field] = $curr_record[$record_id_field];
                            $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitAccessTokenField] = $result['access_token'];
                            $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitRefreshTokenField] = $result['refresh_token'];
                            $saveREDCapResponse = REDCap::saveData($this->getProjectId(), 'array', $writeDataArray, 'overwrite');
                        }

                        $yesterdayParamFormat = date('Y-m-d', strtotime("-1 days"));
                        $url = "https://api.fitbit.com/1.2/user/-/sleep/date/" . $yesterdayParamFormat . ".JSON";
                        //$url = "https://api.fitbit.com/1.2/user/-/sleep/date/2019-12-03.json";

                        $sleepLogResponse = $client->request('GET', $url, [
                            'headers' => [
                                'cache-control' => 'no-cache',
                                'accept' => 'application/json',
                                'Authorization' => 'Bearer ' . $result['access_token']
                            ]
                        ]);

                        if ($sleepLogResponse->getStatusCode() == 200 && isset($yesterdayParamFormat)) {
                            $responseContent =  $sleepLogResponse->getBody()->getContents();
                            $lastInstanceId = $this->getLastRepeatingFormInstance($curr_record[$record_id_field], $this->fitbitSleepLogRepeatingForm);
                            $redcapWriteJSONArray =  [
                                [
                                    "record_id"  => $curr_record[$record_id_field],
                                    "redcap_repeat_instrument" => $this->fitbitSleepLogRepeatingForm,
                                    "redcap_repeat_instance" => $lastInstanceId + 1,
                                    $this->fitbitSleepLogStoreField => $responseContent,
                                    $this->fitbitSleepLogDateField => $yesterdayParamFormat
                                ]
                            ];

                            $redcapWriteJSON = json_encode($redcapWriteJSONArray, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE,);
                            $redcapWriteJSONResponse = REDCap::saveData($this->getProjectId(), 'json', $redcapWriteJSON, 'overwrite');
                        }
                    } catch (\Exception $e) {
                        echo 'Message: ' . $e->getMessage();
                    }
                }
            }
        }
        return TRUE;
    }


    /**
     * Used to get Activity log data from Fitbit server and store to configured repleating form
     */
    public function getLastNightActivityLog()
    {
        $record_id_field = REDCap::getRecordIdField();
        $this->fitbitClientId = $this->getProjectSetting("fitbit-client-id");
        $this->fitbitClientSecret = $this->getProjectSetting("fitbit-client-secret");
        $this->fitbitTokenURL = $this->getProjectSetting("fitbit-token-url");
        $this->fitbitRedirectURL =  $this->getProjectSetting("fitbit-redirect-url");
        $this->fitbitAccessTokenField = $this->getProjectSetting("access-token-field");
        $this->fitbitRefreshTokenField = $this->getProjectSetting("refresh-token-field");
        $this->fitbitActivityLogStoreField = $this->getProjectSetting("activitylog-store-field");
        $this->fitbitActivityLogRepeatingForm = $this->getProjectSetting("activitylogrepeating-form");
        $this->fitbitActivityLogDateField = $this->getProjectSetting("activitylog-date-field");

        if (
            isset($this->fitbitActivityLogStoreField) && !empty($this->fitbitActivityLogStoreField)
            && isset($this->fitbitActivityLogRepeatingForm) &&  !empty($this->fitbitActivityLogRepeatingForm)
            && isset($this->fitbitActivityLogDateField) && !empty($this->fitbitActivityLogDateField)
        ) {
            // everthing set so continue further
        } else {
            return FALSE;
        }

        $basicToken = "";
        $basicToken = base64_encode($this->fitbitClientId . ":" . $this->fitbitClientSecret);
        $filterLogic = "[" . $this->fitbitRefreshTokenField . "]" . " !=  ''";

        //Loop through all the participants who has refersh token in project database
        $readParams = array('project_id' => $this->getProjectId(), 'return_format' => 'array', 'fields' => array($record_id_field, $this->fitbitRefreshTokenField), 'filterLogic' => $filterLogic);
        $data = REDCap::getData($readParams);
        if (count($data) > 0) {
            foreach ($data as $recordid => $event) {
                foreach ($event as $eventid => $curr_record) {
                    //echo "##### :: " .  $curr_record[$this->fitbitRefreshTokenField];
                    try {
                        $client = new \GuzzleHttp\Client();
                        $result = [];
                        $response = $client->request('POST', $this->fitbitTokenURL, [
                            'form_params' => [
                                'refresh_token' => $curr_record[$this->fitbitRefreshTokenField],
                                'grant_type' => 'refresh_token'
                            ],
                            'headers' => [
                                'cache-control' => 'no-cache',
                                'content-type' => 'application/x-www-form-urlencoded',
                                'Authorization' => 'Basic ' . $basicToken
                            ]
                        ]);

                        if ($response->getStatusCode() == 200) {
                            $result = json_decode($response->getBody()->getContents(), true);
                            $writeDataArray = array();
                            $writeDataArray[$curr_record[$record_id_field]][$eventid][$record_id_field] = $curr_record[$record_id_field];
                            $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitAccessTokenField] = $result['access_token'];
                            $writeDataArray[$curr_record[$record_id_field]][$eventid][$this->fitbitRefreshTokenField] = $result['refresh_token'];
                            $saveREDCapResponse = REDCap::saveData($this->getProjectId(), 'array', $writeDataArray, 'overwrite');
                        }

                        $yesterdayParamFormat = date('Y-m-d', strtotime("-1 days"));
                        $url = "https://api.fitbit.com/1/user/-/activities/date/" . $yesterdayParamFormat . ".json";
                        //$url =  "https://api.fitbit.com/1/user/-/activities/date/2021-10-11.json"

                        $activityLogResponse = $client->request('GET', $url, [
                            'headers' => [
                                'cache-control' => 'no-cache',
                                'accept' => 'application/json',
                                'Authorization' => 'Bearer ' . $result['access_token']
                            ]
                        ]);


                        if ($activityLogResponse->getStatusCode() == 200 && isset($yesterdayParamFormat)) {
                            $responseContent =  $activityLogResponse->getBody()->getContents();


                            $lastInstanceId = $this->getLastRepeatingFormInstance($curr_record[$record_id_field], $this->fitbitActivityLogRepeatingForm);
                            $redcapWriteJSONArray =  [
                                [
                                    "record_id"  => $curr_record[$record_id_field],
                                    "redcap_repeat_instrument" => $this->fitbitActivityLogRepeatingForm,
                                    "redcap_repeat_instance" => $lastInstanceId + 1,
                                    $this->fitbitActivityLogStoreField => $responseContent,
                                    $this->fitbitActivityLogDateField => $yesterdayParamFormat
                                ]
                            ];
                            //print_r($redcapWriteJSONArray);
                            $redcapWriteJSON = json_encode($redcapWriteJSONArray, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE,);
                            $redcapWriteJSONResponse = REDCap::saveData($this->getProjectId(), 'json', $redcapWriteJSON, 'overwrite');
                        }
                    } catch (\Exception $e) {
                        echo 'Message: ' . $e->getMessage();
                    }
                }
            }
        }
        return TRUE;
    }




    /**
     * Used to get last repating form instance number to generate next instance
     */
    private function getLastRepeatingFormInstance($record_id, $repeatingForm)
    {
        $default = 0;
        $record_id_field = REDCap::getRecordIdField();
        if (isset($repeatingForm) && !empty($repeatingForm)) {
            //Loop through all the repeating form instances to find max value
            $readParams = array('project_id' => $this->getProjectId(), 'records' => array($record_id), 'return_format' => 'json', 'fields' => array($record_id_field, $repeatingForm . "_complete"));
            $jsonData = REDCap::getData($readParams);
            if (!empty($jsonData)) {
                $dataArr = json_decode($jsonData, true);
                foreach ($dataArr as $keyIndex => $data) {
                    if (isset($data["redcap_repeat_instance"]) && !empty($data["redcap_repeat_instance"])) {
                        if ($data["redcap_repeat_instance"] > $default) {
                            $default = $data["redcap_repeat_instance"];
                        }
                    }
                }
            }
        }
        return $default;
    }
}
