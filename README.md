# fa_db_migration
FrontAccounting v.2.3.xx and v.2.4 database migration module

Can be installed for v.2.4 too. Please refer to : http://frontaccounting.com/punbb/viewtopic.php?id=7056 when installing locally.

This is a module to handle database migration in FrontAccounting version 2.3.xx.
If you are an FA developer who needs to modify FA and change FA database structure a lot, this will help you maintain the change in the database.
You can:
- upload database migration file in the form of sql file
- replace the already uplaoded migration file
- do migration up or roll back version, both one by one or fast forward migration

This module will handle each company separately. In files folder, every company will have their own migration_version.json file and migration_file directory. This directory is where all sql file will be uploaded. 

The migration_version.json will track the current version that the system is active and the next number to be given to new uploaded migration file.
