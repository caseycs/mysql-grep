# mysql grep

Search&replace all tables of the database for occurrences, like grep but for database

Usage example:

Dry run:
```
php dbgrep.php --username=root --password=banaan123 --database=project --search=wiredstuff
```

Replace:
```
php dbgrep.php --username=root --password=banaan123 --search=wiredstuff --database=project  --replace=coolstuff
```

According to columns collation search maybe case-insensetive, BUT replace is every time case-sensetime. 