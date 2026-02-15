<?php

namespace App\Modules\__ModuleName__\Database\Migrations;

use CodeIgniter\Database\Migration;

class Create__ModuleName__Table extends Migration
{
    public function up()
    {
        $this->forge->addField(__Fields__);
__PrimaryKeys__
        $this->forge->createTable('__TableName__', true);
    }

    public function down()
    {
        $this->forge->dropTable('__TableName__', true);
    }
}
