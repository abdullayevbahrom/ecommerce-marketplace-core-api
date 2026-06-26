<?php

namespace app\commands;

use app\models\City;
use app\models\Region;
use yii\console\Controller;
use yii\console\ExitCode;
use Yii;
use yii\helpers\Console;

class RegionController extends Controller
{
    public function actionFillRegion()
    {
        $this->stdout("BTS API-dan ma'lumotlar olinmoqda...\n", Console::FG_CYAN);
        $regions = Yii::$app->bts->fetchRegions();

        if (empty($regions)) {
            $this->stdout("API-dan hech qanday ma'lumot kelmadi.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $tx = Yii::$app->db->beginTransaction();

        try {
            foreach ($regions as $regionData) {
                $region = Region::findOne(['bts_id' => $regionData['code']]);

                if (!$region) {
                    $region = new Region();
                    $region->bts_id = (string) $regionData['code'];
                }

                $region->suppressSyncEvents = true;
                $region->name_uz = $regionData['name']['uz'];
                $region->name_ru = $regionData['name']['ru'];
                $region->name_en = $regionData['name']['en'];
                $region->status = Region::STATUS_ACTIVE;

                if (!$region->save()) {
                    throw new \Exception("Regionni saqlab bo'lmadi: " . json_encode($region->getErrors()));
                }

                $this->stdout("Region muvaffaqiyatli saqlandi: {$region->name_ru}\n", Console::FG_GREEN);

                if (!empty($regionData['cities'])) {
                    foreach ($regionData['cities'] as $cityData) {
                        $city = City::findOne(['bts_id' => $cityData['code']]);
                        if (!$city) {
                            $city = new City();
                            $city->bts_id = $cityData['code'];
                        }

                        $city->region_id = $region->id;
                        $city->bts_region_id = $cityData['region_code'];
                        $city->name_uz = $cityData['name']['uz'];
                        $city->name_ru = $cityData['name']['ru'];
                        $city->name_en = $cityData['name']['en'];
                        $city->status = City::STATUS_ACTIVE;

                        if (!$city->save()) {
                            throw new \Exception("Cityni saqlab bo'lmadi: " . json_encode($city->getErrors()));
                        }
                    }
                }
            }

            $tx->commit();
            $this->stdout("\nSinxronizatsiya muvaffaqiyatli yakunlandi!\n", Console::FG_GREEN, Console::BOLD);

            return ExitCode::OK;
        } catch (\Throwable $th) {
            $tx->rollBack();

            $this->stdout("\nXatolik yuz berdi. Tranzaksiya bekor qilindi (Rollback).\n", Console::FG_RED, Console::BOLD);
            $this->stdout("Xabar: " . $th->getMessage() . "\n", Console::FG_RED);
            $this->stdout("Fayl: " . $th->getFile() . ":" . $th->getLine() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
