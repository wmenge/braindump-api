<?php

use Phinx\Migration\AbstractMigration;

class AddTimestamps extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     */
    public function change()
    {
        $noteTable = $this->table('note');
        $noteTable->addForeignKey('notebook_id', 'notebook', 'id', [ 'delete'=> 'SET_NULL', 'update' => 'NO_ACTION' ])
                  ->update();

        $notebookTable = $this->table('notebook');
        $notebookTable->addColumn('created', 'datetime')
                      ->addColumn('updated', 'datetime')
                      ->update();
    }
}