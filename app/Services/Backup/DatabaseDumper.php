<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseDumper
{
    public function dump(string $connection): string
    {
        $driver = DB::connection($connection)->getDriverName();
        $sql = $this->disableForeignKeys($driver);

        foreach ($this->tables($connection) as $table) {
            $sql .= $this->dumpTable($connection, $table);
        }

        $sql .= $this->enableForeignKeys($driver);

        return $sql;
    }

    public function restore(string $connection, string $sql): void
    {
        $driver = DB::connection($connection)->getDriverName();

        Schema::connection($connection)->withoutForeignKeyConstraints(function () use ($connection, $sql) {
            foreach ($this->splitStatements($sql) as $statement) {
                if ($this->isForeignKeyPragma($statement)) {
                    continue;
                }

                DB::connection($connection)->unprepared($statement);
            }
        });

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::connection($connection)->unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @return list<string>
     */
    protected function tables(string $connection): array
    {
        $builder = Schema::connection($connection);
        $schema = $builder->getCurrentSchemaName();
        $tables = $builder->getTableListing($schema, schemaQualified: false);

        return array_values(array_filter($tables, function (string $table) use ($builder) {
            $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

            if (str_starts_with($name, 'sqlite_')) {
                return false;
            }

            return $builder->hasTable($name);
        }));
    }

    /**
     * @return list<string>
     */
    protected function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($inString) {
                if ($char === $quote) {
                    $doubled = $i + 1 < $length && $sql[$i + 1] === $quote;
                    if ($doubled) {
                        $buffer .= $sql[++$i];

                        continue;
                    }

                    $inString = false;
                    $quote = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $quote = $char;

                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer, " \t\n\r\0\x0B;");
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    protected function isForeignKeyPragma(string $statement): bool
    {
        $normalized = strtoupper(trim($statement));

        return str_starts_with($normalized, 'PRAGMA FOREIGN_KEYS')
            || str_starts_with($normalized, 'SET FOREIGN_KEY_CHECKS')
            || str_starts_with($normalized, 'SET SESSION_REPLICATION_ROLE');
    }

    protected function dumpTable(string $connection, string $table): string
    {
        if (! Schema::connection($connection)->hasTable($table)) {
            return '';
        }

        $wrapped = $this->wrapTable($connection, $table);
        $sql = "DELETE FROM {$wrapped};\n";

        $rows = DB::connection($connection)->table($table)->get();

        if ($rows->isEmpty()) {
            return $sql;
        }

        $columns = array_keys((array) $rows->first());
        $wrappedCols = implode(', ', array_map(
            fn (string $column) => $this->wrap($connection, $column),
            $columns
        ));

        foreach ($rows->chunk(100) as $chunk) {
            $values = $chunk->map(function ($row) use ($connection, $columns) {
                $row = (array) $row;
                $quoted = array_map(
                    fn (string $column) => $this->quote($connection, $row[$column] ?? null),
                    $columns
                );

                return '('.implode(', ', $quoted).')';
            })->implode(",\n");

            $sql .= "INSERT INTO {$wrapped} ({$wrappedCols}) VALUES\n{$values};\n";
        }

        return $sql;
    }

    protected function quote(string $connection, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return DB::connection($connection)->getPdo()->quote((string) $value);
    }

    protected function wrap(string $connection, string $value): string
    {
        return DB::connection($connection)->getQueryGrammar()->wrap($value);
    }

    protected function wrapTable(string $connection, string $table): string
    {
        return DB::connection($connection)->getQueryGrammar()->wrapTable($table);
    }

    protected function disableForeignKeys(string $driver): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => "SET FOREIGN_KEY_CHECKS=0;\n",
            'pgsql' => "SET session_replication_role = replica;\n",
            default => "PRAGMA foreign_keys=OFF;\n",
        };
    }

    protected function enableForeignKeys(string $driver): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => "SET FOREIGN_KEY_CHECKS=1;\n",
            'pgsql' => "SET session_replication_role = DEFAULT;\n",
            default => "PRAGMA foreign_keys=ON;\n",
        };
    }
}
