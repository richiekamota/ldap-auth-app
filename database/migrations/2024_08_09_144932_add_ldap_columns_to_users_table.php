<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLdapColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     * 
     * @return void
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('users', function (Blueprint $table) use ($driver) {
            // Check if the 'guid' column does not already exist
            if (!Schema::hasColumn('users', 'guid')) {
                $table->string('guid')->nullable();

                if ($driver !== 'sqlsrv') {
                    $table->unique('guid');
                }
            }
        });

        if ($driver === 'sqlsrv' && !DB::selectOne("SELECT * FROM sys.indexes WHERE name = 'users_guid_unique'")) {
            DB::statement(
                $this->compileUniqueSqlServerIndexStatement('users', 'guid')
            );
        }
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if the 'guid' column exists before attempting to drop it
            if (Schema::hasColumn('users', 'guid')) {
                $table->dropColumn('guid');
            }
        });
    }

    /**
     * Compile a compatible "unique" SQL Server index constraint.
     * 
     * @param string $table
     * @param string $column 
     * 
     * @return string 
     */
    protected function compileUniqueSqlServerIndexStatement($table, $column)
    {
        return sprintf('create unique index %s on %s (%s) where %s is not null',
            implode('_', [$table, $column, 'unique']),
            $table,
            $column,
            $column
        );
    }
}
