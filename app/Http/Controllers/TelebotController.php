<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Place;
use App\Models\Visit;
use Illuminate\Http\Request;

class TelebotController extends Controller
{
    //public string $token = '6210096609:AAH2ESiGb1qYXQtMRYHkyd8rEJ4YwKIpxHk';
    public string $token;
    // https://api.telegram.org/bot6170505099:AAGJ3XtXtTmyRB-ZNQp3h6EKWbA4Wwz25gQ/setWebhook?url=https://reslab.pro/telebot/&allowed_updates=["callback_query","message"]

    public function __construct(Request $request)
    {
        $host = $request->getHttpHost();

        if ($host === 'rbm.local') {
            $this->token =  '6170505099:AAGJ3XtXtTmyRB-ZNQp3h6EKWbA4Wwz25gQ';
        } else {
            $this->token = '6210096609:AAH2ESiGb1qYXQtMRYHkyd8rEJ4YwKIpxHk';
        }
    }

    public function index(Request $request)
    {
        $json = $request->all();
        file_put_contents(__DIR__ . '/debug-telebot.txt', print_r($json, true));
        if (!isset($json['update_id'])) {
            return false;
        }

        if (!empty($json['callback_query']['data'])) {
            $this->callbackHandler($json);
        } else {
            $this->messageHandler($json);
        }

        return true;
    }

    public function callbackHandler($json): void
    {
        $id = $json['callback_query']['from']['id'];
        $fields = 'callback_query_id=' . $json['callback_query']['id'] . '&text=';
        $this->curl('answerCallbackQuery', $fields);

        $place     = explode('_', $json['callback_query']['data']);
        $placeName = 'Вы выбрали место: ' . $place[2] . "\nТеперь нажмите кнопку \"Отправить геолокацию\" и подтвердите отправку.\n";

        $driver = Driver::where('telegram_id', $id)->first();

        if ($driver) {
            $driver->place_id = $place[1];
            $driver->save();

            $visit = new Visit();
            $visit->driver_id = $driver->id;
            $visit->place_id = $place[1];
            $visit->save();
        }

        $keyboard = [
            "keyboard"          =>
                [
                    [
                        [
                            "text"             => "Отправить геолокацию",
                            "request_location" => true
                        ]
                    ]

                ],
            "one_time_keyboard" => true,
            "resize_keyboard"   => true

        ];

        $fields = 'chat_id=' . $id . '&text=' . rawurlencode($placeName) . '&reply_markup=' . json_encode($keyboard);
        $this->curl('sendMessage', $fields);

        file_put_contents(__DIR__ . '/debug-telebot-action_01.txt', print_r($placeName, true));
    }

    public function curl($method, $fields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . $this->token . "/$method");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);

        file_put_contents(__DIR__ . '/debug-telebot-curl.txt', print_r(json_decode($result), true));
    }

    public function messageHandler($json)
    {
        if (!empty($json['my_chat_member'])) {
            $id = $json['my_chat_member']['from']['id'];
        } else {
            $id = $json['message']['from']['id'];
        }


        if (!empty($json['message']['location'])) {
            $this->updateDriver($json);
        } else {
            $driver = Driver::where('telegram_id', $id)->first();
            if (empty($driver) || empty($driver->area_id)) {
                $this->createNewDriver($json);


                $fields = 'chat_id=' . $id . '&text=' . rawurlencode('Данные водителя требуют подтверждения. Обратитесь к администратору');
                $this->curl('sendMessage', $fields);
            } else {
                $this->sendPlaces($id);
            }


        }
    }


    public function updateDriver($json) {
        file_put_contents(__DIR__ . '/debug-telebot-location.txt', print_r($json['message']['location'], true));

        $id = $json['message']['from']['id'];

        $location = $json['message']['location'];
        $lat = $location['latitude'];
        $lng = $location['longitude'];

        $driver = Driver::where('telegram_id', $id)->first();

        if ($driver) {
            $driver->lat = $lat;
            $driver->lng = $lng;
            $driver->save();

            $visit = Visit::where('driver_id', $driver->id)->orderBy('id', 'desc')->first();

            if ($visit) {
                $visit->driver_id = $driver->id;
                $visit->lat = $lat;
                $visit->lng = $lng;
                $visit->save();
            }
        }


        $keyboard = [
            "keyboard"          =>
                [
                    [
                        [
                            "text" => "Выбрать следующее место",
                        ]
                    ]

                ],
            "one_time_keyboard" => true,
            "resize_keyboard"   => true

        ];

        $fields = 'chat_id=' . $id . '&text=' . rawurlencode('Когда прибудите на следующую точку - нажмите кнопку "Выбрать следующее место"') . '&reply_markup=' . json_encode($keyboard);
        $this->curl('sendMessage', $fields);
    }

    public function sendPlaces($id)
    {
        $places = $this->getPlaces($id);

        $keys = [];
        foreach ($places as $place) {
            $keys[] = [
                [
                    'text'          => $place->name,
                    'callback_data' => 'action_' . $place->id . '_' . $place->name
                ]
            ];
        }

        $keyboard = [
            "resize_keyboard" => true,
            "inline_keyboard" => $keys
        ];

        $text = 'Выберите место:';

        $fields = 'chat_id=' . $id . '&text=' . rawurlencode($text) . '&reply_markup=' . json_encode($keyboard);
        $this->curl('sendMessage', $fields);
    }

    public function getPlaces(int $telegram_id)
    {
        $driver = Driver::where('telegram_id', $telegram_id)->first();

        if (empty($driver)) {
            return [];
        }

        $places = Place::where('area_id', $driver->area_id)->get(['id', 'name']);

        return $places;
    }

    public function createNewDriver($json)
    {
        $id = $json['message']['from']['id'];

        $driver = Driver::where('telegram_id', $id)->first();

        if (!empty($driver)) {
            return false;
        }

        /*        $firstName = $json['message']['from']['first_name'] . ' ' ?? '';
                $lastName = $json['message']['from']['last_name'] ?? '';
                $userName = !empty($json['message']['from']['username']) ? ' (' . $json['message']['from']['username'] . ')' : '';
                $driverName = $firstName . $lastName . $userName;*/

        $driverName = $json['message']['from']['first_name'];

        $driver = new Driver();
        $driver->name = $driverName;
        $driver->email = 'udefined@mail.com';
        $driver->phone = '';
        $driver->telegram_id = $id;
        $driver->driver_no = 0;
        $driver->car_no = 0;
        $driver->area_id = 0;
        $driver->lat = 0.0;
        $driver->lng = 0.0;
        $driver->place_id = 0;
        $driver->save();
    }
}
