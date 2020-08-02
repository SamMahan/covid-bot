<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{

    public function route(Request $request)
    {
        try{
        error_log("HERE, AT ROUTE");
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
        if($this->checkCommand('getcountry', $message)) $this->getCountry();
        } catch(\Throwable $err) {
            error_log(print_r($err, true));
        }
    }

    //handler
    private function getCountry($request) {
        $message = $request->input('message');
        $split = split(' ', $message['text']);
        $countryData = $this->request('country/code', ['code' => $split[1]]);
        if (sizeof($countryData) < 1 ) {
            $this->sendMessage($message, 'sorry, that country was not found');
        }

        $countryStats = $countryData[0];

        $text = "Case Statistics for " . $countryStats['province'] . ' \n ';
        $text.= "Total Confirmed Cases: " . $countryStats['confirmed'] . '\n';
        $text .= "Total Recovered Cases" . $countryStats['recovered'] . '\n';
        $text .= 'Total Current Active Cases' . $countryStats['active'] . '\n';
        $text .= 'Total Deaths' . $countryStats['deaths'];

        $this->sendMessage($message, $text);
    }

    private function checkCommand($searchText, $message)
    {
        $chat = $message['text'];
        
        $append = ($chat['type'] === 'private') ? '' : '@' . env('BOT_NAME', 'covid_watch_bot');
        $searchText = $searchText . $append;
        if (str_contains($message['text'], $searchText)) return true;
        return false;
    }

    private function request($endpoint, $params) {
        $client = new \http\Client;
        $request = new \http\Client\Request;

        $params['format'] = 'json';

        $request->setRequestUrl('https://covid-19-data.p.rapidapi.com/'.$endpoint);
        $request->setRequestMethod('GET');
        $request->setQuery(new \http\QueryString($params));

        $request->setHeaders(array(
            'x-rapidapi-host' => 'covid-19-data.p.rapidapi.com',
            'x-rapidapi-key' => env('RAPIDAPI_KEY')
        ));

        $client->enqueue($request)->send();
        $response = $client->getResponse();

        return $response->getBody();
    }

    private function sendMessage($message, $responseText) {
        $params = [
            'chatId' => $message['chat']['id'],
            'text' => $responseText
        ];

        $client = new \http\Client;
        $request = new \http\Client\Request;

        $request->setRequestUrl('https://api.telegram.org/bot' + env('BOT_TOKEN') + '/sendMessage', $params);
        $request->setRequestMethod('POST');
    
        $client->enqueue($request)->send();
    }
}
