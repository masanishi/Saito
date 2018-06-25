<?php
use Migrations\AbstractMigration;

class Saitox5x0x0 extends AbstractMigration
{

    public function up()
    {

        $this->table('entries')
            ->changeColumn('locked', 'boolean', [
                'default' => '0',
                'null' => true,
            ])
            ->changeColumn('fixed', 'boolean', [
                'default' => '0',
                'null' => true,
            ])
            ->update();

        $this->table('categories')
            ->addColumn('accession_new_thread', 'integer', [
                'after' => 'accession',
                'default' => '2',
                'length' => 4,
                'null' => false,
            ])
            ->addColumn('accession_new_posting', 'integer', [
                'after' => 'accession_new_thread',
                'default' => '2',
                'length' => 4,
                'null' => true,
            ])
            ->update();

        $this->table('users')
            ->addColumn('avatar', 'string', [
                'after' => 'ignore_count',
                'default' => null,
                'length' => 255,
                'null' => true,
            ])
            ->addColumn('avatar_dir', 'string', [
                'after' => 'avatar',
                'default' => null,
                'length' => 255,
                'null' => true,
            ])
            ->update();
    }

    public function down()
    {

        $this->table('categories')
            ->removeColumn('accession_new_thread')
            ->removeColumn('accession_new_posting')
            ->update();

        /* Was MySQL TINYINT(4), this would change to INT. Not desired.
        $this->table('entries')
            ->changeColumn('locked', 'integer', [
                'default' => '0',
                'length' => 4,
                'null' => true,
            ])
            ->changeColumn('fixed', 'integer', [
                'default' => '0',
                'length' => 4,
                'null' => true,
            ])
            ->update();
        */

        $this->table('users')
            ->removeColumn('avatar')
            ->removeColumn('avatar_dir')
            ->update();
    }
}
