<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Unirest;

class Controller extends BaseController
{

    public function route(Request $request) {
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
        if($this->checkCommand('getcountrykeyboard', $message)) $this->getCountryKeyboard($request);
        if($this->checkCommand('start', $message)) $this->getStartMessage($request);
        if($this->checkCommand('checkcountry', $message)) $this->checkCountry($request);
        } catch(\Throwable $err) {
            error_log(print_r($err, true));
        }
    }

    
    /**
     * handler
     */
    private function getStartMessage($request) {
        $message = "Hey there. This is a simple bot usefd for quick access to coronavirus stats. \n
It pulls from a free coronavirus API specified here: https://rapidapi.com/Gramzivi/api/covid-19-data. \n
This is primarily a personal project designed to promote The Greater Good and therefore I'll be working on it sporadically.\n
Use it to debunk your goofy friends who think the virus is a hoax or something idk. \n

Commands:\n
/getcountrykeyboard - Activates a keyboard where you can select a country. Selection of a country will automatically
query the API for data on that country. \n
/checkcountry - For when the keyboard isn't good enough for you and you want to type out the country name manually.\n
/start - See this message again.";
        $this->sendMessage($request->input('message'), $message);
    }

    /**
     * handler
     */
    private function getCountryKeyboard($request) {
        $message = $request->input('message');
        $countryList = $this->request('help/countries', []);

        $keyboard = [];

        foreach ($countryList as $country) {
            $keyboard[] = $this->makeKey($country->name);
        }
        $keyboardObj = [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false,
            'selective' => true,
            'force_reply' => false
        ];
        $this->sendMessage($message, '', true, $keyboardObj);
    }

    private function makeKey($keyText, $requestContact = false, $requestLocation = false) {
        $key = [
             "/checkcountry $keyText",
        ];
        return $key;
    }

    /**
     * handler
     */
    private function checkCountry($request) {
        $message = $request->input('message');
        
        $split = explode(' ', $message['text']);
        if (sizeof($split) < 2) {
            $this->sendMessage($message, 'please supply a name with your query', true);
            return;
        }
        $countryData = $this->request('country', ['name' => $split[1]]);
        if (sizeof($countryData) < 1 ) {
            $this->sendMessage($message, 'sorry, that country was not found', true);
            return;
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
        error_log(print_r($chat, true));
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
        if (is_array($keyboard)) $params['reply_markup'] = json_encode($keyboard);
        if ($sendReply) $params['reply_to_message_id'] = $message['message_id'];

        $headers = ['Accept' => 'application/x-www-form-urlencoded'];

        // $params['text'] = json_encode($params);
        error_log(print_r($params, true));
        $params1 = Unirest\Request\Body::form($params);
        $response = Unirest\Request::post(
            'https://api.telegram.org/bot' . env('BOT_TOKEN') . '/sendMessage', 
            $headers,
            $params1
        );
    }
}