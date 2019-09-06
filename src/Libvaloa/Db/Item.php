<?php

/**
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.io>.
 *
 * Portions created by the Initial Developer are
 * Copyright (C) 2010,2019 Tarmo Alexander Sundström <ta@sundstrom.io>
 *
 * All Rights Reserved.
 *
 * Contributor(s):
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
namespace Libvaloa\Db;

use stdClass;
use OutOfBoundsException;
use DBException;

/**
 * Class Object
 * @package Libvaloa\Db
 */
class Item
{
    /**
     * Database connection
     *
     * @var \Libvaloa\Db\Db
     */
    private $db;

    /**
     * Target database table
     *
     * @var string
     */
    private $table;

    /**
     * Target primary key field in table
     *
     * @var string
     */
    private $primaryKey = 'id';

    /**
     * Data object for table row
     *
     * @var stdClass
     */
    private $object;

    /**
     * Track if anything was modified
     *
     * @var bool
     */
    private $modified = false;

    /**
     * Columns in table row
     *
     * @var bool
     */
    private $columns = false;

    /**
     * Constructor - give the name of the target table.
     *
     * @param Db $dbconn
     * @param string $table
     * @param int $id
     * @throws \Libvaloa\Db\DBException
     */
    public function __construct(\Libvaloa\Db\Db $dbconn, string $table, int $id = -1)
    {
        // Target table:
        $this->table = $table;

        // Item object
        $this->object = new stdClass();

        // Database connection
        $this->db = $dbconn;

        // Get and set columns from target table
        $this->setColumns($this->getColumns());

        // Load object if id was given
        if ($id > 0) {
            $this->byID($id);
        }
    }

    /**
     * Set primary key field, defaults to id.
     *
     * @param string $key Primary key field
     */
    public function setPrimaryKeyColumn(string $key)
    {
        $this->primaryKey = $key;
    }

    /**
     * Get primary key column name.
     *
     * @return string
     */
    public function getPrimaryKeyColumn() : string
    {
        return $this->primaryKey;
    }

    /**
     * Set columns in target table. Only names set in $columns array will
     * be included in the query.
     *
     * @param array $columns Table columns as array
     */
    public function setColumns(array $columns)
    {
        $this->object = (object) $columns;
    }

    /**
     * Returns list of table columns as array.
     *
     * @return array
     * @throws \Libvaloa\Db\DBException
     */
    private function getColumns() : array
    {
        // Detect columns
        switch ($this->db->properties['db_server']) {
            default:
                // MySQL / MariaDB:
                $query = '
                    SELECT column_name, data_type, column_key
                    FROM information_schema.columns
                    WHERE table_name = ?
                    AND table_schema = ?';

                $stmt = $this->db->prepare($query);
                $stmt->set($this->table);
                $stmt->set($this->db->properties['db_db']);
                $stmt->execute();

                foreach ($stmt as $row) {
                    $columns[$row->column_name] = null;

                    // Set primary key if found
                    if ($row->column_key == 'PRI') {
                        $this->setPrimaryKeyColumn($row->column_name);
                    }
                }

                if (isset($columns)) {
                    return $columns;
                } else {
                    return [];
                }
            break;
        }
    }

    /**
     * Get field value from row object
     *
     * @param $field
     * @return null|string
     */
    public function __get($field)
    {
        if ($field == 'primaryKey') {
            return $this->primaryKey;
        }

        return isset($this->object->$field) ? $this->object->$field : null;
    }

    /**
     * Set field value to row object.
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        if ($key == $this->primaryKey) {
            return;
        }

        foreach ($this->object as $_tmpk => $_tmpv) {
            if ($_tmpk == $key) {
                if ($value !== $_tmpv) {
                    $this->object->{$key} = $value;
                    $this->modified = true;
                }
            }
        }
    }

    /**
     * Load database row by id.
     *
     * @param int $id
     * @return int
     * @throws \Libvaloa\Db\DBException
     */
    public function byID(int $id) : int
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE {$this->primaryKey} = ?");

        $stmt->set($id);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row === false) {
            throw new OutOfBoundsException('Selected row does not exist.');
        }

        $this->object = $row;

        return $id;
    }

    /**
     * Save row
     */
    public function save() : int
    {
        if ($this->modified === false) {
            return -1;
        }

        if (!isset($this->object->{$this->primaryKey})) {
            $this->object->{$this->primaryKey} = null;
        }

        $fields = array();
        foreach ($this->object as $key => $val) {
            $fields[$key] = '?';
        }

        if (!is_numeric($this->object->{$this->primaryKey})) {
            $query = "
                INSERT INTO {$this->table} (`".implode('`,`', array_keys($fields)).'`)
                VALUES ('.implode(',', $fields).')';

            if ($this->db->properties['db_server'] === 'postgres') {
                $query .= " RETURNING {$this->primaryKey}";
            }
        } else {
            $query = "
                UPDATE {$this->table}
                SET `".implode('` = ?,`', array_keys($fields))."` = ?
                WHERE {$this->primaryKey} = ?";
        }

        unset($fields);
        $stmt = $this->db->prepare($query);

        foreach ($this->object as $val) {
            $stmt->set($val);
        }

        if (is_numeric($this->object->{$this->primaryKey})) {
            $stmt->set((int) $this->object->{$this->primaryKey});
        }

        $stmt->execute();

        if (!is_numeric($this->object->{$this->primaryKey})) {
            $this->object->{$this->primaryKey} = (int) $this->db->properties['db_server'] === 'postgres' ? $stmt->fetchColumn() : $this->db->lastInsertID();
        }

        return $this->object->{$this->primaryKey};
    }

    /**
     * Delete row.
     */
    public function delete()
    {
        if (!isset($this->object->{$this->primaryKey})
            || !is_numeric($this->object->{$this->primaryKey})) {
            return;
        }

        $query = "
            DELETE FROM {$this->table}
            WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($query);
        $stmt->set((int) $this->object->{$this->primaryKey});
        $stmt->execute();
    }
}
