#Primal Database Pool

Created and Copyright 2013 by Jarvis Badgley, chiper at chipersoft dot com.

Primal Database Pool is a basic class for managing database configurations and active PDO links from a central location. It offers an optional singleton interface for maintaining a global instance of the pool.

[Primal](http://www.primalphp.com) is a collection of independent micro-libraries.

##Usage

###Adding a configuration

The class currently offers support for three database types:

- `addMySQL($name, $host, $username, $password[, $database[, $options[, $port]]])`
- `addPostgrSQL($name, $host, $username, $password[, $database[, $options[, $port]]])`
- `addSQLite($name, $database, $username[, $options])`

`$name` is the name which will be used to retrieve the PDO object.

All of this functions are chainable.