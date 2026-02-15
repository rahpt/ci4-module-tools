<?php

namespace App\Modules\__Module__\Database\Seeds;

use CodeIgniter\Database\Seeder;

class __Module__Seeder extends Seeder
{
    public function run()
    {
        $data = __Data__;
        $fields = __Fields__;

        $db = \Config\Database::connect();
        $builder = $db->table('__TableName__');

        foreach ($data as $row) {
            $insert = array_combine($fields, $row);
            $builder->insert($insert);
        }
    }
}
