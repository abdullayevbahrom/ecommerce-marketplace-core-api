<?php

use yii\db\Migration;

class m260204_094027_moderation_comments_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%moderation_comments}}', [
            'id' => $this->primaryKey(),

            'entity_type' => $this->string(50)->notNull(),
            'entity_id'   => $this->integer()->notNull(),

            'action'      => $this->string(50)->notNull(), // block | unblock | reject | approve
            'comment'     => $this->text()->null(),

            'moderator_id' => $this->integer()->notNull(),
            'is_sent_to_warehouse' => $this->boolean()->defaultValue(false),

            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex(
            'idx-moderation_comments-entity',
            '{{%moderation_comments}}',
            ['entity_type', 'entity_id']
        );

        $this->createIndex(
            'idx-moderation_comments-moderator',
            '{{%moderation_comments}}',
            'moderator_id'
        );

        $this->addForeignKey(
            'fk-moderation_comments-moderator',
            '{{%moderation_comments}}',
            'moderator_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-moderation_comments-moderator', '{{%moderation_comments}}');
        $this->dropTable('{{%moderation_comments}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260204_094027_moderation_comments_table cannot be reverted.\n";

        return false;
    }
    */
}
