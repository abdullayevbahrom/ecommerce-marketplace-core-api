<?php

use yii\db\Migration;

class m260402_120000_add_code_to_filter_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%filter}}', 'code', $this->string(100)->null()->after('type'));

        $rows = (new \yii\db\Query())
            ->from('{{%filter}}')
            ->select(['id', 'name_ru', 'name_uz', 'name_en'])
            ->where(['parent_id' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $usedCodes = [];

        foreach ($rows as $row) {
            $base = $this->slugify($row['name_ru'] ?: ($row['name_uz'] ?: ($row['name_en'] ?: 'filter')));
            if ($base === '') {
                $base = 'filter';
            }

            $code = $base;
            $suffix = 1;

            while (isset($usedCodes[$code]) || $this->codeExists($code, (int)$row['id'])) {
                $suffix++;
                $code = $base . '-' . $suffix;
            }

            $usedCodes[$code] = true;

            $this->update('{{%filter}}', ['code' => $code], ['id' => (int)$row['id']]);
        }

        $this->createIndex('idx-filter-code', '{{%filter}}', 'code', true);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-filter-code', '{{%filter}}');
        $this->dropColumn('{{%filter}}', 'code');
    }

    private function codeExists(string $code, int $excludeId): bool
    {
        return (new \yii\db\Query())
            ->from('{{%filter}}')
            ->where(['code' => $code])
            ->andWhere(['<>', 'id', $excludeId])
            ->exists($this->db);
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'ў' => 'o', 'қ' => 'q', 'ғ' => 'g', 'ҳ' => 'h',
        ];

        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value);
        $value = trim((string)$value, '-');

        return preg_replace('/-+/', '-', $value);
    }
}
