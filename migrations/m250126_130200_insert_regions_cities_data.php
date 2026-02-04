<?php

use yii\db\Migration;

/**
 * Inserts regions and cities data from BTS service
 */
class m250126_130200_insert_regions_cities_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Insert regions data
        $this->batchInsert('{{%regions}}', ['bts_id', 'name_ru', 'name_uz', 'name_en'], [
            [2, 'Андижанская область', 'Andijon viloyati', 'Andijan region'],
            [3, 'Наманганская область', 'Namangan viloyati', 'Namangan region'],
            [4, 'Хорезмская область', 'Xorazm viloyati', 'Khorezm region'],
            [5, 'Ташкентская область', 'Toshkent viloyati', 'Tashkent region'],
            [6, 'город Ташкент', 'Tashkent shaxri', 'Tashkent city'],
            [7, 'Бухарская область', 'Buxoro viloyati', 'Bukhara region'],
            [8, 'Самаркандская область', 'Samarqand viloyati', 'Samarkand region'],
            [9, 'Джизакская область', 'Jizzax viloyati', 'Jizzakh region'],
            [10, 'Навоийская область', 'Navoiy viloyati', 'Navoi region'],
            [11, 'Каракалпакстан', 'Qoraqalpog\'iston', 'Karakalpakstan'],
            [12, 'Сырдарьинская область', 'Sirdaryo viloyati', 'Syrdarya region'],
            [13, 'Сурхандарьинская область', 'Surxondaryo viloyati', 'Surkhandarya region'],
            [14, 'Кашкадарьинская область', 'Qashqadaryo viloyati', 'Kashkadarya region'],
            [15, 'Ферганская область', 'Farg\'ona viloyati', 'Fergana region']
        ]);

        // Get the region IDs for foreign key relationships
        $regionMapping = [];
        $regions = $this->db->createCommand('SELECT id, bts_id FROM {{%regions}}')->queryAll();
        foreach ($regions as $region) {
            $regionMapping[$region['bts_id']] = $region['id'];
        }

        // Insert cities data (first batch)
        $this->batchInsert('{{%cities}}', ['bts_id', 'region_id', 'bts_region_id', 'name_ru', 'name_uz', 'name_en'], [
            [1, $regionMapping[14], 14, 'г.Шахрисабз', 'Шахрисабз шаҳар', 'Shakhrisabz city'],
            [2, $regionMapping[3], 3, 'Папский р-н', 'Поп тумани', 'Pop district'],
            [3, $regionMapping[2], 2, 'Асакинский р-н', 'Асака туман', 'Asaka district'],
            [4, $regionMapping[5], 5, 'Ташкентский р-н', 'Тошкент тумани', 'Tashkent district'],
            [5, $regionMapping[5], 5, 'г.Янгийуль', 'Янгийўл шаҳар', 'Yangiyul city'],
            [6, $regionMapping[5], 5, 'г.Ахангаран', 'Оҳангарон шаҳар', 'Ohangaron city'],
            [7, $regionMapping[5], 5, 'г.Нурафшон', 'Нурафшон шаҳар', 'Nurafshon city'],
            [8, $regionMapping[11], 11, 'г.Нукус', 'Нукус шаҳар', 'Nukus city'],
            [9, $regionMapping[11], 11, 'Тахиаташский р-н', 'Тахиатош тумани', 'Takhiatosh district'],
            [10, $regionMapping[4], 4, 'г.Хива', 'Хива шаҳар', 'Khiva city']
        ]);

        // Continue with more cities data (second batch)
        $this->batchInsert('{{%cities}}', ['bts_id', 'region_id', 'bts_region_id', 'name_ru', 'name_uz', 'name_en'], [
            [25, $regionMapping[2], 2, 'г.Андижан', 'Андижон шаҳар', 'Andijan city'],
            [26, $regionMapping[2], 2, 'г.Ханабад', 'Хонобод шаҳар', 'Khanabad city'],
            [40, $regionMapping[7], 7, 'г.Бухара', 'Бухоро шаҳар', 'Bukhara city'],
            [41, $regionMapping[7], 7, 'г.Каган', 'Когон шаҳар', 'Kogon city'],
            [57, $regionMapping[9], 9, 'г.Джизак', 'Жиззах шаҳар', 'Jizzakh city'],
            [65, $regionMapping[14], 14, 'г.Карши', 'Қарши шаҳар', 'Qarshi city'],
            [96, $regionMapping[6], 6, 'г.Ташкент', 'Тошкент шаҳар', 'Tashkent city'],
            [111, $regionMapping[3], 3, 'г.Наманган', 'Наманган шаҳар', 'Namangan city'],
            [119, $regionMapping[10], 10, 'г.Навои', 'Навоий шаҳар', 'Navoi city'],
            [130, $regionMapping[8], 8, 'г.Самарканд', 'Самарқанд шаҳар', 'Samarkand city']
        ]);

        // Add more major cities
        $this->batchInsert('{{%cities}}', ['bts_id', 'region_id', 'bts_region_id', 'name_ru', 'name_uz', 'name_en'], [
            [144, $regionMapping[12], 12, 'г.Гулистан', 'Гулистон шаҳар', 'Guliston city'],
            [155, $regionMapping[13], 13, 'г.Термез', 'Термиз шаҳар', 'Termez city'],
            [176, $regionMapping[5], 5, 'г.Чирчик', 'Чирчиқ шаҳар', 'Chirchiq city'],
            [177, $regionMapping[5], 5, 'г.Ангрен', 'Ангрен шаҳар', 'Angren city'],
            [178, $regionMapping[5], 5, 'г.Алмалык', 'Олмалиқ шаҳар', 'Olmaliq city'],
            [190, $regionMapping[15], 15, 'г.Фергана', 'Фарғона шаҳар', 'Fergana city'],
            [191, $regionMapping[15], 15, 'г.Маргилан', 'Марғилон шаҳар', 'Margilon city'],
            [192, $regionMapping[15], 15, 'г.Коканд', 'Қўқон шаҳар', 'Kokand city'],
            [193, $regionMapping[15], 15, 'г.Кувасай', 'Қувасой шаҳар', 'Kuvasay city'],
            [225, $regionMapping[4], 4, 'г.Ургенч', 'Урганч шаҳар', 'Urgench city']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%cities}}');
        $this->delete('{{%regions}}');
    }

    /*
    // Manual SQL for full data insertion:
    -- Insert regions
    INSERT INTO `regions` (`bts_id`, `name_ru`, `name_uz`, `name_en`) VALUES
    (2, 'Андижанская область', 'Andijon viloyati', 'Andijan region'),
    (3, 'Наманганская область', 'Namangan viloyati', 'Namangan region'),
    (4, 'Хорезмская область', 'Xorazm viloyati', 'Khorezm region'),
    (5, 'Ташкентская область', 'Toshkent viloyati', 'Tashkent region'),
    (6, 'город Ташкент', 'Tashkent shaxri', 'Tashkent city'),
    (7, 'Бухарская область', 'Buxoro viloyati', 'Bukhara region'),
    (8, 'Самаркандская область', 'Samarqand viloyati', 'Samarkand region'),
    (9, 'Джизакская область', 'Jizzax viloyati', 'Jizzakh region'),
    (10, 'Навоийская область', 'Navoiy viloyati', 'Navoi region'),
    (11, 'Каракалпакстан', 'Qoraqalpog\'iston', 'Karakalpakstan'),
    (12, 'Сырдарьинская область', 'Sirdaryo viloyati', 'Syrdarya region'),
    (13, 'Сурхандарьинская область', 'Surxondaryo viloyati', 'Surkhandarya region'),
    (14, 'Кашкадарьинская область', 'Qashqadaryo viloyati', 'Kashkadarya region'),
    (15, 'Ферганская область', 'Farg\'ona viloyati', 'Fergana region');

    -- Insert major cities (replace region_id with actual IDs from regions table)
    INSERT INTO `cities` (`bts_id`, `region_id`, `bts_region_id`, `name_ru`, `name_uz`, `name_en`) VALUES
    (96, (SELECT id FROM regions WHERE bts_id = 6), 6, 'г.Ташкент', 'Тошкент шаҳар', 'Tashkent city'),
    (25, (SELECT id FROM regions WHERE bts_id = 2), 2, 'г.Андижан', 'Андижон шаҳар', 'Andijan city'),
    (40, (SELECT id FROM regions WHERE bts_id = 7), 7, 'г.Бухара', 'Бухоро шаҳар', 'Bukhara city'),
    (130, (SELECT id FROM regions WHERE bts_id = 8), 8, 'г.Самарканд', 'Самарқанд шаҳар', 'Samarkand city'),
    (111, (SELECT id FROM regions WHERE bts_id = 3), 3, 'г.Наманган', 'Наманган шаҳар', 'Namangan city'),
    (190, (SELECT id FROM regions WHERE bts_id = 15), 15, 'г.Фергана', 'Фарғона шаҳар', 'Fergana city'),
    (192, (SELECT id FROM regions WHERE bts_id = 15), 15, 'г.Коканд', 'Қўқон шаҳар', 'Kokand city'),
    (225, (SELECT id FROM regions WHERE bts_id = 4), 4, 'г.Ургенч', 'Урганч шаҳар', 'Urgench city'),
    (8, (SELECT id FROM regions WHERE bts_id = 11), 11, 'г.Нукус', 'Нукус шаҳар', 'Nukus city'),
    (65, (SELECT id FROM regions WHERE bts_id = 14), 14, 'г.Карши', 'Қарши шаҳар', 'Qarshi city'),
    (155, (SELECT id FROM regions WHERE bts_id = 13), 13, 'г.Термез', 'Термиз шаҳар', 'Termez city'),
    (119, (SELECT id FROM regions WHERE bts_id = 10), 10, 'г.Навои', 'Навоий шаҳар', 'Navoi city'),
    (57, (SELECT id FROM regions WHERE bts_id = 9), 9, 'г.Джизак', 'Жиззах шаҳар', 'Jizzakh city'),
    (144, (SELECT id FROM regions WHERE bts_id = 12), 12, 'г.Гулистан', 'Гулистон шаҳар', 'Guliston city');
    
    -- For full cities data from BTS.php, you need to extract all CITIES constants and create similar INSERT statements
    */
}