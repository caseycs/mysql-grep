<?php
$host = 'localhost';
$port = 3306;
$username = '';
$password = '';
$database = '';
$query = '';
$replace = false;

//parse cli options
$longopts  = array(
    "host:",    // Optional value
    "port:",    // Optional value
    "username:",    // Optional value
    "password:",    // Optional value
    "database:",    // Optional value
    "query:",    // Optional value
    "replace:",    // Optional value
);
$options = getopt('', $longopts);
//print_r($options);
//print_r($argv);

$database = $options['database'];

if (isset($options['host'])) {
    $host = $options['host'];
}
if (isset($options['port'])) {
    $port = $options['port'];
}
if (isset($options['username'])) {
    $username = $options['username'];
}
if (isset($options['password'])) {
    $password = $options['password'];
}
if (isset($options['query'])) {
    $query = $options['query'];
}
if (isset($options['replace'])) {
    $replace = $options['replace'];
}

$dsn = "mysql:host={$host};port={$port}";
$pdo = new PDO($dsn, $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $tableWhere[] = "`{$rowColumn['COLUMN_NAME']}` LIKE " . $pdo->quote('%' . $query . '%') . "";
    }
    $sql2 .= join(' OR ', $tableWhere);
//    echo $sql2 . PHP_EOL;

    $sqlUpdate = "UPDATE {$database}.{$rowTable['TABLE_NAME']} SET ";
    $sqlUpdateExecute = false;

    //result
    $sth2 = $pdo->query($sql2);
    $columnsFound = array();
    while ($rowTableRow = $sth2->fetch(PDO::FETCH_ASSOC)) {
        $total ++;
        echo "found row in table {$rowTable['TABLE_NAME']}" . PHP_EOL;
        foreach ($rowTableRow as $k => $v) {
            echo "\t {$k} : {$v}" . PHP_EOL;

            $sqlUpdate .= "{$k} = REPLACE({$k}, {$pdo->quote($query)}, {$pdo->quote($replace)}), ";
            $sqlUpdateExecute = true;
        }
    }

    if ($sqlUpdateExecute) {
        echo "updating table {$rowTable['TABLE_NAME']}" . PHP_EOL;
        $sqlUpdate = substr($sqlUpdate, 0, -2);
        $sqlUpdate .= ' WHERE ' . join ('OR', $tableWhere);
//        echo $sqlUpdate . PHP_EOL;
        try {
            $pdo->exec($sqlUpdate);
        } catch (\Exception $e) {
            echo "FAIL" . PHP_EOL;
        }
    }
}

echo "Total found: " . $total . PHP_EOL;
