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
     * @var Columns
     */
    private $columns;

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
        $this->columns = new Columns($dbconn, $table);
        $this->object = (object) $this->columns->getColumns();

        // Load object if id was given
        if ($id > 0) {
            $this->byID($id);
        }
    }

    /**
     * Get primary key column name.
     *
     * @return string
     */
    public function getPrimaryKeyColumn() : string
    {
        return $this->columns->getPrimaryKeyColumn();
    }

    /**
     * @return array
     */
    private function getColumns() : array
    {
        return $this->columns->getColumns();
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
