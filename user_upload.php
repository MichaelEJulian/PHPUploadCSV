<?php

function checkDBConnection($user, $password, $host, $dbname){
    try {
        $conn = @mysqli_connect($host, $user, $password, $dbname);
    } catch (Exception $e) {
        echo $e->errorMessage();
        return false;
    }

    // Close connection
    if ($conn){
        $conn->close();
    }
    
    return true;
}

function recreateTable($user, $password, $host, $dbname){
    $conn = mysqli_connect($host, $user, $password,$dbname);  
         
    try {
        $conn = @mysqli_connect($host, $user, $password, $dbname);
    } catch (Exception $e) {
        echo $e->errorMessage();
        return false;
    }

    $sql = "DROP TABLE users";
    if(mysqli_query($conn, $sql)) {  
        echo "Table 'users' deleted successfully\n";  
    }else {  
        echo "Failed to delete 'users' table\n";
    }  

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
    }
    
    // Close connection
    if ($conn){
        $conn->close();
    }

}

function dryRun($csvfile)
{
    try 
    {
        $row = 1; //Start 1 Ignoring CSV Heading
        $added = 0;
        $failed = 0;
        $failedlines = '';
        if (($handle = fopen($csvfile, "r")) !== FALSE) {
            fgets($handle);
            while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $row++;
                $num = count($data); //Column count
               
                if ($num == 3){ // get 3 columns on this row
                    $name = ucfirst(strtolower(trim($data[0])));
                    $surname = ucfirst(strtolower(trim($data[1])));
                    $email = strtolower(trim($data[2]));
                    $lineerror = false;
                    $lineerrormsg = '';

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

                    if ($lineerror){
                        $failed++;
                        $failedlines .= "Row $row - Invalid Row Data => $name, $surname, $email\n" . $lineerrormsg;
                    }
                    else{
                        $added++;
                    }
                }
                else{
                    $failed++;
                    $failedlines .= "Row $row - Invalid Row Data\n" . " -- Incorrect number of columns\n";
                }
               
                

            } // End While

            echo "Total Processed = " . ($row - 1) . "\n";
            echo "Total Added = $added\n";
            echo "Total Failed = $failed\n";

            if ($failed > 0){
                echo $failedlines;
            }

            fclose($handle);
        }
        
        if ($failed == 0){
            return true;
        }
        else{
            return false;
        }
    } 
    catch (Exception $e) 
    {
        echo $e->errorMessage();
        return false;
    }
}

function processCSV($csvfile, $user, $password, $host, $database){
    if (dryRun($csvfile)){ // CSV format is ok proceed
        echo "Process CSV => Truncate table and add CSV records";
    }
    else{ // Do nothing process is all CSV should be valid or no records added

    }
}

function showHelp(){

    $bar = <<<EOT
        --file [csv file name] – this is the name of the CSV to be parsed 
        --create_table – this will cause the MySQL users table to be built (and no further • action will be taken) 
        --dry_run – this will be used with the 
        --file directive in case we want to run the script but not insert into the DB. 
          All other functions will be executed, but the database won't be altered 
        
        -u – MySQL username 
        -p – MySQL password 
        -h – MySQL host 
        -d - MySQL database
        --help - show Help guide. 

    EOT;

    echo $bar;
    exit;

}

// Script example.php
$shortopts  = "";
$shortopts .= "u:";  // Required value MySQL User
$shortopts .= "p:";  // Required value mySQL Password
$shortopts .= "h:";  // Required value mySQL Host
$shortopts .= "d:";  // Optional value mySQL Host

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
        if (array_key_exists('u', $options) && array_key_exists('p', $options) && array_key_exists('h', $options) && array_key_exists('d', $options)){
            $dbusername = $options['u'];
            $dbpassword = $options['p'];
            $dbhost = $options['h'];
            $dbname = $options['d'];

            if (!checkDBConnection($dbusername, $dbpassword, $dbhost, $dbname)){
                $errors .= "DB connection is not valid\n";
            }
            else{
                //Drop and Create the users table
                recreateTable($dbusername, $dbpassword, $dbhost, $dbname);
            }
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
                dryRun($csvfile);
            }
            else{
                //We process the CSV but first check that we passed the DB parameters and can connect to DB
                if (array_key_exists('u', $options) && array_key_exists('p', $options) && array_key_exists('h', $options) && array_key_exists('d', $options)){
                    $dbusername = $options['u'];
                    $dbpassword = $options['p'];
                    $dbhost = $options['h'];
                    $dbname = $options['d'];

                    if (!checkDBConnection($dbusername, $dbpassword, $dbhost, $dbname)){
                        $errors .= "DB connection is not valid\n";
                    }
                    else{
                        //Process the CSV
                        print "Process the CSV";
                    }

                }
                else{
                    $errors .= "-u -p -h -d DB parameters are required\n";
                }
            }
        }
    }
    else{
        $errors .= "Invalid option parameter\n";
    }
}
else{
    showHelp();
}


echo $errors;

?>