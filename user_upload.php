<?php

error_reporting(E_ALL ^ E_WARNING);

function getDBConnection($user, $password, $host, $dbname){
    $conn = new mysqli($host, $user, $password, $dbname);

    if (mysqli_connect_error()) {
        echo mysqli_connect_errno() . ":" . mysqli_connect_error();
        return null;
    }

    return $conn;
}

function createTable($conn){
    $sql = <<<EOT
    CREATE TABLE `users` (
        `name` varchar(100) NOT NULL,
        `surname` varchar(100) NOT NULL,
        `email` varchar(200) NOT NULL,
        UNIQUE KEY(`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    EOT;

    if(mysqli_query($conn, $sql)) {  
        echo "Table 'users' created successfully\n";  
    }
    else{
        echo "Failed to create 'users' table\n";
        echo "Error description: " . mysqli_error($conn);
    }
}

function processCSV($csvfile, $conn, $isdryrun)
{
    try 
    {
        $row = 1; //start row 1
        $uploadcnt=0; //count of uploaded records
        $failedcnt=0; //count of failed upload
        $validcnt = 0; //count of valid csv row
        $invalidcnt = 0; //count of invalid csv row -> invalid length, invalid email
        $invalidlines = '';//Use to display invalid form in CSV
        $failedlines = ''; //Use to display error when upload to DB failed
        $records = array(); //Array to save to check for duplicate for unique email in CSV
        if (($handle = fopen($csvfile, "r")) !== FALSE) {
            fgets($handle); //read a line ignore heading
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $row++;
                $num = count($data); //column count
               
                if ($num == 3){ // get 3 columns
                    $name = ucfirst(strtolower(trim($data[0])));
                    $surname = ucfirst(strtolower(trim($data[1])));
                    $email = strtolower(trim($data[2]));

                    $lineerror = false;
                    $lineerrormsg = '';

                    //Validate CSV Row
                    if (isset($records[$email])){ //We already have this email make this invalid
                        $lineerror = true;
                        $lineerrormsg .= " -- Email - Duplicate record\n";
                    }

                    if (strlen(trim($name)) == 0 || strlen($name) > 100){
                        $lineerror = true;
                        $lineerrormsg .= " -- Name - Invalid Length\n";
                    }

                    if (strlen(trim($surname)) == 0 || strlen($surname) > 100){
                        $lineerror = true;
                        $lineerrormsg .= " -- Surname - Invalid Length\n";
                    }

                    if (strlen(trim($email)) == 0 || strlen($email) > 200){
                        $lineerror = true;
                        $lineerrormsg .= " -- Email - Invalid Length\n";
                    }

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
                        $lineerror = true;
                        $lineerrormsg .= " -- Email - Invalid Format\n";
                    } 
                    //End Validate

                    if ($lineerror){
                        $invalidcnt++;
                        $invalidlines .= "Row $row - Invalid Row Data => '$name, $surname, $email'\n" . $lineerrormsg;
                    }
                    else{

                        if (!$isdryrun){
                            //Add to DB if not a dry run
                            $name = $conn->real_escape_string($name);
                            $surname = $conn->real_escape_string($surname);
                            $email = $conn->real_escape_string($email);

                            $sql = "REPLACE into users (name, surname, email) VALUES ('$name','$surname','$email')";    
                            
                            if ($conn->query($sql)) {
                                $uploadcnt++;
                            }
                            else{
                                $failedlines.="Row $row failed - : " . $conn->error . "\n";
                                $failedcnt++;
                            }
                        }
                        $validcnt++;
                    }

                    //Save this record to check duplicate using email as key
                    if (!isset($records[$email])){
                        $records += [$email => 1];
                    }
                }
                else{
                    $invalidcnt++;
                    $invalidlines .= "Row $row - Invalid Row Data\n" . " -- Incorrect number of columns\n";
                }
            } // End While

            //Close file
            fclose($handle);

            echo "Total Record = " . ($row - 1) . "\n";
            echo "\nTotal Valid = $validcnt\n";
            echo "\nTotal Invalid = $invalidcnt\n";

            if ($validcnt > 0){
                echo $invalidlines;
            }

            if (!$isdryrun){
                echo "\nTotal Uploaded = $uploadcnt\n";
                echo "\nTotal Failed = $failedcnt\n";

                if ($failedcnt > 0){
                    echo $failedlines;
                }
            }
            
        }
    } 
    catch (Exception $e) 
    {
        echo $e->errorMessage();
        return false;
    }
}

function showHelp(){
    $bar = <<<EOT
        --file [csv file name] – this is the name of the CSV to be parsed 
        --create_table – this will cause the MySQL users table to be built (and no further action will be taken) 
        --dry_run – this will be used with the 
        --file directive in case we want to run the script but not insert into the DB. 
          All other functions will be executed, but the database won't be altered 
        
        -u – MySQL username 
        -p – MySQL password 
        -h – MySQL host 
        -d - MySQL database (optional leave empty on dedicated mySQL server)
        --help - show Help guide. 

    EOT;
    echo $bar;
    exit;
}

$conn = null; //DB Connection 

// Short parameter option prefix with '-'
$shortopts  = "";
$shortopts .= "u:";  // Required value MySQL User
$shortopts .= "p:";  // Required value mySQL Password
$shortopts .= "h:";  // Required value mySQL Host
$shortopts .= "d:"; // Optional value mySQL Database Name

// Short parameter option prefix with '--'
$longopts  = array(
    "file:",         // Required value Passed the CSV to process
    "create_table",  // No Value - Create the table only no other processing
    "dry_run",       // No value - Process file but no DB insert
    "help",          // No value - Help
);

$options = getopt($shortopts, $longopts);

$errors = "";

if (count($options))
{
    // Recreate the table if this parameter exist also check that DB parameter is valid
    if (array_key_exists('create_table', $options)){
        if (array_key_exists('u', $options) 
        && array_key_exists('p', $options) 
        && array_key_exists('h', $options)){
            $dbusername = $options['u'];
            $dbpassword = $options['p'];
            $dbhost = $options['h'];
            
            $dbname = "";
            if (array_key_exists('d', $options)){
                $dbname = $options['d'];
            }

            $conn = getDBConnection($dbusername, $dbpassword, $dbhost, $dbname);
            if (!$conn){
                $errors .= "DB connection is not valid\n";
            }
            else{
                //Create the users table
                createTable($conn);
            }
        }
        else{
            $errors .= "-u -p -h DB parameters are required\n";
        }
    }
    //Dry Run or Process the CSV need --file parameter
    elseif (array_key_exists('file', $options)){ 
        $csvfile = $options['file']; 
        if (!file_exists($csvfile)){
            $errors .= "--file value $csvfile is not found or valid\n";
        }
        else{
            if(array_key_exists('dry_run', $options)){
                //Do a dry run of the CSV no DB processing at this stage
                processCSV($csvfile, null, true);
            }
            else{
                //We process the CSV but first check that we passed the DB parameters and can connect to DB
                if (array_key_exists('u', $options) 
                && array_key_exists('p', $options) 
                && array_key_exists('h', $options)){
                    $dbusername = $options['u'];
                    $dbpassword = $options['p'];
                    $dbhost = $options['h'];
                   
                    $dbname = "";
                    if (array_key_exists('d', $options)){
                        $dbname = $options['d'];
                    }

                    $conn = getDBConnection($dbusername, $dbpassword, $dbhost, $dbname);
                    if (!$conn){
                        $errors .= "DB connection is not valid\n";
                    }
                    else{
                        //Process the CSV
                        processCSV($csvfile, $conn, false);
                    }
                }
                else{
                    $errors .= "-u -p -h DB parameters are required\n";
                }
            }
        }
    }
    //Help Option
    elseif (array_key_exists('help', $options)){ 
        showHelp();
    }
    else{
        $errors .= "Invalid option parameter\n";
        
    }
}
else{
    echo "Invalid option parameter\n";
    showHelp();
}

if ($errors){
    echo "Error encountered: $errors";
}

// Close connection if it exists
if ($conn){
    $conn->close();
}

?>