
Installation
Create Table users by running user_create_table.sql

* To create initial table run the following command.
php --create_table -u myuser -p mypassword -h localhost

* To create a dry run of the user upload process run the following command.
Note : no actual records is inserted just check if CSV is processed correctly.
php --file users.csv --dry_run

* To upload the users CSV file to database run the following command.
Note : Truncate existing table and insert records from CSV file.
php --file users.csv -u myuser -p mypassword -h localhost

  process_YYYYMMDDHHMMSS.log will be created 

  Added Total = Number of successful uploaded users (name, username and valid email).
  Failed Total = Number of invalid record on the CSV file 
    -- Invalid CSV record will be display below this total.


Valid parameters 

    --file [csv file name] – this is the name of the CSV to be parsed 
    --create_table – this will cause the MySQL users table to be built (and no further • action will be taken) 
    --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. 
        All other functions will be executed, but the database won't be altered 
    
    -u – MySQL username 
    -p – MySQL password 
    -h – MySQL host 
    -d – MySQL database 
    --help - show Help guide. 

* Assumptions
create_table = create table only if it does not exists.
Uploading users table truncate existing records and insert records in passed CSV file.



