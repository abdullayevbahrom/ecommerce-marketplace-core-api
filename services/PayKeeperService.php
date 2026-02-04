<?php
namespace app\services;

use app\models\order\Order;
use app\models\PayKeeperTransaction;
use yii\helpers\ArrayHelper;

class PayKeeperService {

    const BASE_URL = 'https://server.paykeeper.example.com';

    const PERSONAL_CABINET_LOGIN = 'admin';
    const PERSONAL_CABINET_PASSWORD = 'sample_password';


    const NOTIFY_SECRET_KEY = '';



    const INVOICE_STATUSES = [
        'created' => 'created',
        'sent' => 'sent',
        'paid' => 'paid',
        'expired' => 'expired',
    ];

    /**
     * Документация интеграция
     * линк https://docs.paykeeper.ru/metody-integratsii/vystavlenie-scheta-na-elektronnuyu-pochtu/
     */


    /**
     * @param $order_id
     * @param $amount
     * @return false|string
     * @description получения платежный форма с пост запросам
     */
    public function get_payform($order_id, $amount){

        $payment_parameters = http_build_query([
            [
                "clientid" => 'Testing',
                "orderid" => $order_id,
                "sum" => $amount,
            ]
        ]);

        $options = [
            "http" => [
                [
                    "method" => "POST",
                    "header" =>
                        "Content-type: application/x-www-form-urlencoded",
                    "content" => $payment_parameters
                ]
            ]
        ];

        $context = stream_context_create($options);
        return  file_get_contents(self::BASE_URL."/order/inline/", FALSE, $context);
        // вожрашает html
    }

    /**
     * callback с paykeeper
     * доментатция настройка callback
     * https://docs.paykeeper.ru/lichnyj-kabinet/stranitsa-nastrojki/#metody-opoveshheniya
     *
     */
    public function notify($id, $sum, $clientid, $orderid, $key){
       // 1 id	        Уникальный номер платежа	Да
       // 2	sum	        Сумма платежа	            Да
       // 3	clientid	Фамилия Имя Отчество	    Нет
       // 4	orderid	    Номер заказа	            Нет
       // 5	key	        Цифровая подпись запроса, строка из символов a-f и 0-9	Да
        // if (
        //     $key !=
        //     md5 ($id.number_format($sum, 2, ".", "").$clientid.$orderid.self::NOTIFY_SECRET_KEY)
        // )
        // {
        //     return false;
        // }
        // set order status to paid by $orderid
        if(!($order = Order::find()->where(['id' => $orderid])->one())) return false;
        $order->status_payment = 1;
        return $order->save(false);
    }

    /**
     * @param $order_id
     * @param $amount
     * @return false|string
     * @description создания инвойс, возрашаеть инвойс урл где можно платить
     */
    public function get_invoice_url($order_id, $amount){

        # Логин и пароль от личного кабинета PayKeeper
        # Basic-авторизация передаётся как base64
        $base64 = base64_encode(self::PERSONAL_CABINET_LOGIN.":".self::PERSONAL_CABINET_PASSWORD);
        $headers =  [];
        array_push($headers, 'Content-Type: application/x-www-form-urlencoded');

        # Подготавливаем заголовок для авторизации
        array_push($headers, 'Authorization: Basic ' . $base64);

        # Параметры платежа, сумма - обязательный параметр
        # Остальные параметры можно не задавать
        $payment_data = [
            "pay_amount" => $amount,
            "orderid" => $order_id,
            //"clientid" => "Иванов Иван Иванович",
//            "client_email" => "test@example.com",
//            "service_name" => "Услуга",
//            "client_phone" => "8 (910) 123-45-67"
        ];

        # Готовим первый запрос на получение токена безопасности
        $uri = "/info/settings/token/";

        # Для сетевых запросов в этом примере используется cURL
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $uri);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);

        # Инициируем запрос к API
        $response = curl_exec($curl);
        $php_array = json_decode($response, true);

        # В ответе должно быть заполнено поле token, иначе - ошибка
        if (isset($php_array['token'])) $token = $php_array['token']; else return false;


        # Готовим запрос 3.4 JSON API на получение счёта
        $uri = "/change/invoice/preview/";

        # Формируем список POST параметров
        $request = http_build_query(array_merge($payment_data,  ['token' => $token]));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . $uri);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);


        $response = json_decode(curl_exec($curl), true);

        # В ответе должно быть поле invoice_id, иначе - ошибка
        if (isset($response['invoice_id'])) $invoice_id = $response['invoice_id']; else return false;
        PayKeeperTransaction::create($order_id, $amount, $invoice_id, $request, json_encode($response));
        # В этой переменной прямая ссылка на оплату с заданными параметрами
        return self::BASE_URL."/bill/$invoice_id/";
        # Теперь её можно использовать как угодно, например, выводим ссылку на оплату
    }


    /**
     * @param $invoice_id
     * @return false|mixed
     * @description  получения статус инвойс если инвойс оплачено тогда статус paid
     */
    public function get_invoice_status($invoice_id){

        # Логин и пароль от личного кабинета PayKeeper
        # Формируем строку в base64 для Basic-авторизации
        $base64=base64_encode(self::PERSONAL_CABINET_LOGIN.":".self::PERSONAL_CABINET_PASSWORD);
        $headers= [];
        array_push($headers,'Content-Type: application/x-www-form-urlencoded');
        # Подготавливаем заголовок для Basic-авторизации
        array_push($headers,'Authorization: Basic '.$base64);

        # В примере используется библиотека cURL
        $curl=curl_init();

        # Готовим запрос 3.1 JSON API на получение счёта
        $uri="/info/invoice/byid/?id=$invoice_id";

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,self::BASE_URL.$uri);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'GET');
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($curl,CURLOPT_HEADER,false);

        # В ответе сервера должно быть поле status. Иначе - ошибка
        $response=json_decode(curl_exec($curl),true);
        if (isset($response['status'])) $status = $response['status']; else return false;

       # Это статус счёта. Для примера выведем его.
       return $status;
    }


}