<?php

use yii\db\Migration;

class m260424_410000_feedback_reply_status extends Migration
{
    public function up()
    {
        $this->addColumn('{{%feedback}}', 'status',
            $this->string(20)->notNull()->defaultValue('new')->after('is_read'));
        $this->addColumn('{{%feedback}}', 'reply_text',
            $this->text()->null()->after('status'));
        $this->addColumn('{{%feedback}}', 'replied_at',
            $this->integer()->null()->after('reply_text'));

        // Migrate existing read/unread
        $this->update('{{%feedback}}', ['status' => 'read'],   ['is_read' => 1]);
        $this->update('{{%feedback}}', ['status' => 'new'],    ['is_read' => 0]);

        $this->createIndex('idx_feedback_status', '{{%feedback}}', 'status');
    }

    public function down()
    {
        $this->dropIndex('idx_feedback_status', '{{%feedback}}');
        $this->dropColumn('{{%feedback}}', 'replied_at');
        $this->dropColumn('{{%feedback}}', 'reply_text');
        $this->dropColumn('{{%feedback}}', 'status');
    }
}
