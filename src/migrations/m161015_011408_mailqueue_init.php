<?php
/**
 * @link https://github.com/tigrov/yii2-mailqueue
 * @author Sergei Tigrov rrr-r@ya.ru
 */

use yii\db\Schema;
use yii\db\Migration;

class m161015_011408_mailqueue_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $tableName = $this->db->schema->getRawTableName(\Yii::$app->getMailer()->table);
		$this->createTable($tableName, [
			'id' => Schema::TYPE_PK,
			'unique_key' => Schema::TYPE_TEXT . ' NULL DEFAULT NULL UNIQUE',
			'message_data' => Schema::TYPE_TEXT . ' NOT NULL',
			'attempts' => Schema::TYPE_SMALLINT . ' NOT NULL DEFAULT 0',
			'send_at' => Schema::TYPE_DATETIME . ' NOT NULL DEFAULT now()',
		], $tableOptions);

        $rawTableName = static::getDb()->getSchema()->getRawTableName($tableName);
        $path = explode('.', $rawTableName);
        $indexName = 'idx_' . end($path) . '_send_at';
        $this->createIndex($indexName, $tableName, ['send_at']);
    }

    public function down()
    {
        $tableName = $this->db->schema->getRawTableName(\Yii::$app->getMailer()->table);
        $this->dropTable($tableName);
    }
}
