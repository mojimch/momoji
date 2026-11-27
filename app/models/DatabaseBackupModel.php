<?php

class DatabaseBackupModel extends Model
{
    public function writeSqlDump($output): void
    {
        if (!is_resource($output)) {
            throw new InvalidArgumentException('Tujuan output backup tidak valid.');
        }
        $tables = $this->tables();
        $orderedTables = $this->dependencyOrder($tables);
        $writeLine = static function (string $line) use ($output): void {
            if (fwrite($output, $line . "\n") === false) {
                throw new RuntimeException('Data backup tidak dapat ditulis.');
            }
        };

        $writeLine('-- Momoji database backup');
        $writeLine('-- Generated at ' . gmdate('Y-m-d H:i:s') . ' UTC');
        $writeLine('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";');
        $writeLine('SET time_zone = "+00:00";');
        $writeLine('SET FOREIGN_KEY_CHECKS = 0;');
        $writeLine('');
        foreach (array_reverse($orderedTables) as $table) {
            $writeLine('DROP TABLE IF EXISTS ' . $this->quoteIdentifier($table) . ';');
        }
        $writeLine('');

        foreach ($orderedTables as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $create = $this->db->query('SHOW CREATE TABLE ' . $quotedTable)->fetch(PDO::FETCH_NUM);
            if (!isset($create[1])) {
                throw new RuntimeException('Struktur tabel tidak dapat dibaca untuk backup.');
            }
            $writeLine($create[1] . ';');

            $columns = $this->columns($table);
            if ($columns === []) {
                continue;
            }
            $columnSql = implode(', ', array_map(
                fn(string $column): string => $this->quoteIdentifier($column),
                $columns
            ));
            $rows = $this->db->query(
                'SELECT ' . $columnSql . ' FROM ' . $quotedTable,
                PDO::FETCH_ASSOC
            );
            while (($row = $rows->fetch()) !== false) {
                $values = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    $values[] = $value === null ? 'NULL' : $this->quoteValue($value);
                }
                $writeLine(
                    'INSERT INTO ' . $quotedTable . ' (' . $columnSql . ') VALUES ('
                    . implode(', ', $values) . ');'
                );
            }
            $writeLine('');
        }
        $writeLine('SET FOREIGN_KEY_CHECKS = 1;');
    }

    private function tables(): array
    {
        $statement = $this->db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = [];
        foreach ($statement->fetchAll(PDO::FETCH_NUM) as $row) {
            $tables[] = (string) $row[0];
        }
        return $tables;
    }

    private function dependencyOrder(array $tables): array
    {
        if ($tables === []) {
            return [];
        }
        $dependencies = array_fill_keys($tables, []);
        $statement = $this->db->prepare(
            'SELECT TABLE_NAME, REFERENCED_TABLE_NAME
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = :schema AND REFERENCED_TABLE_NAME IS NOT NULL'
        );
        $statement->execute(['schema' => DB_NAME]);
        foreach ($statement->fetchAll() as $row) {
            $table = (string) $row['TABLE_NAME'];
            $parent = (string) $row['REFERENCED_TABLE_NAME'];
            if (isset($dependencies[$table], $dependencies[$parent])) {
                $dependencies[$table][$parent] = true;
            }
        }

        $ordered = [];
        while (count($ordered) < count($tables)) {
            $ready = [];
            foreach ($dependencies as $table => $parents) {
                if ($parents === [] && !in_array($table, $ordered, true)) {
                    $ready[] = $table;
                }
            }
            if ($ready === []) {
                throw new RuntimeException('Relasi tabel siklik mencegah penyusunan backup yang aman.');
            }
            foreach ($ready as $table) {
                $ordered[] = $table;
                unset($dependencies[$table]);
            }
            foreach ($dependencies as &$parents) {
                foreach ($ready as $table) {
                    unset($parents[$table]);
                }
            }
            unset($parents);
        }
        return $ordered;
    }

    private function columns(string $table): array
    {
        $statement = $this->db->query('SHOW COLUMNS FROM ' . $this->quoteIdentifier($table));
        $columns = [];
        foreach ($statement->fetchAll() as $column) {
            if (str_contains(strtoupper((string) $column['Extra']), 'GENERATED')) {
                continue;
            }
            $columns[] = (string) $column['Field'];
        }
        return $columns;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(mixed $value): string
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }
        if (!is_string($value)) {
            $value = (string) $value;
        }
        $quoted = $this->db->quote($value);
        if ($quoted === false) {
            throw new RuntimeException('Nilai database tidak dapat dikodekan untuk backup.');
        }
        return $quoted;
    }
}
