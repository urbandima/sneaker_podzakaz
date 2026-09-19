<?php

use yii\db\Migration;

/**
 * B0.5: Создание таблицы company_settings для хранения реквизитов компании
 */
class m260329_154500_create_company_settings_table extends Migration
{
    public function safeUp()
    {
        // Таблица уже создаётся более ранней миграцией m241023_200000_create_company_settings_and_statuses
        // со схемой, которую реально использует app\backend\modules\admin\models\CompanySettings
        // (name/unp/address/bank/bic/account/phone/email/offer_url). Эта миграция — дублирующая
        // попытка создать ту же таблицу с другой, неиспользуемой схемой; делаем её безопасным no-op,
        // чтобы не ронять чистые инсталляции (CI, новые окружения).
        if ($this->db->schema->getTableSchema('{{%company_settings}}', true) !== null) {
            echo "    > skipped: {{%company_settings}} уже создана миграцией m241023_200000_create_company_settings_and_statuses\n";
            return true;
        }

        $this->createTable('{{%company_settings}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull()->comment('Название компании'),
            'legal_name' => $this->string(255)->comment('Юридическое название'),
            'unp' => $this->string(50)->comment('УНП'),
            'address' => $this->text()->comment('Юридический адрес'),
            'phone' => $this->string(50)->comment('Телефон'),
            'email' => $this->string(100)->comment('Email'),
            'work_time' => $this->string(255)->comment('Режим работы'),
            'bank_name' => $this->string(255)->comment('Название банка'),
            'bank_account' => $this->string(100)->comment('Расчетный счет'),
            'bank_code' => $this->string(50)->comment('БИК банка'),
            'director' => $this->string(255)->comment('Директор'),
            'accountant' => $this->string(255)->comment('Главный бухгалтер'),
            'logo_url' => $this->string(500)->comment('URL логотипа'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        // Вставляем начальные данные
        $this->insert('{{%company_settings}}', [
            'name' => 'СНИКЕРХЭД',
            'legal_name' => 'ИП Иванов Иван Иванович',
            'unp' => '123456789',
            'address' => '220000, г. Минск, ул. Купревича 1, корп. 1',
            'phone' => '+375 (29) 123-45-67',
            'email' => 'info@sneakerhead.by',
            'work_time' => 'Пн-Вс: 10:00-22:00',
            'bank_name' => 'ОАО "Беларусбанк"',
            'bank_account' => 'BY00UNBS00000000000000000000',
            'bank_code' => 'UNBSBY2X',
            'director' => 'Иванов И.И.',
            'accountant' => 'Петрова П.П.',
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->createIndex('idx-company_settings-created_at', '{{%company_settings}}', 'created_at');
    }

    public function safeDown()
    {
        // Откатываем, только если таблицу создала именно эта миграция (маркер — колонка
        // bank_name, которой нет в схеме m241023_200000_create_company_settings_and_statuses).
        $schema = $this->db->schema->getTableSchema('{{%company_settings}}', true);
        if ($schema !== null && in_array('bank_name', $schema->columnNames, true)) {
            $this->dropTable('{{%company_settings}}');
        } else {
            echo "    > skipped down: {{%company_settings}} принадлежит другой миграции\n";
        }
    }
}
