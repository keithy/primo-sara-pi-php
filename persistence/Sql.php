<?php

//use UseDatabase\NoData;
// This context represents a single table in an Sql database
// The model itself, should never need knowledge of where it
// was or will be persisted.
// The dbContext will be fully initialized where it is used.

namespace Persistence {

    class Sql extends \DCI\Context
    {
        const baseModelClass = '\Stage\Actors\Model';

        // $model
        // 
        // A) an instance representing a data object (to be inserted or updated to the database)
        // or
        // B) a partial instance with the defining characteristics needed to retrieve the full object
        //    from the database
        // $controller
        // 
        // Our connection to the big wide world, who is asking 

        protected $pdo;
        protected $infoCache; // a persisted cache
        public $modelClass;
        public $table;
        public $key;
        public $fields;
        public $marshall;

        // Construct context - allocating a PDO connection
        function __construct($pdo, $optionalInfoCache = null)
        {
            $this->pdo = $pdo;
            $this->infoCache = $optionalInfoCache ?? $this; // optional
        }

        // Context definition - allocating a modelClass, table, primary key, and fields subset
        function withTableEtc($modelClass, $table, $key = null, $fields = '*')
        {
            $clone = clone $this;
            $clone->marshall = $this;

            $clone->modelClass = $modelClass;
            $clone->table = $table;
            $clone->key = $key;

            if ($key && $fields !== '*') $fields = "{$key},{$fields}";
            $clone->fields = $fields;

            return $clone;
        }

        function asString()
        {
            return $this->pdo->logs->tag . '.' . $this->table;
        }

        // ROLES ALLOCATION
        // a query is a skeleton instance used for finding instances based upon a match
        function useAsQuery($model)
        {
            return $model->addRole('Model_AsQuery', $this);
        }

        function newModel($id, $model = null)
        {
            $modelClass = $this->modelClass;
            $model ?? $model = new $modelClass();
            $model->{$this->key} = $id;

            return $model->addRole('Model_NotYetPersisted', $this);
        }

        function useAs($model, $id = null)
        {
            $modelClass = $this->modelClass;
            $model ?? $model = new $modelClass();
            if ($id) $model->{$this->key} = $id;

            return $model->addRole('Model_Persisted', $this);
        }

        function newQuery($modelClass = null)
        {
            $modelClass ?? $modelClass = $this->modelClass;
            return $this->useAsQuery(new $modelClass());
        }

        // a Finder is a structure for finding data items of $modelClass based upon a search/regex
        function newFinder($finderClass = null)
        {
            $finderClass = $finderClass ?? static::baseModelClass;
            $finder = (new $finderClass())->addRole('Model_AsFinder', $this);
            return $finder;
        }

        // utils
        // 
        // returns array of objects found (or empty array) 
//        function dbWhereIn($modelClass, $matchColumn, $array, $fields = '*')
//        {
//            $table = $this->tableFor($modelClass);
//
//            $fields = implode(',', $fields);
//
//            $in = str_repeat('?,', count($array) - 1);
//
//            return $this->pdo->run("SELECT {$fields} FROM `{$table}` WHERE `{$matchColumn}` IN  ({$in}?)", $array)->fetchAllAsObjects($modelClass, [$this]);
//        }

        function run(...$args)
        {
            return $this->pdo->run(...$args);
        }

        function selectAll($fields = null, $limit = 0, $offset = 0)
        {
            $fields ?? $fields = $this->fields;
            $limit = $limit ? " LIMIT {$offset},{$limit}" : '';

            return $this->run("SELECT {$fields} FROM `{$this->table}`{$limit}")
                            ->asObjects($this->modelClass, $this->marshall)
                            ->fetchAll();
        }

        // Versatile "WHERE IS" query matching multiple column => values.
        // returns array of objects found (or empty array)
        function selectWhereIs($lookup, $fields = null, $limit = 0, $offset = 0)
        {
            $fields ?? $fields = $this->fields;
            $limit = $limit ? " LIMIT {$offset},{$limit}" : '';

            $is = "(`" . implode('` IS ?) OR (`', array_keys($lookup)) . '` IS ?)';

            return $this->run("SELECT {$fields} FROM `{$this->table}` WHERE {$is}{$limit}", array_values($lookup))
                            ->asObjects($this->modelClass, $this->marshall)
                            ->fetchAll();
        }

        // Versatile "DELETE WHERE IS" query matching multiple column => values.
        // returns array of objects found (or empty array)
        function deleteWhereIs($lookup)
        {
            $is = "(`" . implode('` IS ?) OR (`', array_keys($lookup)) . '` IS ?)';
            return $this->run("DELETE FROM `{$this->table}` WHERE {$is}", array_values($lookup));
        }

        function deleteRow($id)
        {
            return $this->deleteWhereIs([$this->key => $id]);
        }

        function selectIds($ids, $fields = null)
        {
            $fields ?? $fields = $this->fields;
            
            $in = str_repeat('?,', count($ids) - 1);
            return $this->run("SELECT {$fields} FROM `{$this->table}` WHERE `{$this->key}` IN  ({$in}?)", $ids)
                            ->asObjects($this->modelClass, $this->marshall)
                            ->fetchAll();
        }

        // Versatile "INSERT INTO" column => values.
        // returns array of objects found (or empty array)
        // Note $dict keys MUST be validated against real columns to avoid injection vulnerability
        function insertRow($dict)
        {
            $keys = array_keys($dict);

            if (!empty(array_diff($keys, $this->columns())))
                    throw new \PDOException("Invalid column name: {$key}");

            $into = implode('`,`', $keys);
            $values = str_repeat('? ,', count($dict) - 1);

            return $this->run("INSERT INTO `{$this->table}` (`{$into}`) VALUES ({$values}?)", array_values($dict));
        }

        // Note $update keys MUST be validated against real columns to avoid injection vulnerability
        function updateRow($dict, $prevDict = [])
        {
            $key = $this->key;

            $is = "`" . $key . "` IS '" . $dict[$key] . "'";

            k_error_log(print_r($dict, true));
            k_error_log(print_r($prevDict, true));
            $update = array_diff_assoc($dict, $prevDict);
            k_error_log(print_r($update, true));

            if ($update[$key] ?? false) unset($update[$key]);

            if (empty($update)) return true;

            $keys = array_keys($update);

            if (!empty(array_diff($keys, $this->columns())))
                    throw new \PDOException("Invalid column name: {$key}");

            $into = "`" . implode('` = ?, `', $keys) . '` = ?';
            //$values = str_repeat('?,', count($update) - 1);

            return $this->run("UPDATE `{$this->table}` SET {$into} WHERE {$is}", array_values($update));
        }

        function CONCAT($list)
        {
            return $this->pdo->helper->CONCAT($list);
        }

        function limit()
        {
            $limits = $this->configAt('query_limits') ?? [];
            return $limits[$this->table] ?? $limits['DEFAULT'] ?? 10;
        }

        function columns()
        {
            return $this->infoCache->at("{$this->table}_columns",
                            function( ) {
                        return $this->pdo->columnsOfTable($this->table);
                    });
        }

        // fallback when no infoCache provider is supplied
        function at($k, $fnOrVal = null)
        {
            static $cache = [];
            if (isset($cache[$k])) return $cache[$k];

            return (is_callable($fnOrVal)) ? $cache[$k] = $fnOrVal($k) : $cache[$k] = $fnOrVal;
        }

        // output

        function dbResultsFrom($stmt, $modelClass, $noData = null)
        {
            if ($modelClass === 1) {
                $results = $stmt->fetchColumn();
            } else if ($modelClass !== null) {
                $results = $stmt->asObjects($modelClass, $this)->fetchAll();
            } else {
                $results = $modelClass->fetchAll();
            }

            if ($results === false) return $this->respondNoData($noData);

            return $results;
        }

        function stmtFind($search = [], $like = false, $regex = false, $ids = [], $idCol = '', $fields = null, $limit = 0, $offset = 0)
        {
            $sqlArgs = [];
            $where = "";
            $and = "";

            $limit = $limit ? " LIMIT {$offset},{$limit}" : '';

            if (false !== $like) {
                if ("" === $where) $where = "WHERE";
                $concat = $this->CONCAT(explode(',', $search));
                $where = "{$where}{$and} {$concat} LIKE ?";
                $sqlArgs[] = str_replace('*', '%', $like);
                $and = " AND";
            }

            // not working in sqlite yet
            if (false !== $regex) {
                if ("" === $where) $where = "WHERE";
                $concat = $this->CONCAT(explode(',', $search));
                $where = "{$where}{$and} {$concat} REGEX '{$regex}'";
                $sqlArgs[] = "{$regex}";
                $and = " AND";
            }


            if (!empty($ids)) {
                if ("" === $where) $where = "WHERE";

                $in = str_repeat('?,', count($ids) - 1);
                $where = "{$where}{$and} `{$idCol}` IN ({$in}?)";
                $sqlArgs = array_merge($sqlArgs, $ids);
                $and = " AND";
            }

            return $this->run("SELECT {$fields} FROM `{$this->table}` {$where}{$limit}", $sqlArgs);
        }
    }

}
/**
 * Roles are defined in a sub-namespace of the context as a workaround for the fact that
 * PHP doesn't support inner classes.
 *
 * We use the trait keyword to define roles because the trait keyword is native to PHP (PHP 5.4+).
 * In an ideal world it would be better to have a "role" keyword -- think of "trait" as just
 * our implementation technique for roles in PHP.
 * (This particular implmentation for PHP actually uses a separate class for the role behind the scenes,
 * but that programmer needn't be aware of that.)
 */

namespace Persistence\Sql\Roles {

    // an idea to test
    trait AllRoles
    {

        function dbGetId($id)
        {
            $key = $this->context->key;
            return $this->{$key};
        }

        function getClass()
        {
            return get_class($this->getDataObject());
        }
    }

    trait Model_AsQuery
    {
        use AllRoles;

        function dbSetId($id)
        {
            $key = $this->context->key;
            $this->{$key} = $id;
            return $this;
        }

        // Given a partial object with only a few key properties filled in
        // query the database
        function dbFetch($fields = null, $limit = 20, $offset = 0)
        {
            $lookup = $this->toArray();

            return $this->context->selectWhereIs($lookup, $fields, $limit, $offset);
        }

        function dbFetchOne($fields = null, $offset = 0)
        {
            $found = $this->dbFetch($fields, 1, $offset);

            return empty($found) ? null : $found[0];
        }

        function dbFetchAll($fields = null, $limit = 20, $offset = 0)
        {
            return $this->context->selectAll($fields, $limit, $offset);
        }

        function dbFetchIds($ids, $fields = null)
        {
            return $this->context->selectIds($ids, $fields);
        }

        function dbDelete()
        {
            $lookup = $this->toArray();

            $this->context->deleteWhereIs($lookup);

            return true;
        }
    }

    trait Model_AsFinder
    {

        //use AllRoles;

        function dbFetchAll($fields, $limit, $offset)
        {
            $stmt = $this->context->stmtFind($this->search, $this->like, $this->regex, $this->ids, $this->col, $fields, $limit, $offset);

            return $stmt->asObjects($this->context->modelClass, $this->context->marshall)->fetchAll();
        }

        function dbFetchCount()
        {
            $stmt = $this->context->stmtFind($this->search, $this->like, $this->regex, $this->ids, $this->col, 'COUNT(*)');

            return $stmt->fetchColumn();
        }
    }

    trait Model_NotYetPersisted
    {
        use AllRoles;

        function dbCreate()
        {
            $this->context->insertRow($this->toArray());

            return true;
        }

        function dbSave()
        {
            return $this->dbCreate();
        }
    }

    trait Model_Persisted
    {
        use AllRoles;
        protected $remembered = [];

        function dbRemember()
        {
            $this->remembered = $this->toArray();
            return $this;
        }

        function dbUpdate($prev = null)
        {
            $this->context->updateRow($this->toArray(), $prev ?? $this->remembered);

            return true;
        }

        function dbDelete()
        {
            $this->context->deleteRow($this->dbGetId());

            return true;
        }

        function dbSave()
        {
            return $this->dbUpdate();
        }
    }

}
