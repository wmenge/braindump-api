<?php

use Phinx\Migration\AbstractMigration;

class InitialMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     */
    public function change()
    {
        $notebookTable = $this->table('notebook');
        $notebookTable->addColumn('title', 'string')
              ->create();

        $noteTable = $this->table('note');
        $noteTable->addColumn('notebook_id', 'integer')
              ->addColumn('title', 'string')
              ->addColumn('created', 'datetime')
              ->addColumn('updated', 'datetime')
              ->addColumn('url', 'string')
              ->addColumn('type', 'string')
              ->addColumn('content', 'string')
              //->addForeignKey('notebook_id', 'notebook', 'id', [ 'delete'=> 'SET_NULL', 'update' => 'NO_ACTION' ])
              ->create();
    }
}
