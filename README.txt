# MOVED TO GITLAB
FCUK MICROSOFT, i migrated all my repos to www.gitlab.com/atesin soon i will delete all these (ironically, GIT was invented by linus torvalds)

# memcached-dump

dumps all memcached server data, in 'set' format, to stdout
Info : I wanted to speed up my website through memcached, but with some data resilience
License : GPLv3, this tool was written and published just for learning and comes
without any warranty. Usage :

memcached-dump.php [host|host:port|:port] : connect to server and dump data
* host : optional server address or hostname, defaults to 'localhost'
* port : optional server tcp port, defaults to 11211
memcached-dump.php -[-]<h|help|?> : show this help

---

some nice tricks:

> memcached-dump.php my.other.server               # dumps a remote server if reachable
> memcached-dump.php > mydump-memcached.bak        # save the plain dump to a file
> nc localhost 11211 < mydump-memcached.bak        # restore the previous backuped dump
> memcached-dump.php | gzip > compressed-dump.gz   # save a local dump backup compressed
> gunzip -c compressed-dump.gz| nc localhost 11211 # restore the previous compressed backup
> crontab -e                                       # schedule a daily bachup at 5:00 am
  0 5 * * * memcached-dump > memcached-daily.bak   # 
> # can also configure in PreScript and PostScript memcached systemd unit service

memcached is awesome, it is really simple, optimized and fast (instead redis which
is full of nice features)

so i wanted to install memcached to speed up my sites and avoid php session blocking
in my scripts. When i searched for 'memcached data persistency' or 'memcached backup restore'
i was surprised by the lack of related support/tools but a couple scripts, later i
discovered there is a tool 'memcached-tool' in the official centos package, that also supports
data dump (in a different way)

i am not so skilled in programming so this tool is very simple, this script is inspired in
meabed https://github.com/meabed/memcached-php-backup-restore , i wanted to wrote it in python
but i need it quick, i also tried in bash but realized bash have no good support for streams
and binary data, so i ended in php for now, anyway chances are if you have memcached installed
you also have a website with database and php installed

this tool is very basic, it just dumps data compatible with 'set' format for easy later restore,
the memcached 'set' command just stores the key, flags, expiration time, data length and
the data itself; all other meta data like slabs or last access time are lost, i invite you
to write a better and more portable tool :)

