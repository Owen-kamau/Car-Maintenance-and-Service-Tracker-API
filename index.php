<?php
//Create database connection
$Servername="localhost";
$username = "root";
$password = "6350";
$dbname = "pro";

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
        echo "<p> This is a new semster.</p>";
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
