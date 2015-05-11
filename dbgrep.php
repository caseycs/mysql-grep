#!/usr/bin/env php
<?php
/**
 * Search&replace all tables of the database for occurrences, like grep but for database
 * 
 * Usage example:
 * 
 * Dry run:
 * php dbgrep.php --username=root --password=banaan123 --database=project --search=wiredstuff
 * ```
 * 
 * Replace:
 * php dbgrep.php --username=root --password=banaan123 --search=wiredstuff --database=project  --replace=coolstuff
 * 
 * According to columns collation search maybe case-insensetive, BUT replace is every time case-sensetime. 
 */

$options = parseArguments();
$pdo = connect($options);
search($pdo, $options['database'], $options['search'], isset($options['replace']) ? $options['replace'] : false);

function search(PDO $pdo, $database, $search, $replace = null)
{
    //tables
    $sql = 'SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = :TABLE_SCHEMA';
    $sth = $pdo->prepare($sql);
    $sth->execute(array(':TABLE_SCHEMA' => $database));

    $total = 0;

    foreach ($sth->fetchAll() as $rowTable) {
        echo "table {$rowTable['TABLE_NAME']} ..." . PHP_EOL;

        //columns
        $sql1 = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :TABLE_SCHEMA AND TABLE_NAME = :TABLE_NAME';
        $sth1 = $pdo->prepare($sql1);
        $sth1->execute(array(':TABLE_SCHEMA' => $database, ':TABLE_NAME' => $rowTable['TABLE_NAME']));

        $sql2 = "SELECT * FROM {$database}.{$rowTable['TABLE_NAME']} WHERE ";
        $tableWhere = array();
        foreach ($sth1->fetchAll() as $rowColumn) {
            $tableWhere[] = "`{$rowColumn['COLUMN_NAME']}` LIKE " . $pdo->quote('%' . $search . '%') . "";
        }
        $sql2 .= join(' OR ', $tableWhere);
        //    echo $sql2 . PHP_EOL;

        if (false !== $replace) {
            $sqlUpdate = "UPDATE {$database}.{$rowTable['TABLE_NAME']} SET ";
            $sqlUpdateExecute = false;
        }

        //result
        $sth2 = $pdo->query($sql2);
        $columnsFound = array();
        while ($rowTableRow = $sth2->fetch(PDO::FETCH_ASSOC)) {
            $total++;
            echo "found row in table {$rowTable['TABLE_NAME']}" . PHP_EOL;
            foreach ($rowTableRow as $k => $v) {
                echo "\t {$k} : {$v}" . PHP_EOL;

                if (false !== $replace) {
                    $sqlUpdate .= "{$k} = REPLACE({$k}, {$pdo->quote($search)}, {$pdo->quote($replace)}), ";
                    $sqlUpdateExecute = true;
                }
            }
        }

        if (false !== $replace && $sqlUpdateExecute) {
            echo "updating table {$rowTable['TABLE_NAME']}" . PHP_EOL;
            $sqlUpdate = substr($sqlUpdate, 0, -2);
            $sqlUpdate .= ' WHERE ' . join('OR', $tableWhere);
            //        echo $sqlUpdate . PHP_EOL;
            try {
                $pdo->exec($sqlUpdate);
            } catch (\Exception $e) {
                echo "FAIL {$sqlUpdate}" . PHP_EOL;
            }
        }
    }

    echo "Total found: " . $total . PHP_EOL;
}

//parse cli options
function parseArguments()
{
    $optionsRequired = array('host', 'port', 'username', 'password', 'database', 'search');
    $optionsOptional = array('replace');
    $optionsDefault = array('host' => 'localhost', 'port' => 3306);

    $longopts = array();
    foreach ($optionsRequired as $o) {
        $longopts[] = "{$o}:";
    }
    foreach ($optionsOptional as $o) {
        $longopts[] = "{$o}:";
    }

    $options = getopt('', $longopts);
    $optionsMissing = array();
    foreach ($optionsRequired as $option) {
        //force default value
        if ((!isset($options[$option]) || !$options[$option]) && isset($optionsDefault[$option])) {
            $options[$option] = $optionsDefault[$option];
        }

        //finally verify
        if (!isset($options[$option]) || !$options[$option]) {
            $optionsMissing[] = $option;
        }
    }

    if (array() !== $optionsMissing) {
        echo "Required options not presented. Please specify --" . join(', --', $optionsMissing) . PHP_EOL;
        exit;
    }

    return $options;
}

function connect(array $options)
{
    $dsn = "mysql:host={$options['host']};port={$options['port']}";
    $pdo = new PDO($dsn, $options['username'], $options['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
