<?php

/***** ***** ***** ***** *****
* Send a curl post request after each afterSurveyComplete event
*
* @author IrishWolf
* @copyright 2023 Nerds Go Casual e.V.
* @license GPL v3
* @version 1.0.0
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
***** ***** ***** ***** *****/

class WebhookDiscordLimeSurvey_EGH extends PluginBase
	{
		protected $storage = 'DbStorage';
		static protected $description = 'A simple Webhook for LimeSurvey';
		static protected $name = 'WebhookDiscordLimeSurvey_EGH';
		protected $surveyId;

		public function init()
			{
				$this->subscribe('afterSurveyComplete'); // After Survey Complete
			}

		protected $settings = array(
			'sUrl' => array(
				'type' => 'string',
				'label' => 'The default URL to send the webhook to:',
				'help' => 'To test get one from https://webhook.site'
			),
			'sId' => array(
				'type' => 'string',
				'default' => '000000',
				'label' => 'The ID of the surveys:',
				'help' => 'The unique number of the surveys. You can set multiple surveys with an "," as separator. <br> Example: 123456, 234567, 345678'
			),
            'sAuthToken' => array(
                'type' => 'string',
                'label' => 'API Authentication Token',
                'help' => 'Maybe you need a token to verify your request? <br> This will be send in plain text / not encoded!'
            ),
			'sBug' => array(
				'type' => 'select',
				'options' => array(
					0 => 'No',
					1 => 'Yes'
				),
				'default' => 0,
				'label' => 'Enable Debug Mode',
				'help' => 'Enable debugmode to see what data is transmitted. <br> Respondents will see this as well so you should turn this off for live surveys'
			)
		);

		/***** ***** ***** ***** *****
		* Send the webhook on completion of a survey
		* @return array | response
		***** ***** ***** ***** *****/
		public function afterSurveyComplete() {
            $oEvent = $this->getEvent();
            $surveyId = $oEvent->get('surveyId');
            $hookSurveyId = $this->get('sId', null, null, $this->settings['sId']);
            $hookSurveyIdArray = explode(',', preg_replace('/\s+/', '', $hookSurveyId));
            if (in_array($surveyId, $hookSurveyIdArray)) {
                $this->callWebhook('afterSurveyComplete');
                }
            return;
			}

		/***** ***** ***** ***** *****
		* Calls a webhook
		* @return array | response
		***** ***** ***** ***** *****/
		private function callWebhook($comment)
			{
				$time_start = microtime(true);
				$event = $this->getEvent();
				$surveyId = $event->get('surveyId');
				$responseId = $event->get('responseId');
				$response = $this->pluginManager->getAPI()->getResponse($surveyId, $responseId);
				$submitDate = $response['submitdate'];
				$url = $this->get('sUrl', null, null, $this->settings['sUrl']);
                $hookSurveyId = $this->get('sId', null, null, $this->settings['sId']);
                $auth = $this->get('sAuthToken', null, null, $this->settings['sAuthToken']);

                $egHelfer = $response['egHelfer'];
                $trial_DID = $response['trial_DID'];
                $trial_BenNam = $response['trial_BenNam'];
                $trial_NickNam = $response['trial_NickNam'];
                switch ($response['herkunft']) {
                    case "GT":
                        $herkunft = "Gamertransfer";
                        break;
                    case "Go":
                        $herkunft = "Google";
                        break;
                    case "DS":
                        $herkunft = "Discord-Server";
                        break;
                    case "Fo":
                        $herkunft = "Forum";
                        break;
                    case "So":
                        $herkunft = "Sonstiges";
                        break;
                }
                if ($response['herkunft_comment'])
                    $herkunft .= "\n{$response['herkunft_comment']}";
                if ($response['spiel_LoL'])
                    $spiel_LoL = "LoL";
                if ($response['spiel_OW'])
                    $spiel_OW = "OW";
                if ($response['spiel_PnP'])
                    $spiel_PnP = "PnP";
                if ($response['spiel_RL'])
                    $spiel_RL = "RL";
                if ($response['spiel_DbD'])
                    $spiel_DbD = "DbD";
                $spiel_Sonst = $response['spiel_other'];
                $spiele = implode(", ", array_filter([$spiel_LoL, $spiel_OW, $spiel_PnP, $spiel_RL, $spiel_DbD, $spiel_Sonst]));
                if ($response['acc_riot'])
                    $acc_riot = "Riot: {$response['acc_riot']}";
                if ($response['acc_blizz'])
                    $acc_blizz = "Battle.net: {$response['acc_blizz']}";
                if ($response['acc_steam'])
                    $acc_steam = "Steam: {$response['acc_steam']}";
                if ($response['acc_epic'])
                    $acc_epic = "Epic: {$response['acc_epic']}";
                if ($response['acc_sonst'])
                    $acc_sonst = "Sonstige: {$response['acc_sonst']}";
                $accounts = implode("\n", array_filter([$acc_riot, $acc_blizz, $acc_steam, $acc_epic, $acc_sonst]));
                if (!$accounts)
                    $accounts = "Es wurden keine aufgenommen!";
                $zeit = $response['zeit'];
                $sonst = $response['sonst'];
                if (!$response['sonst'])
                    $sonst = "-";

                $parameters = json_encode([
                    "username" => "EG-Rückmeldung",
                    "avatar_url" => "https://cdn.discordapp.com/avatars/1144944882578899017/a5a6a319fc82248cd40f3dc5323af684.webp",
                    "tts" => false,
                    "embeds" => [
                        [
                            "title" => "EG-Abgeschlossen \o/",
                            "type" => "rich",
                            "description" => "**{$egHelfer}** hat ein EG geführt.",
                            "url" => "",
                            "color" => hexdec( "a9d463" ),
                            "fields" => [
                                [
                                    "name" => "Kerndaten",
                                    "value" => "**ID:** {$trial_DID}\n**Benutzername:** {$trial_BenNam}\n**Nickname:** {$trial_NickNam}",
                                    "inline" => false
                                ],
                                [
                                    "name" => "",
                                    "value" => "",
                                    "inline" => false
                                ],
                                [
                                    "name" => "Herkunft",
                                    "value" => "{$herkunft} ",
                                    "inline" => false
                                ],
                                [
                                    "name" => "",
                                    "value" => "",
                                    "inline" => false
                                ],
                                [
                                  "name" => "Spiele",
                                  "value" => "{$spiele}",
                                  "inline" => true
                                ],
                                [
                                    "name" => "Accounts",
                                    "value" => "{$accounts}",
                                    "inline" => true
                                ],
                                [
                                    "name" => "",
                                    "value" => "",
                                    "inline" => false
                                ],
                                [
                                    "name" => "Online-Zeit",
                                    "value" => "{$zeit}",
                                    "inline" => true
                                ],
                                [
                                    "name" => "Sonstiges?",
                                    "value" => "{$sonst}",
                                    "inline" => true
                                ],
                            ],
                            "footer" => [
                                "text" => "Einsendung #{$responseId}"
                            ]
                        ]
                    ]
              
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
                $hookSent = $this->httpPost($url, $parameters);

                $this->log($comment . " | Params: ". json_encode($parameters) . json_encode($hookSent));
                $this->debug($url, $parameters, $hookSent, $time_start, $response);

                return;
            }

        private function getLastResponse($surveyId, $additionalFields)
            {
                if ($additionalFields)
                    {
                        $columnsInDB = \getQuestionInformation\helpers\surveyCodeHelper::getAllQuestions($surveyId);

                        $aadditionalSQGA = array();
                        foreach ($additionalFields as $field)
                            {
                                $push_val = array_search(trim($field), $columnsInDB);
                                if ($push_val) array_push($aadditionalSQGA, $push_val);
                            }
                        if (count($additionalFields) > 0)
                            {
                            $sadditionalSQGA = ", " . implode(', ', $aadditionalSQGA);
                            }
                    }

                $responseTable = $this->api->getResponseTable($surveyId);
                $query = "SELECT id, token, submitdate {$sadditionalSQGA} FROM {$responseTable} ORDER BY submitdate DESC LIMIT 1";
                $rawResult = Yii::app()->db->createCommand($query)->queryRow();

                $result = $rawResult;

                if (count($aadditionalSQGA) > 0)
                    {
                        foreach ($aadditionalSQGA as $SQGA)
                            {
                                $result[$columnsInDB[$SQGA]] = htmlspecialchars($rawResult[$SQGA]);
                                if ($push_val)
                                    array_push($aadditionalSQGA, $push_val);
                            }
                    }

                return $result;
            }

        /***** ***** ***** ***** *****
        * httpPost function http://hayageek.com/php-curl-post-get/
        * creates and executes a POST request
        * returns the output
        ***** ***** ***** ***** *****/
        private function httpPost($url, $parameters)
            {
                $ch = curl_init( $url );
                curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                curl_setopt( $ch, CURLOPT_POST, 1);
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $parameters);
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt( $ch, CURLOPT_HEADER, 0);
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
                $response = curl_exec( $ch );
                curl_close($ch);
                return $result;
            }

        /***** ***** ***** ***** *****
        * debugging
        ***** ***** ***** ***** *****/
        private function debug($url, $parameters, $hookSent, $time_start, $response)
            {
                if ($this->get('sBug', null, null, $this->settings['sBug']) == 1)
                  {
                    $this->log($comment);
                    $html = '<pre><br><br>----------------------------- DEBUG ----------------------------- <br><br>';
                    $html .= 'Parameters: <br>' . print_r($parameters, true);
                    $html .= "<br><br> ----------------------------- <br><br>";
                    $html .= 'Response: ' . print_r($response, true) . '<br>';
                    $html .= "<br><br> ----------------------------- <br><br>";
                    $html .= 'Hook sent to: ' . print_r($url, true) . '<br>';
                    $html .= 'Total execution time in seconds: ' . (microtime(true) - $time_start);
                    $html .= '</pre>';
                    $event = $this->getEvent();
                    $event->getContent($this)->addContent($html);
                  }
		          }
    }