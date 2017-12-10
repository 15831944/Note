### ampfilter.c - 放大反射列表过滤（`NTP`，`SSDP`，`SNMP`，`MSSQL`）
```
$ wget https://raw.githubusercontent.com/vforbox/Note/master/Security/Network/ampfilter.c
$ gcc -o ampfilter ampfilter.c -pthread
$ ./ampfilter ntplist.txt ntpamp.txt 10 419
```

### ntpfilter.php - `NTP` 放大反射列表过滤
```
$ wget https://raw.githubusercontent.com/vforbox/Note/master/Security/Network/ntpfilter.php
$ php ntpfilter.php ntplist.txt ntpamp.txt 419 10
```
