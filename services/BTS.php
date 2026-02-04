<?php
namespace yii\services;

use Yii;

class BTS
{
    protected $baseUrl = 'http://api.logistics.example.com:8080/index.php';
    protected $apiVersion = 'v1';
    protected $token;
    
    // Default credentials - should be moved to config in production
    protected $username;
    protected $password;
    protected $inn;
    
    // Error codes from BTS API documentation
    const ERROR_BAD_REQUEST = 400;
    const ERROR_UNAUTHORIZED = 401;
    const ERROR_FORBIDDEN = 403;
    const ERROR_NOT_FOUND = 404;
    const ERROR_METHOD_NOT_ALLOWED = 405;
    const ERROR_NOT_ACCEPTABLE = 406;
    const ERROR_GONE = 410;
    const ERROR_TOO_MANY_REQUESTS = 429;
    const ERROR_INTERNAL_SERVER = 500;
    const ERROR_SERVICE_UNAVAILABLE = 503;

    // Uzbekistan Regions (Viloyatlar) - Multi-language support with official names
    const REGIONS = [
        2 => [
            'name_ru' => 'Андижанская область',
            'name_uz' => 'Andijon viloyati',
            'name_en' => 'Andijan region'
        ],
        3 => [
            'name_ru' => 'Наманганская область',
            'name_uz' => 'Namangan viloyati',
            'name_en' => 'Namangan region'
        ],
        4 => [
            'name_ru' => 'Хорезмская область',
            'name_uz' => 'Xorazm viloyati',
            'name_en' => 'Khorezm region'
        ],
        5 => [
            'name_ru' => 'Ташкентская область',
            'name_uz' => 'Toshkent viloyati',
            'name_en' => 'Tashkent region'
        ],
        6 => [
            'name_ru' => 'город Ташкент',
            'name_uz' => 'Tashkent shaxri',
            'name_en' => 'Tashkent city'
        ],
        7 => [
            'name_ru' => 'Бухарская область',
            'name_uz' => 'Buxoro viloyati',
            'name_en' => 'Bukhara region'
        ],
        8 => [
            'name_ru' => 'Самаркандская область',
            'name_uz' => 'Samarqand viloyati',
            'name_en' => 'Samarkand region'
        ],
        9 => [
            'name_ru' => 'Джизакская область',
            'name_uz' => 'Jizzax viloyati',
            'name_en' => 'Jizzakh region'
        ],
        10 => [
            'name_ru' => 'Навоийская область',
            'name_uz' => 'Navoiy viloyati',
            'name_en' => 'Navoi region'
        ],
        11 => [
            'name_ru' => 'Каракалпакстан',
            'name_uz' => 'Qoraqalpog\'iston',
            'name_en' => 'Karakalpakstan'
        ],
        12 => [
            'name_ru' => 'Сырдарьинская область',
            'name_uz' => 'Sirdaryo viloyati',
            'name_en' => 'Syrdarya region'
        ],
        13 => [
            'name_ru' => 'Сурхандарьинская область',
            'name_uz' => 'Surxondaryo viloyati',
            'name_en' => 'Surkhandarya region'
        ],
        14 => [
            'name_ru' => 'Кашкадарьинская область',
            'name_uz' => 'Qashqadaryo viloyati',
            'name_en' => 'Kashkadarya region'
        ],
        15 => [
            'name_ru' => 'Ферганская область',
            'name_uz' => 'Farg\'ona viloyati',
            'name_en' => 'Fergana region'
        ]
    ];

    // Major Cities (Shaharlar) with multi-language names and region IDs
    const CITIES = [
        // Shakhrisabz city (Kashkadarya region)
        1 => [
            'region_id' => 14,
            'name_ru' => 'г.Шахрисабз',
            'name_uz' => 'Шахрисабз шаҳар',
            'name_en' => 'Shakhrisabz city'
        ],
        // Pop district (Namangan region)
        2 => [
            'region_id' => 3,
            'name_ru' => 'Папский р-н',
            'name_uz' => 'Поп тумани',
            'name_en' => 'Pop district'
        ],
        // Asaka district (Andijan region)
        3 => [
            'region_id' => 2,
            'name_ru' => 'Асакинский р-н',
            'name_uz' => 'Асака туман',
            'name_en' => 'Asaka district'
        ],
        // Tashkent district (Tashkent region)
        4 => [
            'region_id' => 5,
            'name_ru' => 'Ташкентский р-н',
            'name_uz' => 'Тошкент тумани',
            'name_en' => 'Tashkent district'
        ],
        // Yangiyul city (Tashkent region)
        5 => [
            'region_id' => 5,
            'name_ru' => 'г.Янгийуль',
            'name_uz' => 'Янгийўл шаҳар',
            'name_en' => 'Yangiyul city'
        ],
        // Ahangaran city (Tashkent region)
        6 => [
            'region_id' => 5,
            'name_ru' => 'г.Ахангаран',
            'name_uz' => 'Оҳангарон шаҳар',
            'name_en' => 'Ohangaron city'
        ],
        // Nurafshon city (Tashkent region)
        7 => [
            'region_id' => 5,
            'name_ru' => 'г.Нурафшон',
            'name_uz' => 'Нурафшон шаҳар',
            'name_en' => 'Nurafshon city'
        ],
        // Nukus city (Karakalpakstan)
        8 => [
            'region_id' => 11,
            'name_ru' => 'г.Нукус',
            'name_uz' => 'Нукус шаҳар',
            'name_en' => 'Nukus city'
        ],
        // Tahiatosh district (Karakalpakstan)
        9 => [
            'region_id' => 11,
            'name_ru' => 'Тахиаташский р-н',
            'name_uz' => 'Тахиатош тумани',
            'name_en' => 'Takhiatosh district'
        ],
        // Khiva city (Khorezm region)
        10 => [
            'region_id' => 4,
            'name_ru' => 'г.Хива',
            'name_uz' => 'Хива шаҳар',
            'name_en' => 'Khiva city'
        ],
        // Amudarya district (Karakalpakstan)
        11 => [
            'region_id' => 11,
            'name_ru' => 'Амударьинский р-н',
            'name_uz' => 'Амударё туман',
            'name_en' => 'Amudarya district'
        ],
        // Beruni district (Karakalpakstan)
        12 => [
            'region_id' => 11,
            'name_ru' => 'Берунийский р-н',
            'name_uz' => 'Беруний туман',
            'name_en' => 'Beruni district'
        ],
        // Konlikul district (Karakalpakstan)
        13 => [
            'region_id' => 11,
            'name_ru' => 'Кандикульский р-н',
            'name_uz' => 'Қонликул туман',
            'name_en' => 'Konlikul district'
        ],
        // Karauzak district (Karakalpakstan)
        14 => [
            'region_id' => 11,
            'name_ru' => 'Караузякский р-н',
            'name_uz' => 'Қораузак туман',
            'name_en' => 'Karauzak district'
        ],
        // Kegeili district (Karakalpakstan)
        15 => [
            'region_id' => 11,
            'name_ru' => 'Кегейлийский р-н',
            'name_uz' => 'Кегейли туман',
            'name_en' => 'Kegeili district'
        ],
        // Kungirot district (Karakalpakstan)
        16 => [
            'region_id' => 11,
            'name_ru' => 'Кунградский р-н',
            'name_uz' => 'Қўнғирот туман',
            'name_en' => 'Kungirot district'
        ],
        // Moynaq district (Karakalpakstan)
        17 => [
            'region_id' => 11,
            'name_ru' => 'Муйнакский р-н',
            'name_uz' => 'Муйнақ туман',
            'name_en' => 'Moynaq district'
        ],
        // Nukus district (Karakalpakstan)
        18 => [
            'region_id' => 11,
            'name_ru' => 'Нукусский р-н',
            'name_uz' => 'Нукус туман',
            'name_en' => 'Nukus district'
        ],
        // Takhtakupir district (Karakalpakstan)
        19 => [
            'region_id' => 11,
            'name_ru' => 'Тахтакупырский р-н',
            'name_uz' => 'Тахтакупир туман',
            'name_en' => 'Takhtakupir district'
        ],
        // Turtkul district (Karakalpakstan)
        20 => [
            'region_id' => 11,
            'name_ru' => 'Турткульский р-н',
            'name_uz' => 'Турткул туман',
            'name_en' => 'Turtkul district'
        ],
        // Khojeyli district (Karakalpakstan)
        21 => [
            'region_id' => 11,
            'name_ru' => 'Ходжейлийский р-н',
            'name_uz' => 'Хўжайли туман',
            'name_en' => 'Khojeyli district'
        ],
        // Chimbay district (Karakalpakstan)
        22 => [
            'region_id' => 11,
            'name_ru' => 'Чимбайский р-н',
            'name_uz' => 'Чимбой туман',
            'name_en' => 'Chimbay district'
        ],
        // Shumanay district (Karakalpakstan)
        23 => [
            'region_id' => 11,
            'name_ru' => 'Шуманайский р-н',
            'name_uz' => 'Шуманай туман',
            'name_en' => 'Shumanay district'
        ],
        // Elliqqala district (Karakalpakstan)
        24 => [
            'region_id' => 11,
            'name_ru' => 'Элликкалинский р-н',
            'name_uz' => 'Элликкалъа туман',
            'name_en' => 'Elliqqala district'
        ],
        // Andijan city (Andijan region)
        25 => [
            'region_id' => 2,
            'name_ru' => 'г.Андижан',
            'name_uz' => 'Андижон шаҳар',
            'name_en' => 'Andijan city'
        ],
        // Khanabad city (Andijan region)
        26 => [
            'region_id' => 2,
            'name_ru' => 'г.Ханабад',
            'name_uz' => 'Хонобод шаҳар',
            'name_en' => 'Khanabad city'
        ],
        // Andijan district (Andijan region)
        27 => [
            'region_id' => 2,
            'name_ru' => 'Андижанский р-н',
            'name_uz' => 'Андижон тумани',
            'name_en' => 'Andijan district'
        ],
        // Balikchi district (Andijan region)
        28 => [
            'region_id' => 2,
            'name_ru' => 'Балыкчинский р-н',
            'name_uz' => 'Балиқчи туман',
            'name_en' => 'Balikchi District'
        ],
        // Bulokboshi district (Andijan region)
        29 => [
            'region_id' => 2,
            'name_ru' => 'Булакбашинский р-н',
            'name_uz' => 'Булоқбоши туман',
            'name_en' => 'Bulokboshi District'
        ],
        // Boz district (Andijan region)
        30 => [
            'region_id' => 2,
            'name_ru' => 'Бозский р-н',
            'name_uz' => 'Бўз туман',
            'name_en' => 'Boz district'
        ],
        // Jalalkuduk district (Andijan region)
        31 => [
            'region_id' => 2,
            'name_ru' => 'Джалалкудукский р-н',
            'name_uz' => 'Жалақудуқ туман',
            'name_en' => 'Jalakuduk district'
        ],
        // Izboskan district (Andijan region)
        32 => [
            'region_id' => 2,
            'name_ru' => 'Избасканский р-н',
            'name_uz' => 'Избоскан туман',
            'name_en' => 'Izboskan district'
        ],
        // Ulug'nor district (Andijan region)
        33 => [
            'region_id' => 2,
            'name_ru' => 'Улугнорский р-н',
            'name_uz' => 'Улуғнор туман',
            'name_en' => 'Ulug\'nor district'
        ],
        // Marhamat district (Andijan region)
        34 => [
            'region_id' => 2,
            'name_ru' => 'Мархаматский р-н',
            'name_uz' => 'Марҳамат туман',
            'name_en' => 'Merhamat district'
        ],
        // Paxtaabad district (Andijan region)
        35 => [
            'region_id' => 2,
            'name_ru' => 'Пахтаабадский р-н',
            'name_uz' => 'Пахтаобод туман',
            'name_en' => 'Paxtaobod district'
        ],
        // Khojaabad district (Andijan region)
        36 => [
            'region_id' => 2,
            'name_ru' => 'Ходжаабадский р-н',
            'name_uz' => 'Хўжаобод туман',
            'name_en' => 'Khojaabad district'
        ],
        // Altynkol district (Andijan region)
        37 => [
            'region_id' => 2,
            'name_ru' => 'Алтынкульский р-н',
            'name_uz' => 'Олтинкўл туман',
            'name_en' => 'Oltinkol district'
        ],
        // Qorgantepa district (Andijan region)
        38 => [
            'region_id' => 2,
            'name_ru' => 'Кургантепинский р-н',
            'name_uz' => 'Қўрғонтепа туман',
            'name_en' => 'Korgontepa district'
        ],
        // Shahrikhan district (Andijan region)
        39 => [
            'region_id' => 2,
            'name_ru' => 'Шахриханский р-н',
            'name_uz' => 'Шахрихон туман',
            'name_en' => 'Shahrikhan district'
        ],
        // Bukhara city (Bukhara region)
        40 => [
            'region_id' => 7,
            'name_ru' => 'г.Бухара',
            'name_uz' => 'Бухоро шаҳар',
            'name_en' => 'Bukhara city'
        ],
        // Kagan city (Bukhara region)
        41 => [
            'region_id' => 7,
            'name_ru' => 'г.Каган',
            'name_uz' => 'Когон шаҳар',
            'name_en' => 'Kogon city'
        ],
        // Bukhara district (Bukhara region)
        42 => [
            'region_id' => 7,
            'name_ru' => 'Бухарский р-н',
            'name_uz' => 'Бухоро туман',
            'name_en' => 'Bukhara district'
        ],
        // Vabkent district (Bukhara region)
        43 => [
            'region_id' => 7,
            'name_ru' => 'Вабкентский р-н',
            'name_uz' => 'Вобкент туман',
            'name_en' => 'Vabkent district'
        ],
        // Jondor district (Bukhara region)
        44 => [
            'region_id' => 7,
            'name_ru' => 'Джандарский р-н',
            'name_uz' => 'Жондор туман',
            'name_en' => 'Jondor district'
        ],
        // Kagan district (Bukhara region)
        45 => [
            'region_id' => 7,
            'name_ru' => 'Каганский р-н',
            'name_uz' => 'Когон туман',
            'name_en' => 'Kogon district'
        ],
        // Alat district (Bukhara region)
        46 => [
            'region_id' => 7,
            'name_ru' => 'Алатский р-н',
            'name_uz' => 'Олот туман',
            'name_en' => 'Olot district'
        ],
        // Peshku district (Bukhara region)
        47 => [
            'region_id' => 7,
            'name_ru' => 'Пешкунский р-н',
            'name_uz' => 'Пешку туман',
            'name_en' => 'Peshku district'
        ],
        // Romitan district (Bukhara region)
        48 => [
            'region_id' => 7,
            'name_ru' => 'Ромитанский р-н',
            'name_uz' => 'Ромитан туман',
            'name_en' => 'Romitan district'
        ],
        // Shafirkan district (Bukhara region)
        49 => [
            'region_id' => 7,
            'name_ru' => 'Шафирканский р-н',
            'name_uz' => 'Шофиркон туман',
            'name_en' => 'Shafirkon district'
        ],
        // Qarakul district (Bukhara region)
        50 => [
            'region_id' => 7,
            'name_ru' => 'Каракульский р-н',
            'name_uz' => 'Қоракўл туман',
            'name_en' => 'Karakol district'
        ],
        // Qarovulbozor district (Bukhara region)
        51 => [
            'region_id' => 7,
            'name_ru' => 'Караулбазарский р-н',
            'name_uz' => 'Қоровулбозор туман',
            'name_en' => 'Karovulbazar district'
        ],
        // Gijduvan district (Bukhara region)
        52 => [
            'region_id' => 7,
            'name_ru' => 'Гиждуванский р-н',
            'name_uz' => 'Ғиждувон туман',
            'name_en' => 'Gijduvan district'
        ],
        // Arnasay district (Jizzakh region)
        53 => [
            'region_id' => 9,
            'name_ru' => 'Арнасайский р-н',
            'name_uz' => 'Арнасой туман',
            'name_en' => 'Arnasay District'
        ],
        // Baxmal district (Jizzakh region)
        54 => [
            'region_id' => 9,
            'name_ru' => 'Бахмальский р-н',
            'name_uz' => 'Бахмал туман',
            'name_en' => 'Baxmal district'
        ],
        // Gallaaral district (Jizzakh region)
        55 => [
            'region_id' => 9,
            'name_ru' => 'Галляаральский р-н',
            'name_uz' => 'Ғаллаорол туман',
            'name_en' => 'Gallaorol district'
        ],
        // Dustlik district (Jizzakh region)
        56 => [
            'region_id' => 9,
            'name_ru' => 'Дустликский р-н',
            'name_uz' => 'Дўстлик туман',
            'name_en' => 'Dustlik district'
        ],
        // Jizzakh city (Jizzakh region)
        57 => [
            'region_id' => 9,
            'name_ru' => 'г.Джизак',
            'name_uz' => 'Жиззах шаҳар',
            'name_en' => 'Jizzakh city'
        ],
        // Sharof Rashidov (Jizzakh region)
        58 => [
            'region_id' => 9,
            'name_ru' => 'Шарофа Рашидова',
            'name_uz' => 'Шароф Рашидов',
            'name_en' => 'Sharaf Rashidov'
        ],
        // Zarbdor district (Jizzakh region)
        59 => [
            'region_id' => 9,
            'name_ru' => 'Зарбдорский р-н',
            'name_uz' => 'Зарбдор туман',
            'name_en' => 'Zarbdar district'
        ],
        // Zafarabad district (Jizzakh region)
        60 => [
            'region_id' => 9,
            'name_ru' => 'Зафарабадский р-н',
            'name_uz' => 'Зафаробод туман',
            'name_en' => 'Zafarabad district'
        ],
        // Zaamin district (Jizzakh region)
        61 => [
            'region_id' => 9,
            'name_ru' => 'Заминский р-н',
            'name_uz' => 'Зомин туман',
            'name_en' => 'Zomin district'
        ],
        // Mirzachol district (Jizzakh region)
        62 => [
            'region_id' => 9,
            'name_ru' => 'Мирзачульский р-н',
            'name_uz' => 'Мирзачўл туман',
            'name_en' => 'Mirzachol district'
        ],
        // Pakhtakor district (Jizzakh region)
        63 => [
            'region_id' => 9,
            'name_ru' => 'Пахтакорский р-н',
            'name_uz' => 'Пахтакор туман',
            'name_en' => 'Pakhtakor district'
        ],
        // Farish district (Jizzakh region)
        64 => [
            'region_id' => 9,
            'name_ru' => 'Фаришский р-н',
            'name_uz' => 'Фориш туман',
            'name_en' => 'Farish district'
        ],
        // Yangiabad district (Jizzakh region)
        65 => [
            'region_id' => 9,
            'name_ru' => 'Янгиабадский р-н',
            'name_uz' => 'Янгиобод туман',
            'name_en' => 'Yangiabad district'
        ],
        // Qarshi city (Qashqadaryo region)
        66 => [
            'region_id' => 14,
            'name_ru' => 'г.Карши',
            'name_uz' => 'Қарши шаҳар',
            'name_en' => 'Karshi city'
        ],
        // Qarshi district (Qashqadaryo region)
        67 => [
            'region_id' => 14,
            'name_ru' => 'Каршинский р-н',
            'name_uz' => 'Қарши туман',
            'name_en' => 'Karshi district'
        ],
        // Mubarek district (Qashqadaryo region)
        68 => [
            'region_id' => 14,
            'name_ru' => 'Мубарекский р-н',
            'name_uz' => 'Муборак туман',
            'name_en' => 'Mubarak district'
        ],
        // G'uzor district (Qashqadaryo region)
        69 => [
            'region_id' => 14,
            'name_ru' => 'Гузарский р-н',
            'name_uz' => 'Ғузор тумани',
            'name_en' => 'Guzor district'
        ],
        // Qamashi district (Qashqadaryo region)
        70 => [
            'region_id' => 14,
            'name_ru' => 'Камашинский р-н',
            'name_uz' => 'Қамаши туман',
            'name_en' => 'Qamashi district'
        ],
        // Chirakchi district (Qashqadaryo region)
        71 => [
            'region_id' => 14,
            'name_ru' => 'Чиракчинский р-н',
            'name_uz' => 'Чироқчи туман',
            'name_en' => 'Chirakchi district'
        ],
        // Shahrisabz district (Qashqadaryo region)
        72 => [
            'region_id' => 14,
            'name_ru' => 'Шахризабский р-н',
            'name_uz' => 'Шахрисабз туман',
            'name_en' => 'Shahrisabz district'
        ],
        // Kasbi district (Qashqadaryo region)
        73 => [
            'region_id' => 14,
            'name_ru' => 'Касбинский р-н',
            'name_uz' => 'Касби туман',
            'name_en' => 'Kasbi district'
        ],
        // Koson district (Qashqadaryo region)
        74 => [
            'region_id' => 14,
            'name_ru' => 'Касанский р-н',
            'name_uz' => 'Косон туман',
            'name_en' => 'Koson district'
        ],
        // Kitab district (Qashqadaryo region)
        75 => [
            'region_id' => 14,
            'name_ru' => 'Китабский р-н',
            'name_uz' => 'Китоб туман',
            'name_en' => 'Kitab district'
        ],
        // Nishan district (Qashqadaryo region)
        76 => [
            'region_id' => 14,
            'name_ru' => 'Нишанский р-н',
            'name_uz' => 'Нишон туман',
            'name_en' => 'Nishan district'
        ],
        // Mirishkor district (Qashqadaryo region)
        77 => [
            'region_id' => 14,
            'name_ru' => 'Миришкорский р-н',
            'name_uz' => 'Миришкор туман',
            'name_en' => 'Mirishkor district'
        ],
        // Dehkanabad district (Qashqadaryo region)
        78 => [
            'region_id' => 14,
            'name_ru' => 'Дехканабадский р-н',
            'name_uz' => 'Деҳқонобод туман',
            'name_en' => 'Dehkanabad district'
        ],
        // Yakkabag district (Qashqadaryo region)
        79 => [
            'region_id' => 14,
            'name_ru' => 'Яккабагский р-н',
            'name_uz' => 'Яккабоғ туман',
            'name_en' => 'Yakkabog district'
        ],
        // Navoiy city (Navoiy region)
        80 => [
            'region_id' => 10,
            'name_ru' => 'г.Навойи',
            'name_uz' => 'Навоий шаҳар',
            'name_en' => 'Navoi city'
        ],
        // Zarafshan city (Navoiy region)
        81 => [
            'region_id' => 10,
            'name_ru' => 'г.Заравшан',
            'name_uz' => 'Зарафшон шаҳар',
            'name_en' => 'Zarafshan city'
        ],
        // Karmana district (Navoiy region)
        82 => [
            'region_id' => 10,
            'name_ru' => 'Карманинский р-н',
            'name_uz' => 'Кармана туман',
            'name_en' => 'Karmana district'
        ],
        // Tomdi district (Navoiy region)
        83 => [
            'region_id' => 10,
            'name_ru' => 'Тамдынский р-н',
            'name_uz' => 'Томди тумани',
            'name_en' => 'Tomdi district'
        ],
        // Navbahor district (Navoiy region)
        84 => [
            'region_id' => 10,
            'name_ru' => 'Навбахорский р-н',
            'name_uz' => 'Навбаҳор туман',
            'name_en' => 'Navbahor district'
        ],
        // Nurata district (Navoiy region)
        85 => [
            'region_id' => 10,
            'name_ru' => 'Нуратинский р-н',
            'name_uz' => 'Нурота туман',
            'name_en' => 'Nurota district'
        ],
        // Khatirchi district (Navoiy region)
        86 => [
            'region_id' => 10,
            'name_ru' => 'Хатырчинский р-н',
            'name_uz' => 'Хатирчи туман',
            'name_en' => 'Khatirchi district'
        ],
        // Qiziltepa district (Navoiy region)
        87 => [
            'region_id' => 10,
            'name_ru' => 'Кызылтепинский р-н',
            'name_uz' => 'Қизилтепа туман',
            'name_en' => 'Kiziltepa district'
        ],
        // Konimekh district (Navoiy region)
        88 => [
            'region_id' => 10,
            'name_ru' => 'Кoнимехский р-н',
            'name_uz' => 'Конимех туман',
            'name_en' => 'Konimekh district'
        ],
        // Uchkuduk district (Navoiy region)
        89 => [
            'region_id' => 10,
            'name_ru' => 'г.Учкудук',
            'name_uz' => 'Учқудуқ туман',
            'name_en' => 'Uchquduq district'
        ],
        // Namangan city (Namangan region)
        90 => [
            'region_id' => 3,
            'name_ru' => 'г.Наманган',
            'name_uz' => 'Наманган шаҳар',
            'name_en' => 'Namangan city'
        ],
        // Mingbulak district (Namangan region)
        91 => [
            'region_id' => 3,
            'name_ru' => 'Мингбулакский р-н',
            'name_uz' => 'Мингбулоқ тумани',
            'name_en' => 'Mingbulok district'
        ],
        // Kosonsoy district (Namangan region)
        92 => [
            'region_id' => 3,
            'name_ru' => 'Касансайский р-н',
            'name_uz' => 'Косонсой тумани',
            'name_en' => 'Kosonsoy district'
        ],
        // Namangan district (Namangan region)
        93 => [
            'region_id' => 3,
            'name_ru' => 'Наманганский р-н',
            'name_uz' => 'Наманган тумани',
            'name_en' => 'Namangan district'
        ],
        // Norin district (Namangan region)
        94 => [
            'region_id' => 3,
            'name_ru' => 'Нарынский р-н',
            'name_uz' => 'Норин тумани',
            'name_en' => 'Norin district'
        ],
        // Turakurgan district (Namangan region)
        95 => [
            'region_id' => 3,
            'name_ru' => 'Туракурганский р-н',
            'name_uz' => 'Тўрақўрғон тумани',
            'name_en' => 'Torakurgan district'
        ],
        // Uychi district (Namangan region)
        96 => [
            'region_id' => 3,
            'name_ru' => 'Уйчинский р-н',
            'name_uz' => 'Уйчи тумани',
            'name_en' => 'Uychi district'
        ],
        // Uchkurgan district (Namangan region)
        97 => [
            'region_id' => 3,
            'name_ru' => 'Учкурганский р-н',
            'name_uz' => 'Учқўрғон тумани',
            'name_en' => 'Uchkurgan district'
        ],
        // Chartak district (Namangan region)
        98 => [
            'region_id' => 3,
            'name_ru' => 'Чартакский р-н',
            'name_uz' => 'Чортоқ тумани',
            'name_en' => 'Chortok district'
        ],
        // Chust district (Namangan region)
        99 => [
            'region_id' => 3,
            'name_ru' => 'Чустский р-н',
            'name_uz' => 'Чуст тумани',
            'name_en' => 'Chust district'
        ],
        // Yangikurgan district (Namangan region)
        100 => [
            'region_id' => 3,
            'name_ru' => 'Янгикурганский р-н',
            'name_uz' => 'Янгиқўрғон тумани',
            'name_en' => 'Yangikurgan district'
        ],
        // Samarkand city (Samarkand region)
        101 => [
            'region_id' => 8,
            'name_ru' => 'г.Самарканд',
            'name_uz' => 'Самарқанд шаҳар',
            'name_en' => 'Samarkand city'
        ],
        // Urgut district (Samarkand region)
        102 => [
            'region_id' => 8,
            'name_ru' => 'Ургутсский р-н',
            'name_uz' => 'Ургут туман',
            'name_en' => 'Urgut district'
        ],
        // Pakhtachi district (Samarkand region)
        103 => [
            'region_id' => 8,
            'name_ru' => 'Пахтаачинский р-н',
            'name_uz' => 'Пахтачи туман',
            'name_en' => 'Paxtachi district'
        ],
        // Kattakurgan district (Samarkand region)
        104 => [
            'region_id' => 8,
            'name_ru' => 'Каттакурганский р-н',
            'name_uz' => 'Каттақўрғон туман',
            'name_en' => 'Kattakorgan district'
        ],
        // Samarkand district (Samarkand region)
        105 => [
            'region_id' => 8,
            'name_ru' => 'Самаркандский р-н',
            'name_uz' => 'Самарқанд туман',
            'name_en' => 'Samarkand district'
        ],
        // Bulungur district (Samarkand region)
        106 => [
            'region_id' => 8,
            'name_ru' => 'Булунгурский р-н',
            'name_uz' => 'Булунғур туман',
            'name_en' => 'Bulungur district'
        ],
        // Jambay district (Samarkand region)
        107 => [
            'region_id' => 8,
            'name_ru' => 'Джамбайский р-н',
            'name_uz' => 'Жомбой туман',
            'name_en' => 'Jomboy district'
        ],
        // Kushrobad district (Samarkand region)
        108 => [
            'region_id' => 8,
            'name_ru' => 'Кошрабадский р-н',
            'name_uz' => 'Қўшробод туман',
            'name_en' => 'Kushrabad District'
        ],
        // Narpay district (Samarkand region)
        109 => [
            'region_id' => 8,
            'name_ru' => 'Нарпайский р-н',
            'name_uz' => 'Нарпай туман',
            'name_en' => 'Narpay district'
        ],
        // Taylak district (Samarkand region)
        110 => [
            'region_id' => 8,
            'name_ru' => 'Тайлякский р-н',
            'name_uz' => 'Тайлоқ туман',
            'name_en' => 'Taylok district'
        ],
        // Pastdargom district (Samarkand region)
        111 => [
            'region_id' => 8,
            'name_ru' => 'Пастдаргомский р-н',
            'name_uz' => 'Пастдарғом туман',
            'name_en' => 'Pastdargom district'
        ],
        // Nurabad district (Samarkand region)
        112 => [
            'region_id' => 8,
            'name_ru' => 'Нуробадский р-н',
            'name_uz' => 'Нуробод туман',
            'name_en' => 'Nurabad district'
        ],
        // Kattakurgan city (Samarkand region)
        113 => [
            'region_id' => 8,
            'name_ru' => 'г.Каттакурган',
            'name_uz' => 'Каттақўрғон шаҳар',
            'name_en' => 'Kattakorgan city'
        ],
        // Payaryk district (Samarkand region)
        114 => [
            'region_id' => 8,
            'name_ru' => 'Пайарыкский р-н',
            'name_uz' => 'Пайариқ туман',
            'name_en' => 'Payariq district'
        ],
        // Akdarya district (Samarkand region)
        115 => [
            'region_id' => 8,
            'name_ru' => 'Акдарьинский р-н',
            'name_uz' => 'Оқдарё туман',
            'name_en' => 'Akdarya district'
        ],
        // Ishtihan district (Samarkand region)
        116 => [
            'region_id' => 8,
            'name_ru' => 'Иштихонский р-н',
            'name_uz' => 'Иштихон туман',
            'name_en' => 'Ishtikhon district'
        ],
        // Termez city (Surkhandarya region)
        117 => [
            'region_id' => 13,
            'name_ru' => 'г.Термез',
            'name_uz' => 'Термиз шаҳар',
            'name_en' => 'Termiz city'
        ],
        // Termez district (Surkhandarya region)
        118 => [
            'region_id' => 13,
            'name_ru' => 'Термезский р-н',
            'name_uz' => 'Термиз туман',
            'name_en' => 'Termiz district'
        ],
        // Muzrabot district (Surkhandarya region)
        119 => [
            'region_id' => 13,
            'name_ru' => 'Музрабадский р-н',
            'name_uz' => 'Музработ туман',
            'name_en' => 'Muzrabot district'
        ],
        // Altinsoy district (Surkhandarya region)
        120 => [
            'region_id' => 13,
            'name_ru' => 'Алтынсайский р-н',
            'name_uz' => 'Олтинсой туман',
            'name_en' => 'Altinsoy district'
        ],
        // Denov district (Surkhandarya region)
        121 => [
            'region_id' => 13,
            'name_ru' => 'Денаусский р-н',
            'name_uz' => 'Денов туман',
            'name_en' => 'Denov district'
        ],
        // Sariosia district (Surkhandarya region)
        122 => [
            'region_id' => 13,
            'name_ru' => 'Сарыассийский р-н',
            'name_uz' => 'Сариосиё туман',
            'name_en' => 'Sariosia district'
        ],
        // Qiziriq district (Surkhandarya region)
        123 => [
            'region_id' => 13,
            'name_ru' => 'Кизирикский р-н',
            'name_uz' => 'Қизириқ туман',
            'name_en' => 'Qiziriq district'
        ],
        // Jarkurgan district (Surkhandarya region)
        124 => [
            'region_id' => 13,
            'name_ru' => 'Джаркурганский р-н',
            'name_uz' => 'Жарқўрғон туман',
            'name_en' => 'Jarkurgan district'
        ],
        // Angor district (Surkhandarya region)
        125 => [
            'region_id' => 13,
            'name_ru' => 'Ангорский р-н',
            'name_uz' => 'Ангор туман',
            'name_en' => 'Angar district'
        ],
        // Kumkurgan district (Surkhandarya region)
        126 => [
            'region_id' => 13,
            'name_ru' => 'Кумкурганский р-н',
            'name_uz' => 'Қумқўрғон туман',
            'name_en' => 'Kumkurgan district'
        ],
        // Baysun district (Surkhandarya region)
        127 => [
            'region_id' => 13,
            'name_ru' => 'Байсунский р-н',
            'name_uz' => 'Бойсун туман',
            'name_en' => 'Boysun district'
        ],
        // Shurchi district (Surkhandarya region)
        128 => [
            'region_id' => 13,
            'name_ru' => 'Шурчинский р-н',
            'name_uz' => 'Шўрчи туман',
            'name_en' => 'Shorchi district'
        ],
        // Sherabad district (Surkhandarya region)
        129 => [
            'region_id' => 13,
            'name_ru' => 'Шерабадский р-н',
            'name_uz' => 'Шеробод туман',
            'name_en' => 'Sherabad district'
        ],
        // Uzun district (Surkhandarya region)
        130 => [
            'region_id' => 13,
            'name_ru' => 'Узунский р-н',
            'name_uz' => 'Узун туман',
            'name_en' => 'Uzun district'
        ],
        // Gulistan city (Syrdarya region)
        131 => [
            'region_id' => 12,
            'name_ru' => 'г.Гулистан',
            'name_uz' => 'Гулистон шаҳар',
            'name_en' => 'Gulistan city'
        ],
        // Yangier city (Syrdarya region)
        132 => [
            'region_id' => 12,
            'name_ru' => 'г.Янгиер',
            'name_uz' => 'Янгиер туман',
            'name_en' => 'Yangiyer district'
        ],
        // Shirin city (Syrdarya region)
        133 => [
            'region_id' => 12,
            'name_ru' => 'г.Ширин',
            'name_uz' => 'Ширин туман',
            'name_en' => 'Shirin district'
        ],
        // Oqoltin district (Syrdarya region)
        134 => [
            'region_id' => 12,
            'name_ru' => 'Оқалтин тумани',
            'name_uz' => 'Оқалтин тумани',
            'name_en' => 'Оқалтин тумани'
        ],
        // Bayovut district (Syrdarya region)
        135 => [
            'region_id' => 12,
            'name_ru' => 'Баяутский р-н',
            'name_uz' => 'Боёвут туман',
            'name_en' => 'Boyovut district'
        ],
        // Gulistan district (Syrdarya region)
        136 => [
            'region_id' => 12,
            'name_ru' => 'Гулистанский р-н',
            'name_uz' => 'Гулистон туман',
            'name_en' => 'Gulistan district'
        ],
        // Mirzaabad district (Syrdarya region)
        137 => [
            'region_id' => 12,
            'name_ru' => 'Мирзаабадский р-н',
            'name_uz' => 'Мирзаобод туман',
            'name_en' => 'Mirzaabad district'
        ],
        // Saykhunabad district (Syrdarya region)
        138 => [
            'region_id' => 12,
            'name_ru' => 'Сайхунабадский р-н',
            'name_uz' => 'Сайхунобод туман',
            'name_en' => 'Saykhunabad district'
        ],
        // Sardoba district (Syrdarya region)
        139 => [
            'region_id' => 12,
            'name_ru' => 'Сардобинский р-н',
            'name_uz' => 'Сардоба туман',
            'name_en' => 'Sardoba district'
        ],
        // Syrdarya district (Syrdarya region)
        140 => [
            'region_id' => 12,
            'name_ru' => 'Сырдарьинский р-н',
            'name_uz' => 'Сирдарё туман',
            'name_en' => 'Syrdaryo district'
        ],
        // Khavos district (Syrdarya region)
        141 => [
            'region_id' => 12,
            'name_ru' => 'Хавастский р-н',
            'name_uz' => 'Ховос туман',
            'name_en' => 'Khavos district'
        ],
        // Angren city (Tashkent region)
        142 => [
            'region_id' => 5,
            'name_ru' => 'г.Ангрен',
            'name_uz' => 'Ангрен шаҳар',
            'name_en' => 'Angren city'
        ],
        // Bekabad city (Tashkent region)
        143 => [
            'region_id' => 5,
            'name_ru' => 'г.Бекабад',
            'name_uz' => 'Бекобод шаҳар',
            'name_en' => 'Bekobod city'
        ],
        // Almalyk city (Tashkent region)
        144 => [
            'region_id' => 5,
            'name_ru' => 'г.Алмалык',
            'name_uz' => 'Олмалиқ шаҳар',
            'name_en' => 'Almalik city'
        ],
        // Chirchik city (Tashkent region)
        145 => [
            'region_id' => 5,
            'name_ru' => 'г.Чирчик',
            'name_uz' => 'Чирчиқ шаҳар',
            'name_en' => 'Chirchik city'
        ],
        // Bekabad district (Tashkent region)
        146 => [
            'region_id' => 5,
            'name_ru' => 'Бекабадский р-н',
            'name_uz' => 'Бекобод туман',
            'name_en' => 'Bekobad district'
        ],
        // Bostanliq district (Tashkent region)
        147 => [
            'region_id' => 5,
            'name_ru' => 'Бостанлыкский р-н',
            'name_uz' => 'Бўстонлиқ туман',
            'name_en' => 'Bostonliq district'
        ],
        // Kibray district (Tashkent region)
        148 => [
            'region_id' => 5,
            'name_ru' => 'Кибрайский р-н',
            'name_uz' => 'Қибрай туман',
            'name_en' => 'Kibrai district'
        ],
        // Zangiota district (Tashkent region)
        149 => [
            'region_id' => 5,
            'name_ru' => 'Зангиатинский р-н',
            'name_uz' => 'Зангиота туман',
            'name_en' => 'Zangiota district'
        ],
        // Kuyichirchik district (Tashkent region)
        150 => [
            'region_id' => 5,
            'name_ru' => 'Куйи-чирчикский р-н',
            'name_uz' => 'Қуйичирчиқ туман',
            'name_en' => 'Kuyichirchik district'
        ],
        // Akkurgan district (Tashkent region)
        151 => [
            'region_id' => 5,
            'name_ru' => 'Аккурганский р-н',
            'name_uz' => 'Оққўрғон туман',
            'name_en' => 'Akkurgan district'
        ],
        // Parkent district (Tashkent region)
        152 => [
            'region_id' => 5,
            'name_ru' => 'Паркентский р-н',
            'name_uz' => 'Паркент туман',
            'name_en' => 'Parkent district'
        ],
        // Ortachirchik district (Tashkent region)
        154 => [
            'region_id' => 5,
            'name_ru' => 'Урта-чирчикский р-н',
            'name_uz' => 'Ўртачирчиқ туман',
            'name_en' => 'Ortachirchik district'
        ],
        // Chinoz district (Tashkent region)
        155 => [
            'region_id' => 5,
            'name_ru' => 'Чиназский р-н',
            'name_uz' => 'Чиноз туман',
            'name_en' => 'Chinoz district'
        ],
        // Yukorichirchik district (Tashkent region)
        156 => [
            'region_id' => 5,
            'name_ru' => 'Юкори чирчикский р-н',
            'name_uz' => 'Юқоричирчиқ туман',
            'name_en' => 'Yukorichirchik tuman'
        ],
        // Boka district (Tashkent region)
        157 => [
            'region_id' => 5,
            'name_ru' => 'Букинский р-н',
            'name_uz' => 'Бўка туман',
            'name_en' => 'Boka district'
        ],
        // Yangiyol district (Tashkent region)
        158 => [
            'region_id' => 5,
            'name_ru' => 'Янгийульский р-н',
            'name_uz' => 'Янгийўл туман',
            'name_en' => 'Yangiyol district'
        ],
        // Ahangaran district (Tashkent region)
        159 => [
            'region_id' => 5,
            'name_ru' => 'Ахангаранский р-н',
            'name_uz' => 'Охангарон туман',
            'name_en' => 'Akhangargan district'
        ],
        // Fergana city (Fergana region)
        160 => [
            'region_id' => 15,
            'name_ru' => 'г.Фергана',
            'name_uz' => 'Фарғона шаҳар',
            'name_en' => 'Fergana city'
        ],
        // Margilan city (Fergana region)
        161 => [
            'region_id' => 15,
            'name_ru' => 'г.Маргилан',
            'name_uz' => 'Марғилон шаҳар',
            'name_en' => 'Margilan city'
        ],
        // Kuvasay city (Fergana region)
        162 => [
            'region_id' => 15,
            'name_ru' => 'г.Кувасай',
            'name_uz' => 'Қувасой шаҳар',
            'name_en' => 'Kuvasoy city'
        ],
        // Kokand city (Fergana region)
        163 => [
            'region_id' => 15,
            'name_ru' => 'г.Коканд',
            'name_uz' => 'Қўқон шаҳар',
            'name_en' => 'Kokan city'
        ],
        // Baghdad district (Fergana region)
        164 => [
            'region_id' => 15,
            'name_ru' => 'Багдадский р-н',
            'name_uz' => 'Боғдод туман',
            'name_en' => 'Baghdad district'
        ],
        // Buvaida district (Fergana region)
        165 => [
            'region_id' => 15,
            'name_ru' => 'Бувайдинский р-н',
            'name_uz' => 'Бувайда туман',
            'name_en' => 'Buvaida district'
        ],
        // Dangara district (Fergana region)
        166 => [
            'region_id' => 15,
            'name_ru' => 'Дангаринский р-н',
            'name_uz' => 'Данғара туман',
            'name_en' => 'Dangara district'
        ],
        // Yazyavan district (Fergana region)
        167 => [
            'region_id' => 15,
            'name_ru' => 'Язьяванский р-н',
            'name_uz' => 'Ёзёвон туман',
            'name_en' => 'Yozhiovon district'
        ],
        // Altyaryk district (Fergana region)
        168 => [
            'region_id' => 15,
            'name_ru' => 'Алтыарыкский р-н',
            'name_uz' => 'Олтиариқ туман',
            'name_en' => 'Altyariq district'
        ],
        // Besharik district (Fergana region)
        169 => [
            'region_id' => 15,
            'name_ru' => 'Бешарыкский р-н',
            'name_uz' => 'Бешариқ туман',
            'name_en' => 'Besharik district'
        ],
        // Kushtepa district (Fergana region)
        170 => [
            'region_id' => 15,
            'name_ru' => 'Куштепинский р-н',
            'name_uz' => 'Қўштепа туман',
            'name_en' => 'Koshtepa district'
        ],
        // Rishtan district (Fergana region)
        171 => [
            'region_id' => 15,
            'name_ru' => 'Риштанский р-н',
            'name_uz' => 'Риштон туман',
            'name_en' => 'Rishton district'
        ],
        // Sokh district (Fergana region)
        172 => [
            'region_id' => 15,
            'name_ru' => 'Сохский р-н',
            'name_uz' => 'Сўх туман',
            'name_en' => 'Sokh district'
        ],
        // Tashlak district (Fergana region)
        173 => [
            'region_id' => 15,
            'name_ru' => 'Ташлакский р-н',
            'name_uz' => 'Тошлоқ туман',
            'name_en' => 'Toshlok district'
        ],
        // Uchkuprik district (Fergana region)
        174 => [
            'region_id' => 15,
            'name_ru' => 'Учкуприкский р-н',
            'name_uz' => 'Учкўприк туман',
            'name_en' => 'Uchkuprik district'
        ],
        // Fergana district (Fergana region)
        175 => [
            'region_id' => 15,
            'name_ru' => 'Ферганский р-н',
            'name_uz' => 'Фарғона туман',
            'name_en' => 'Fergana district'
        ],
        // Uzbekistan district (Fergana region)
        176 => [
            'region_id' => 15,
            'name_ru' => 'Узбекистанский р-н',
            'name_uz' => 'Ўзбекистон туман',
            'name_en' => 'Uzbekistan district'
        ],
        // Kuva district (Fergana region)
        177 => [
            'region_id' => 15,
            'name_ru' => 'Кувинский р-н',
            'name_uz' => 'Қува туман',
            'name_en' => 'Kuva district'
        ],
        // Furkat district (Fergana region)
        178 => [
            'region_id' => 15,
            'name_ru' => 'Фуркатский р-н',
            'name_uz' => 'Фурқат туман',
            'name_en' => 'Furqat district'
        ],
        // Urgench city (Khorezm region)
        179 => [
            'region_id' => 4,
            'name_ru' => 'г.Ургенч',
            'name_uz' => 'Урганч шаҳар',
            'name_en' => 'Urganch city'
        ],
        // Bagat district (Khorezm region)
        180 => [
            'region_id' => 4,
            'name_ru' => 'Багатский р-н',
            'name_uz' => 'Боғот туман',
            'name_en' => 'Bogot district'
        ],
        // Urgench district (Khorezm region)
        181 => [
            'region_id' => 4,
            'name_ru' => 'Ургенчский р-н',
            'name_uz' => 'Урганч туман',
            'name_en' => 'Urganch district'
        ],
        // Kushkupyr district (Khorezm region)
        182 => [
            'region_id' => 4,
            'name_ru' => 'Кушкупырский р-н',
            'name_uz' => 'Қўшкўпир туман',
            'name_en' => 'Koshkopir district'
        ],
        // Khanka district (Khorezm region)
        183 => [
            'region_id' => 4,
            'name_ru' => 'Ханкинский р-н',
            'name_uz' => 'Хонка туман',
            'name_en' => 'Khanka district'
        ],
        // Yangiaryq district (Khorezm region)
        184 => [
            'region_id' => 4,
            'name_ru' => 'Янгиарыкский р-н',
            'name_uz' => 'Янгиариқ туман',
            'name_en' => 'Yangariq district'
        ],
        // Khiva district (Khorezm region)
        185 => [
            'region_id' => 4,
            'name_ru' => 'Хивинский р-н',
            'name_uz' => 'Хива туман',
            'name_en' => 'Khiva district'
        ],
        // Yangibazar district (Khorezm region)
        186 => [
            'region_id' => 4,
            'name_ru' => 'Янгибазарский р-н',
            'name_uz' => 'Янгибозор туман',
            'name_en' => 'Yangibozor district'
        ],
        // Hazarasp district (Khorezm region)
        187 => [
            'region_id' => 4,
            'name_ru' => 'Хазараспский р-н',
            'name_uz' => 'Хозарасп туман',
            'name_en' => 'Khozarasp district'
        ],
        // Shavat district (Khorezm region)
        188 => [
            'region_id' => 4,
            'name_ru' => 'Шаватский р-н',
            'name_uz' => 'Шовот туман',
            'name_en' => 'Shavat district'
        ],
        // Gurlan district (Khorezm region)
        189 => [
            'region_id' => 4,
            'name_ru' => 'Гурленский р-н',
            'name_uz' => 'Гурлан туман',
            'name_en' => 'Gurlan district'
        ],
        // Bektemir district (Tashkent city)
        190 => [
            'region_id' => 6,
            'name_ru' => 'Бектемирский р-н',
            'name_uz' => 'Бектемир тумани',
            'name_en' => 'Bektemir district'
        ],
        // Mirabad district (Tashkent city)
        191 => [
            'region_id' => 6,
            'name_ru' => 'Мирабадский р-н',
            'name_uz' => 'Миробод тумани',
            'name_en' => 'Mirabad district'
        ],
        // M.Ulugbek district (Tashkent city)
        192 => [
            'region_id' => 6,
            'name_ru' => 'М.Улугбекский р-н',
            'name_uz' => 'М.Улуғбек тумани',
            'name_en' => 'M.Ulugbek district'
        ],
        // Sergeli district (Tashkent city)
        193 => [
            'region_id' => 6,
            'name_ru' => 'Сергелийский р-н',
            'name_uz' => 'Сергели тумани',
            'name_en' => 'Sergeli district'
        ],
        // Almazor district (Tashkent city)
        194 => [
            'region_id' => 6,
            'name_ru' => 'Алмазарский р-н',
            'name_uz' => 'Олмазор тумани',
            'name_en' => 'Almazor district'
        ],
        // Uchtepa district (Tashkent city)
        195 => [
            'region_id' => 6,
            'name_ru' => 'Учтепинский р-н',
            'name_uz' => 'Учтепа тумани',
            'name_en' => 'Uchtepa district'
        ],
        // Yashnabad district (Tashkent city)
        196 => [
            'region_id' => 6,
            'name_ru' => 'Яшнабадский р-н',
            'name_uz' => 'Яшнобод тумани',
            'name_en' => 'Yashnabad district'
        ],
        // Chilonzor district (Tashkent city)
        197 => [
            'region_id' => 6,
            'name_ru' => 'Чилонзарский р-н',
            'name_uz' => 'Чилонзор тумани',
            'name_en' => 'Chilonzor district'
        ],
        // Shaikhontakhur district (Tashkent city)
        198 => [
            'region_id' => 6,
            'name_ru' => 'Шайхантахурский р-н',
            'name_uz' => 'Шайхонтохур тумани',
            'name_en' => 'Shaikhontakhur district'
        ],
        // Yunusabad district (Tashkent city)
        199 => [
            'region_id' => 6,
            'name_ru' => 'Юнусабадский р-н',
            'name_uz' => 'Юнусобод тумани',
            'name_en' => 'Yunusabad district'
        ],
        // Yakkasaray district (Tashkent city)
        200 => [
            'region_id' => 6,
            'name_ru' => 'Яккасарайский р-н',
            'name_uz' => 'Яккасарой тумани',
            'name_en' => 'Yakkasaray district'
        ],
        // Piskent district (Tashkent region)
        201 => [
            'region_id' => 5,
            'name_ru' => 'Пскентский р-н',
            'name_uz' => 'Пискент туман',
            'name_en' => 'Piskent district'
        ],
        // Yangi hayot district (Tashkent city)
        216 => [
            'region_id' => 6,
            'name_ru' => 'Янги ҳаётский р-н',
            'name_uz' => 'Янги ҳаёт тумани',
            'name_en' => 'Yangihayot district'
        ],
        // Bozatov district (Karakalpakstan)
        217 => [
            'region_id' => 11,
            'name_ru' => 'Бозатовский р-н',
            'name_uz' => 'Бўзатов тумани',
            'name_en' => 'Bozatov district'
        ],
        // Tuproqqala district (Khorezm region)
        218 => [
            'region_id' => 4,
            'name_ru' => 'Тупраккалинский р-н',
            'name_uz' => 'Тупроққалъа туман',
            'name_en' => 'Tuprokkal\'a district'
        ],
        // Ko'kdala district (Qashqadaryo region)
        219 => [
            'region_id' => 14,
            'name_ru' => 'Кўкдала',
            'name_uz' => 'Ko\'kdala tumani',
            'name_en' => 'Kokdala district'
        ],
        // Bandikhan district (Surkhandarya region)
        220 => [
            'region_id' => 13,
            'name_ru' => 'Бандихон р-н',
            'name_uz' => 'Бандихон',
            'name_en' => 'Bandikhan district'
        ]
    ];

    // Package Types - Common BTS package categories
    const PACKAGE_TYPES = [
        1 => 'Обычная упаковка',        // Standard packaging
        2 => 'Мягкая упаковка',         // Soft packaging
        3 => 'Жесткая упаковка',        // Hard packaging
        4 => 'BTS упаковка',            // BTS packaging
        5 => 'Хрупкая упаковка',        // Fragile packaging
        6 => 'Документы',               // Documents
        7 => 'Ценные отправления',      // Valuable items
        8 => 'Крупногабаритные',        // Large items
    ];

    // Post Types - Common BTS shipment types
    const POST_TYPES = [
        1 => 'Обычное',                 // Regular
        2 => 'Ценное',                  // Valuable
        3 => 'С уведомлением',          // With notification
        4 => 'Документы',               // Documents
        5 => 'Посылка',                 // Package
        6 => 'Экспресс',                // Express
        7 => 'Курьерская доставка',     // Courier delivery
        8 => 'Интернет-заказ',          // Internet order
        9 => 'Наложенный платеж',       // Cash on delivery
        10 => 'Возврат товара',         // Return goods
        11 => 'Коммерческая посылка',   // Commercial package
        12 => 'Образцы товаров',        // Product samples
        13 => 'Подарок',                // Gift
        14 => 'Личные вещи',            // Personal items
        15 => 'Запчасти',               // Spare parts
        16 => 'Техника',                // Equipment
        17 => 'Одежда',                 // Clothing
        18 => 'Продукты питания',       // Food products
        19 => 'Книги/периодика',        // Books/periodicals
        20 => 'Медикаменты',            // Medicines
        21 => 'Косметика',              // Cosmetics
        22 => 'Электроника',            // Electronics
    ];

    // BTS Order Status Constants - Multi-language support based on BTS documentation
    const BTS_STATUSES = [
        -1 => [
            'name_ru' => 'Черновик',
            'name_uz' => 'Qoralama',
            'name_en' => 'Draft'
        ],
        0 => [
            'name_ru' => 'Отказ',
            'name_uz' => 'Rad etilgan',
            'name_en' => 'Refused'
        ],
        1 => [
            'name_ru' => 'У отправителя',
            'name_uz' => 'Yuboruvchida',
            'name_en' => 'At the sender'
        ],
        2 => [
            'name_ru' => 'Курьер принял',
            'name_uz' => 'Kuryer buyurtmani qabul qildi',
            'name_en' => 'Courier accepted'
        ],
        3 => [
            'name_ru' => 'В офисе отправления',
            'name_uz' => 'Jo\'natuvchining ofisida',
            'name_en' => 'At the sending office'
        ],
        4 => [
            'name_ru' => 'В офисе доставки',
            'name_uz' => 'Yetkazish ofisida',
            'name_en' => 'At the delivery office'
        ],
        5 => [
            'name_ru' => 'Курьер доставляет',
            'name_uz' => 'Kuryer yetkazmoqda',
            'name_en' => 'Courier delivering'
        ],
        6 => [
            'name_ru' => 'Доставлен',
            'name_uz' => 'Yetkazib berilgan',
            'name_en' => 'Delivered'
        ],
        7 => [
            'name_ru' => 'Возврат',
            'name_uz' => 'Qaytarish',
            'name_en' => 'Return'
        ],
        8 => [
            'name_ru' => 'В промежуточном офисе',
            'name_uz' => 'Tranzit ofisda',
            'name_en' => 'At the intermediate office'
        ],
        10 => [
            'name_ru' => 'В сортировочном центре (РЦ)',
            'name_uz' => 'Saralash markazida',
            'name_en' => 'Sorting Center'
        ],
        31 => [
            'name_ru' => 'На складе',
            'name_uz' => 'Omborda',
            'name_en' => 'In the warehouse'
        ],
        32 => [
            'name_ru' => 'В мешке',
            'name_uz' => 'Qopda',
            'name_en' => 'In the bag'
        ],
        33 => [
            'name_ru' => 'В перевозке',
            'name_uz' => 'Transportda',
            'name_en' => 'In transit'
        ],
        34 => [
            'name_ru' => 'В РЦ Курьера',
            'name_uz' => 'RC kuryerka',
            'name_en' => 'At the courier distribution center'
        ]
    ];

    // Legacy compatibility - Simple order statuses (keeping for backward compatibility)
    const ORDER_STATUSES = [
        1 => 'У клиента',                    // At client
        2 => 'Принято к отправке',           // Accepted for shipment  
        3 => 'В пути',                       // In transit
        4 => 'Прибыло в пункт назначения',   // Arrived at destination
        5 => 'Выдано получателю',            // Delivered to recipient
        6 => 'Возвращено отправителю',       // Returned to sender
        7 => 'Отменено',                     // Cancelled
        8 => 'На складе',                    // At warehouse
        9 => 'Ожидает получения',            // Awaiting pickup
        10 => 'Утеряно',                     // Lost
        11 => 'Поврежденное',                // Damaged
        12 => 'Просрочено',                  // Overdue
        13 => 'Задержано',                   // Delayed
        14 => 'На доставке',                 // Out for delivery
        15 => 'Недоставлено',                // Undelivered
    ];

    public function __construct($config = [])
    {
        // Allow configuration override
        if (isset($config['baseUrl'])) {
            $this->baseUrl = $config['baseUrl'];
        }
        if (isset($config['token'])) {
            $this->token = $config['token'];
        }
        if (isset($config['username'])) {
            $this->username = $config['username'];
        }
        if (isset($config['password'])) {
            $this->password = $config['password'];
        }
        if (isset($config['inn'])) {
            $this->inn = $config['inn'];
        }
        
        // Load from Yii params if available
        if (!$this->token && isset(Yii::$app->params['bts_token'])) {
            $this->token = Yii::$app->params['bts_token'];
        }
        if (!$this->username && isset(Yii::$app->params['bts_username'])) {
            $this->username = Yii::$app->params['bts_username'];
        }
        if (!$this->password && isset(Yii::$app->params['bts_password'])) {
            $this->password = Yii::$app->params['bts_password'];
        }
        if (!$this->inn && isset(Yii::$app->params['bts_inn'])) {
            $this->inn = Yii::$app->params['bts_inn'];
        }
    }

    /**
     * Get list of available regions
     * @param string $language Language code (ru, uz, en)
     * @return array
     */
    public static function getRegions($language = 'ru')
    {
        $regions = [];
        foreach (self::REGIONS as $id => $region) {
            $key = 'name_' . $language;
            $regions[$id] = isset($region[$key]) ? $region[$key] : $region['name_ru'];
        }
        return $regions;
    }

    /**
     * Get list of cities with optional region filtering
     * @param int|null $regionId Filter by region ID
     * @param string $language Language code (ru, uz, en)
     * @return array
     */
    public static function getCities($regionId = null, $language = 'ru')
    {
        $cities = [];
        $key = 'name_' . $language;
        
        foreach (self::CITIES as $id => $city) {
            if ($regionId === null || $city['region_id'] == $regionId) {
                $cities[$id] = [
                    'region_id' => $city['region_id'],
                    'name' => isset($city[$key]) ? $city[$key] : $city['name_ru']
                ];
            }
        }
        
        return $cities;
    }

    /**
     * Get detailed regions with all language variants
     * @return array
     */
    public static function getRegionsDetailed()
    {
        return self::REGIONS;
    }

    /**
     * Get detailed cities with all language variants
     * @param int|null $regionId Filter by region ID
     * @return array
     */
    public static function getCitiesDetailed($regionId = null, $language = 'ru')
    {
        $cities = [];
        $key = 'name_' . $language;

        if ($regionId === null) {
            return self::CITIES;
        }
                
        foreach (self::CITIES as $id => $city) {
            if ($regionId === null || $city['region_id'] == $regionId) {
                $cities[$id] = [
                    'region_id' => $city['region_id'],
                    'name' => isset($city[$key]) ? $city[$key] : $city['name_ru']
                ];
            }
        }

        return $cities;
    }

    /**
     * Get region name by ID
     * @param int $regionId
     * @param string $language Language code (ru, uz, en)
     * @return string|null
     */
    public static function getRegionName($regionId, $language = 'ru')
    {
        if (!isset(self::REGIONS[$regionId])) {
            return null;
        }
        
        $key = 'name_' . $language;
        $region = self::REGIONS[$regionId];
        return isset($region[$key]) ? $region[$key] : $region['name_ru'];
    }

    /**
     * Get city name by ID
     * @param int $cityId
     * @param string $language Language code (ru, uz, en)
     * @return string|null
     */
    public static function getCityName($cityId, $language = 'ru')
    {
        if (!isset(self::CITIES[$cityId])) {
            return null;
        }
        
        $key = 'name_' . $language;
        $city = self::CITIES[$cityId];
        return isset($city[$key]) ? $city[$key] : $city['name_ru'];
    }

    /**
     * Get city by name (case-insensitive search)
     * @param string $cityName
     * @param string $language Language code (ru, uz, en)
     * @return array|null
     */
    public static function findCityByName($cityName, $language = 'ru')
    {
        $key = 'name_' . $language;
        
        foreach (self::CITIES as $id => $city) {
            $name = isset($city[$key]) ? $city[$key] : $city['name_ru'];
            if (strcasecmp($name, $cityName) === 0) {
                return ['id' => $id] + $city;
            }
        }
        return null;
    }

    /**
     * Search cities by partial name match
     * @param string $searchTerm
     * @param string $language Language code (ru, uz, en)
     * @param int|null $regionId Filter by region ID
     * @return array
     */
    public static function searchCities($searchTerm, $language = 'ru', $regionId = null)
    {
        $results = [];
        $key = 'name_' . $language;
        $searchTerm = mb_strtolower($searchTerm);
        
        foreach (self::CITIES as $id => $city) {
            if ($regionId !== null && $city['region_id'] != $regionId) {
                continue;
            }
            
            $name = isset($city[$key]) ? $city[$key] : $city['name_ru'];
            if (mb_strpos(mb_strtolower($name), $searchTerm) !== false) {
                $results[$id] = ['id' => $id] + $city;
            }
        }
        
        return $results;
    }

    /**
     * Get complete address information for a city
     * @param int $cityId
     * @param string $language Language code (ru, uz, en)
     * @return array|null
     */
    public static function getAddressInfo($cityId, $language = 'ru')
    {
        if (!isset(self::CITIES[$cityId])) {
            return null;
        }
        
        $city = self::CITIES[$cityId];
        $regionName = self::getRegionName($city['region_id'], $language);
        $cityName = self::getCityName($cityId, $language);
        
        return [
            'city_id' => $cityId,
            'city_name' => $cityName,
            'region_id' => $city['region_id'],
            'region_name' => $regionName,
            'full_address' => $regionName . ', ' . $cityName
        ];
    }

    /**
     * Get package types
     * @return array
     */
    public static function getPackageTypes()
    {
        return self::PACKAGE_TYPES;
    }

    /**
     * Get post types
     * @return array
     */
    public static function getPostTypes()
    {
        return self::POST_TYPES;
    }

    /**
     * Get order statuses (legacy)
     * @return array
     */
    public static function getOrderStatuses()
    {
        return self::ORDER_STATUSES;
    }

    /**
     * Get BTS statuses with multi-language support
     * @param string $language Language code (ru, uz, en)
     * @return array
     */
    public static function getBtsStatuses($language = 'ru')
    {
        $statuses = [];
        foreach (self::BTS_STATUSES as $id => $status) {
            $key = 'name_' . $language;
            $statuses[$id] = isset($status[$key]) ? $status[$key] : $status['name_ru'];
        }
        return $statuses;
    }

    /**
     * Get BTS status label by ID and language
     * @param int $statusId
     * @param string $language Language code (ru, uz, en)
     * @return string|null
     */
    public static function getBtsStatusLabel($statusId, $language = 'ru')
    {
        if (!isset(self::BTS_STATUSES[$statusId])) {
            return null;
        }
        
        $key = 'name_' . $language;
        $status = self::BTS_STATUSES[$statusId];
        return isset($status[$key]) ? $status[$key] : $status['name_ru'];
    }

    /**
     * Get detailed BTS statuses with all language variants
     * @return array
     */
    public static function getBtsStatusesDetailed()
    {
        return self::BTS_STATUSES;
    }

    /**
     * Check if BTS status ID is valid
     * @param int $statusId
     * @return bool
     */
    public static function isValidBtsStatus($statusId)
    {
        return isset(self::BTS_STATUSES[$statusId]);
    }

    /**
     * Get API Token
     * @return array
     */
    public function getApiToken()
    {
        $data = [
            'username' => $this->username,
            'password' => $this->password,
            'inn' => $this->inn
        ];

        $response = $this->makeRequest('POST', 'auth/get-token', $data, false);
        
        if ($response['success'] && isset($response['data']['data']['token'])) {
            $this->token = $response['data']['data']['token'];
        }
        
        return $response;
    }

    /**
     * Create order request
     * @param array $orderData
     * @return array
     */
    public function createOrder($orderData)
    {
        return $this->makeRequest('POST', 'order/add', $orderData);
    }

    /**
     * Edit existing order
     * @param int $orderId
     * @param array $orderData
     * @return array
     */
    public function editOrder($orderId, $orderData)
    {
        return $this->makeRequest('POST', "order/edit/{$orderId}", $orderData);
    }

    /**
     * Get order information
     * @param int $orderId
     * @return array
     */
    public function getOrderInfo($orderId)
    {
        return $this->makeRequest('GET', "order/detail", ['id' => $orderId]);
    }

    /**
     * Delete order
     * @param int $orderId
     * @return array
     */
    public function deleteOrder($orderId)
    {
        return $this->makeRequest('POST', "order/delete/{$orderId}");
    }

    /**
     * Calculate delivery cost
     * @param array $calculatorData
     * @return array
     */
    public function calculateDelivery($calculatorData)
    {
        return $this->makeRequest('POST', 'order/calculate', $calculatorData);
    }

    /**
     * Get current order status
     * @param int $orderId
     * @return array
     */
    public function getOrderStatus($orderId)
    {
        return $this->makeRequest('GET', 'order/status', ['id' => $orderId]);
    }

    /**
     * Get order tracking information
     * @param int $orderId
     * @return array
     */
    public function getOrderTracking($orderId)
    {
        return $this->makeRequest('GET', 'order/tracking', ['id' => $orderId]);
    }

    /**
     * Track order status - as per BTS API documentation
     * GET /order/track&id=<orderId>
     * 
     * Full URL: http://api.logistics.example.com:8080/index.php?r=v1/order/track&id=<orderId>
     * Headers: Authorization: Bearer <token>
     * 
     * Expected Response:
     * {
     *     "orderId": 229234,
     *     "status": {
     *         "id": 1,
     *         "name": "new"
     *     }
     * }
     * 
     * @param int $orderId BTS Order ID
     * @return array Response with success flag, data, and error info
     */
    public function trackOrder($btsOrderId)
    {
        return $this->makeRequest('GET', 'order/track', ['id' => $btsOrderId]);
    }

    /**
     * Get order history - as per BTS API documentation
     * GET /order/history&id=<orderId>
     * 
     * Full URL: http://api.logistics.example.com:8080/index.php?r=v1/order/history&id=<orderId>
     * Headers: Authorization: Bearer <token>
     * 
     * Expected Response:
     * [
     *   {
     *     "message": "message1",
     *     "timestamp": 1720690236,
     *     "status_id": 4,
     *     "location": "QUSHBEGI BTS (PVZ)",
     *     "trackingLink": "https://..."
     *   },
     *   {
     *     "message": "message2",
     *     "timestamp": 1720690321,
     *     "status_id": 5,
     *     "location": "JARQO'RG'ON BTS",
     *     "trackingLink": "https://..."
     *   }
     * ]
     * 
     * @param int $btsOrderId BTS Order ID
     * @return array Response with success flag, data, and error info
     */
    public function getOrderHistory($btsOrderId)
    {
        return $this->makeRequest('GET', 'order/history', ['id' => $btsOrderId]);
    }

    /**
     * Get list of available statuses
     * @return array
     */
    public function getStatusList()
    {
        return $this->makeRequest('GET', 'status/list');
    }

    /**
     * Legacy method for backward compatibility
     * @param string $link
     * @param string|null $data
     * @return string
     */
    public function request($link, $data = null)
    {
        if ($data === null) {
            $response = $this->makeRequest('GET', $link);
        } else {
            $response = $this->makeRequest('POST', $link, json_decode($data, true));
        }
        
        return json_encode($response['data']);
    }

    /**
     * Make HTTP request to BTS API
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param bool $useAuth
     * @return array
     */
    protected function makeRequest($method, $endpoint, $data = [], $useAuth = true)
    {
        $url = $this->baseUrl . '?r=' . $this->apiVersion . '/' . $endpoint;
        
        $headers = ['Content-Type: application/json'];
        
        if ($useAuth && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '&' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return $this->formatError('cURL Error: ' . $error, 0);
        }

        $decodedResponse = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'httpCode' => $httpCode,
            'data' => $decodedResponse,
            'error' => $this->getErrorMessage($httpCode, $decodedResponse)
        ];
    }

    /**
     * Get error message based on HTTP code and response
     * @param int $httpCode
     * @param array $response
     * @return string|null
     */
    protected function getErrorMessage($httpCode, $response)
    {
        if ($httpCode >= 200 && $httpCode < 300) {
            return null;
        }

        // BTS API specific error messages
        if (isset($response['message']['text'])) {
            return $response['message']['text'];
        }
        
        if (isset($response['errors'])) {
            return is_array($response['errors']) ? json_encode($response['errors']) : $response['errors'];
        }

        // Generic HTTP error messages
        switch ($httpCode) {
            case self::ERROR_BAD_REQUEST:
                return 'Bad Request - Your request is invalid';
            case self::ERROR_UNAUTHORIZED:
                return 'Unauthorized - Your API key is wrong';
            case self::ERROR_FORBIDDEN:
                return 'Forbidden - Access denied';
            case self::ERROR_NOT_FOUND:
                return 'Not Found - The requested resource could not be found';
            case self::ERROR_METHOD_NOT_ALLOWED:
                return 'Method Not Allowed - Invalid HTTP method';
            case self::ERROR_NOT_ACCEPTABLE:
                return 'Not Acceptable - You requested a format that isn\'t json';
            case self::ERROR_GONE:
                return 'Gone - The requested resource has been removed';
            case self::ERROR_TOO_MANY_REQUESTS:
                return 'Too Many Requests - You\'re requesting too many! Slow down!';
            case self::ERROR_INTERNAL_SERVER:
                return 'Internal Server Error - We had a problem with our server';
            case self::ERROR_SERVICE_UNAVAILABLE:
                return 'Service Unavailable - We\'re temporarily offline for maintenance';
            default:
                return 'Unknown error occurred';
        }
    }

    /**
     * Format error response
     * @param string $message
     * @param int $code
     * @return array
     */
    protected function formatError($message, $code)
    {
        return [
            'success' => false,
            'httpCode' => $code,
            'data' => null,
            'error' => $message
        ];
    }

    /**
     * Validate required order data
     * @param array $data
     * @return array
     */
    public function validateOrderData($data)
    {
        $required = [
            'senderCityId', 'senderAddress', 'senderReal', 'senderPhone',
            'weight', 'packageId', 'postTypeId', 'receiver', 'receiverAddress',
            'receiverCityId', 'receiverPhone'
        ];

        $errors = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "The '{$field}' field is required";
            }
        }

        // Validate city IDs with new structure
        if (isset($data['senderCityId']) && !isset(self::CITIES[$data['senderCityId']])) {
            $errors['senderCityId'] = "Invalid sender city ID";
        }
        
        if (isset($data['receiverCityId']) && !isset(self::CITIES[$data['receiverCityId']])) {
            $errors['receiverCityId'] = "Invalid receiver city ID";
        }

        // Validate package and post type IDs
        if (isset($data['packageId']) && !isset(self::PACKAGE_TYPES[$data['packageId']])) {
            $errors['packageId'] = "Invalid package type ID";
        }
        
        if (isset($data['postTypeId']) && !isset(self::POST_TYPES[$data['postTypeId']])) {
            $errors['postTypeId'] = "Invalid post type ID";
        }

        return $errors;
    }
}
