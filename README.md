
Installation
Create Table users by running user_create_table.sql

CREATE_TABLE
* To create initial table run the following command.
php user_upload.php --create_table -u myuser -p mypassword -h localhost -d catalyst

DRY RUN
* To create a dry run of the user upload process run the following command.
Note : no actual records is inserted just check if CSV is processed correctly.
php user_upload.php --file users.csv --dry_run

UPLOAD_USERS
* To upload the users CSV file to database run the following command.
Note : Truncate existing table and insert records from CSV file.
php user_upload.php --file users.csv -u myuser -p mypassword -h localhost -d catalyst

TOTALS DISPLAY
Total Record = Count the number of CSV row to be processed
Total Valid = Count number of valid CSV row
Total Invalid = Count the number of invalid CSV 
    -- Invalid length
    -- Invalid email format
    -- Duplicate csv record
Total Uploaded = Total uploaded to DB
Total Failed = Total failed records when insert is executed catch errors and display

Valid parameters 
    --file [csv file name] – this is the name of the CSV to be parsed 
    --create_table – this will cause the MySQL users table to be built (and no further • action will be taken) 
    --dry_run – this will be used with the --file directive in case we want to run the script but not insert into the DB. 
        All other functions will be executed, but the database won't be altered 
    
    -u – MySQL username 
    -p – MySQL password 
    -h – MySQL host 
    -d – MySQL database (optional for non dedicate mySQL DB server)
    --help - show Help guide. 

* Assumptions
Location of CSV file is current directory
create_table = create table only if it does not exists. If it exists it does not recreate.
Deletion of users is not handled by this process.
Uploading records insert or replace existing records.

* Must have additional feature
Log files of processing using process_YYYYMMDD_HHMMSS.log
Handle deletion of user. ie. if it does not exist on CSV delete
New functionality to refresh table. Truncate or recreate etc.
