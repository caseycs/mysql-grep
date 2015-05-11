# mysql grep

Search&replace all tables of the database for occurrences, like grep but for database.
No external dependencies are needed (only `php-cli` actually, 5.3+), so just download sources

```
wget https://raw.githubusercontent.com/caseycs/mysql-grep/master/dbgrep.php`
```

and run!

Search only:
```
php dbgrep.php --username=root --password=banaan123 \
    --database=project --search=wiredstuff
```

Replace:
```
php dbgrep.php --username=root --password=banaan123 \
    --database=project --search=wiredstuff
    --replace=coolstuff
```

According to columns collation search maybe case-insensetive (so you will find `WiredStuff` and `WIREDSTUFF` as well), BUT replace is every time case-sensetive. So you have to replace for all the cases separately: `wiredstuff -> coolstuff`, `WiredStuff -> CoolStuff` and so on.
