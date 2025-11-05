<?php
//Create database connection
$Servername="localhost";
$username = "root";
<<<<<<< HEAD
$password = "1234";
=======
$password = "12345";
>>>>>>> cf99e83 (save my local changes)
$dbname = "cmts_db";

//create connection 
$conn = new mysqli($Servername, $username, $password, $dbname);

//check connection 
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
    echo"Connected successfully to " . $dbname;
}

class MyClass {
    public function heading() {
        echo "Welcome to BBIT DevOps!";
    }
    public function myMethod() {
        echo "<p> This is a Car Maintanance and Tracker Service.</p>";
   }
   public function footer(){
    echo"<footer>Contact us at <a href='mailto:info@bbit.edu'>info@bbit.edu</a></footer>";
   }
}
// create an instance of MyClass
$instance = new MyClass();

// call the method myMethod
$instance->heading();
$instance->myMethod();
$instance->footer();
