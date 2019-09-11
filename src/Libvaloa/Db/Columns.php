<?php

/**
 * The Initial Developer of the Original Code is
 * Tarmo Alexander Sundström <ta@sundstrom.io>.
 *
 * Portions created by the Initial Developer are
 * Copyright (C) 2019 Tarmo Alexander Sundström <ta@sundstrom.io>
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
 * Class Columns
 * @package Libvaloa\Columns
 */
class Columns
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
    public function __construct(\Libvaloa\Db\Db $dbconn, string $table)
    {
        // Target table:
        $this->table = $table;

        // Database connection
        $this->db = $dbconn;

        // Get and set columns from target table
        $this->setColumns($this->_getColumns());
    }

    /**
     * @param string $key
     * @return string
     */
    private function setPrimaryKeyColumn(string $key)
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
    private function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function getColumns() : array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    private function _getColumns() : array
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
}
