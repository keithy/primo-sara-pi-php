<?php

use Phinx\Migration\AbstractMigration;

class CreateTableTests extends AbstractMigration
{

    public function change()
    {
        $table = $this->table('tests');

        $table
                ->addColumn('name', 'string', ['limit' => 41, 'null' => false])
                ->addColumn('email', 'string', ['limit' => 50, 'null' => false])
        ;
 
        $table->create();
    }
}
