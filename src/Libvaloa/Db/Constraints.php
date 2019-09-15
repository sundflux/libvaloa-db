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

Use \Libvaloa\Debug\Debug;

/**
 * Class Constraints.
 *
 * Adds foreign keys automatically.
 *
 * @package Libvaloa\Db
 */
class Constraints
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
     * @var
     */
    private $columns;

    /**
     * @var array
     */
    private $constraints = [];

    /**
     * @var string
     */
    private $primaryKeyColumn = 'id';

    /**
     * Constraints constructor.
     * @param Db $dbconn
     * @param string $table
     * @throws DBException
     */
    public function __construct(\Libvaloa\Db\Db $db, string $table)
    {
        // Target table:
        $this->table = $table;

        // Database connection
        $this->db = $db;
    }

    /**
     * @param $column
     */
    public function setPrimaryKeyColumn(string $column)
    {
        $this->primaryKeyColumn = $column;
    }

    /**
     * @return array
     * @throws DBException
     */
    public function getConstraints() : array
    {
        $columns = new Columns($this->db, $this->table);
        $this->columns = $columns->getColumns();

        $lookup = "_{$this->primaryKeyColumn}";
        foreach ($this->columns as $column => $v) {
            if (strpos($column, $lookup) !== false) {
                $table = substr($column, 0, -3);
                $this->constraints[] = $table;
            }
        }

        return $this->constraints;
    }

    /**
     * @param array $constraints
     * @return int
     */
    public function createConstraints(array $constraints) : int
    {
        if (empty($constraints)) {
            return false;
        }

        $cnt = 0;
        foreach ($constraints as $cnst) {
            // Check if foreign key has already been set
            if ($this->checkColumnHasConstraint($cnst)) {
                Debug::__print('Foreign key already exists - skipping');

                continue;
            }

            // Skip internal references
            if ($cnst == 'parent') {
                continue;
            }

            // Add foreign key
            $query = "
                ALTER TABLE `{$this->table}`
                ADD FOREIGN KEY (`{$cnst}_{$this->primaryKeyColumn}`) 
                REFERENCES `{$cnst}` (`{$this->primaryKeyColumn}`)
                ON DELETE RESTRICT 
                ON UPDATE RESTRICT ";

            try {
                $stmt = $this->db->prepare($query);
                $stmt->execute();

                $cnt++;
            } catch(\Libvaloa\Db\DBException $e) {

            } catch(\Exception $e) {

            }
        }

        return $cnt;
    }

    /**
     * @param $column
     * @return bool
     */
    public function checkColumnHasConstraint($column) : bool
    {
        $query = "
            SELECT COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_COLUMN_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME = ?";

        $foundConstraint = false;

        $stmt = $this->db->prepare($query);
        $stmt->set($this->table);
        $stmt->set($column);
        $stmt->execute();
        foreach ($stmt as $k => $v) {
            if ($v->REFERENCED_TABLE_NAME == $column && $v->REFERENCED_COLUMN_NAME == $this->primaryKeyColumn
                && $v->COLUMN_NAME = "{$column}_{$this->primaryKeyColumn}") {
                $foundConstraint = true;
            }

        }

        return $foundConstraint;
    }

    /**
     * @return array
     */
    public function getReferences() : array
    {
        $query = "
            SELECT COLUMN_NAME, TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_NAME = ?";

        $stmt = $this->db->prepare($query);
        $stmt->set($this->table);
        $stmt->execute();

        $references = [];
        foreach ($stmt as $k => $v) {
            $references[] = $v->TABLE_NAME.'.'.$v->COLUMN_NAME;
        }

        return $references;
    }

}
