<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Unirest;

class Controller extends BaseController
{

    public function route(Request $request)
    {
        try{
        $botToken = $request->input('botkey');
        if ($botToken != env('BOT_TOKEN')) {
            return;
        }
        // return "ALLOWED";
        $this->validate($request, [
            'message' => 'required',
        ]);
        $message = $request->input('message');
        $this->sendMessage($message,'input received');
        if($this->checkCommand('getcountry', $message)) $this->getCountryKeyboard($request);
        } catch(\Throwable $err) {
            error_log(print_r($err, true));
        }
    }
    /**
     * handler
     */
    private function getCountryKeyboard($request) {
        $message = $request->input('message');
        $countryList = $this->request('help/countries', []);

        $keyboard = [];

        foreach ($countryList as $country) {
            $keyboard = $this->makeKey($country->name);
        }

        $keyboardObj = [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'selective' => true
        ];

        $this->sendMessage($message, 'working', true, $keyboardObj);
    }

    private function makeKey($keyText, $requestContact = false, $requestLocation = false) {
        $key = [
            'text' => $keyText,
            'request_contact' => $requestContact,
            'request_location' => $requestLocation
        ];
        return $key;
    }

    //handler
    private function getCountry($request) {
        $message = $request->input('message');
        
        $split = explode(' ', $message['text']);
        $countryData = $this->request('country/code', ['code' => $split[1]]);
        if (sizeof($countryData) < 1 ) {
            $this->sendMessage($message, 'sorry, that country was not found');
        }

        $countryStats = $countryData[0];
        $text = "Case Statistics for {$countryStats->country} \n";
        $text .= "Total Confirmed Cases: {$countryStats->confirmed} \n";
        $text .= "Total Recovered Cases: {$countryStats->recovered} \n";
        $text .= "Total Current Critical Cases: {$countryStats->critical} \n";
        $text .= "Total Deaths: {$countryStats->deaths}";

        $this->sendMessage($message, $text);
    }

    private function checkCommand($searchText, $message)
    {
        $chat = $message['chat'];
        
        $append = ($chat['type'] === 'private') ? '' : '@' . env('BOT_NAME', 'covid_watch_bot');
        $searchText = $searchText . $append;
        if (str_contains($message['text'], $searchText)) return true;
        return false;
    }

    private function request($endpoint, $params) {
        $params['format'] = 'json';

        $headers = array(
            'x-rapidapi-host' => 'covid-19-data.p.rapidapi.com',
            'x-rapidapi-key' => env('RAPID_API_KEY')
        );

        $response = Unirest\Request::get('https://covid-19-data.p.rapidapi.com/'. $endpoint, $headers, $params);
        return $response->body;
    }

    private function sendMessage($message, $responseText, $sendReply = false, $keyboard = null) {
        $params = [
            'chat_id' => $message['chat']['id'],
            'text' => $responseText
        ];
        if (is_array($keyboard)) $params['reply_markup'] = $keyboard;
        if ($sendReply) $params['reply_to_message_id'] = $message['message_id'];
        $headers = array('Accept' => 'application/json');
        $params = Unirest\Request\Body::json($params);

        $response = Unirest\Request::post(
            'https://api.telegram.org/bot' . env('BOT_TOKEN') . '/sendMessage', 
            $headers,
            $params
        );
    }
}
