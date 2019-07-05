<?php

//use UseDatabase\NoData;

namespace Persistence {

    class Sql extends \DCI\Context
    {
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
        protected $table;
        protected $primaryKey;
        protected $fields;

        // ROLES ALLOCATION
        function useAsQuery($model)
        {
            return $model->addRole('Model_AsQuery', $this);
        }

        function useAsNew($model)
        {
            return $model->addRole('Model_NotYetPersisted', $this);
        }

        function useAsExisting($model)
        {
            return $model->addRole('Model_Persisted', $this)->remember();
        }

        function newQuery($aClass = '\Actor\StdClass')
        {
            $model = new $aClass();
            return $model->addRole('Model_AsQuery', $this);
        }

        // Construct
        function __construct($pdo, $optionalInfoCache = null)
        {
            $this->pdo = $pdo;
            $this->infoCache = $optionalInfoCache ?? $this; // optional
        }

        function withTable($table, $primaryKey = null, $fields = '*')
        {
            $clone = clone $this;
            $clone->table = $table;
            $clone->primaryKey = $primaryKey;

            if ($primaryKey && $fields !== '*') $fields = "{$primaryKey},{$fields}";
            $clone->fields = $fields;

            return $clone;
        }

        function withFields($fieldsStr)
        {
            $clone = clone $this;
            $clone->fields = $fieldsStr;
            return $clone;
        }

        function tableFor($modelClass)
        {
            return $this->table ?? $this->table = strtolower($modelClass::singular);
        }

        function primaryKeyFor($modelClass)
        {
            return $this->primaryKey ?? $this->primaryKey = $this->tableFor($modelClass) . '_id';
        }

        function fetchAll($modelClass = '\Actor\StdClass', $fields = null)
        {
            return $this->dbSelectAll($modelClass, $fields);
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

        function dbSelectAll($modelClass, $fields = null)
        {
            $table = $this->tableFor($modelClass);

            $fields = $fields ?? $this->fields;

            $stmt = $this->pdo->run("SELECT {$fields} FROM `{$table}`");

            return $stmt->asObjects($modelClass, $this)->fetchAll();
        }

        // // Versatile "WHERE IS" query matching multiple column => values.
        // returns array of objects found (or empty array)
        function dbSelectWhereIs($modelClass, $dict, $fields = null)
        {
            $table = $this->tableFor($modelClass);

            $fields ?? $fields = $this->fields;

            $is = "(`" . implode('` IS ?) OR (`', array_keys($dict)) . '` IS ?)';

            $stmt = $this->pdo->run("SELECT {$fields} FROM `{$table}` WHERE {$is}", array_values($dict));

            return $stmt->asObjects($modelClass, $this)->fetchAll();
        }

        // Versatile "DELETE WHERE IS" query matching multiple column => values.
        // returns array of objects found (or empty array)
        function dbDeleteWhereIs($modelClass, $dict)
        {
            $table = $this->tableFor($modelClass);

            $is = "(`" . implode('` IS ?) OR (`', array_keys($dict)) . '` IS ?)';

            return $this->pdo->run("DELETE FROM `{$table}` WHERE {$is}", array_values($dict));
        }

        function dbSelectIds($modelClass, $ids, $fields = null)
        {
            $table = $this->tableFor($modelClass);
            $key = $this->primaryKeyFor($modelClass);

            $fields ?? $fields = $this->fields;

            $in = str_repeat('?,', count($ids) - 1);
            $stmt = $this->pdo->run("SELECT {$fields} FROM `{$table}` WHERE `{$key}` IN  ({$in}?)", $ids);

            return $stmt->asObjects($modelClass, $this)->fetchAll();
        }

        // Versatile "INSERT INTO" column => values.
        // returns array of objects found (or empty array)
        function dbInsertInto($modelClass, $dict)
        {
            $table = $this->tableFor($modelClass);

            $into = "(`" . implode('`,`', array_keys($dict)) . '`)';
            $values = str_repeat('?,', count($dict) - 1);

            return $this->pdo->run("INSERT INTO `{$table}`{$into} VALUES ({$values}?)", array_values($dict));
        }

        function dbUpdate($modelClass, $dict, $prevDict = [])
        {
            $table = $this->tableFor($modelClass);
            $key = $this->primaryKeyFor($modelClass);

            $is = "`" . $key . "` IS '" . $dict[$key] . "'";

            $update = array_diff_assoc($dict, $prevDict);

            if ($update[$key] ?? false) unset($update[$key]);

            $into = "`" . implode('` = ?, `', array_keys($update)) . '` = ?';
            //$values = str_repeat('?,', count($update) - 1);

            $this->pdo->run("UPDATE `{$table}` SET {$into} WHERE {$is}", array_values($update));

            return true;
        }

        function CONCAT($list)
        {
            return $this->pdo->helper->CONCAT($list);
        }

        function limitFor($table)
        {
            $limits = $this->configAt('query_limits') ?? [];
            return $limits[$table] ?? $limits['DEFAULT'] ?? 10;
        }

        function columnsOf($modelClass)
        {
            $table = $this->tableFor($modelClass);

            return $this->infoCache->at("{$table}_columns",
                            function( ) use ($table) {
                        return $this->pdo->columnsOfTable($table);
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
    trait AllModels
    {

        function myClass()
        {
            return get_class($this->getDataObject());
        }
    }

    trait Model_AsQuery
    {

        // Given a partial object with only a few key properties filled in
        // query the database
        function fetch($fields = null)
        {
            $modelClass = get_class($this->getDataObject());

            $lookup = $this->toArray();

            return $this->context->dbSelectWhereIs($modelClass, $lookup, $fields);
        }

        function fetchOne($fields = null)
        {
            $found = $this->fetch($fields);

            return empty($found) ? null : $found[0];
        }

        function fetchAll($fields = null)
        {
            $model = $this->getDataObject();
            $modelClass = get_class($model);

            return $this->context->dbSelectAll($modelClass, $fields);
        }

        function fetchIds($ids, $fields = null)
        {
            $model = $this->getDataObject();
            $modelClass = get_class($model);

            return $this->context->dbSelectIds($modelClass, $ids, $fields);
        }

        function delete()
        {
            $model = $this->getDataObject();
            $modelClass = get_class($model);

            $lookup = $this->toArray();

            return $this->context->dbDeleteWhereIs($modelClass, $lookup);
        }
    }

    trait Model_NotYetPersisted
    {

        function create()
        {
            $create = $this->toArray();

            return $this->context->dbInsertInto($this->modelClass(), $create);
        }

        function save()
        {
            return $this->create();
        }
    }

    trait Model_Persisted
    {
        protected $remembered;

        function remember()
        {
            $this->remembered = $this->toArray();
        }

        function update()
        {
            $update = $this->toArray();

            return $this->context->dbUpdate($this->modelClass(), $update, $this->remembered);
        }

        function delete()
        {
            $model = $this->getDataObject();
            $key = $this->context->primaryKeyFor($this->modelClass());
            $lookup = [$key => $model[$key]];

            return $this->context->dbDeleteWhereIs($this->modelClass(), $lookup);
        }

        function save()
        {
            return $this->update();
        }
    }

}
