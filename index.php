<?php
//First we need to create a connection to our MySQL database and also set up some details into variables for the database connection.
$db_user = "root";
$db_pass = "1404scottFRI";
$db_db = "tropo";
//We use the details we've stored into our variables to connect to the database.
mysql_connect("localhost", $db_user, $db_pass) or die("ERROR: MySQL cannot connect.");

//Next we need to select the database which we wish to work with.
mysql_select_db($db_db) or die("ERROR: MySQL cannot select database.");	

//Next we get all of the data from our calls table of the database.
$calls_sql = mysql_query("SELECT * FROM `calls`");

?>
<!doctype html>
<html>

<head>
  <meta charset="UTF-8">
  <title>TAF Call Records</title>
  <link rel="stylesheet" href=".style.css">
  <script src=".sorttable.js"></script>
</head>

<body>

  <div id="container">
  
    <h1>Thank-a-farmer Calls</h1>
    
    <table class="sortable">
      <thead>
        <tr>
          <th>Date</th>
		  <th>Message</th>
		  <th>Rec type</th>
		  <th>First name</th>
		  <th>Last name</th>
		  <th>Phone</th>
          <th>Email</th>
		  <th>City</th>
		  <th>Province</th>
		  <th>Country</th>
		  <th>Age</th>
		  <th>Call type</th>
		  <th>Language</th>
        </tr>
      </thead>
      <tbody>



<?php



//We then want to set up a while loop which will allow us to output the details for each individual row of the table as it loops.
while($call = mysql_fetch_object($calls_sql)){

  //check to see if there is a recording here
 $recordings_sql = mysql_query("SELECT * FROM `recordings` WHERE `callId` = '".$call->callId."'");

  $recording_text ='';
  $rec_type = '';
  while($recording = mysql_fetch_object($recordings_sql)){
    $recording_text = "<a href='".$recording->recording_url."'>Listen</a>";
    $rec_type = $recording->rec_type;
    //$recording_text ="<audio src='" . $recording->recording_url."' controls='controls> oops doesnt support</audio>";
  }

  
    // Print 'em
          print("
          <tr class='file'>
		  <td>$call->timestamp</td>
		  <td>$recording_text</td> 
		  <td>$rec_type</td>  
		  <td>$call->callerfname</td>
		  <td>$call->callerlname</td>
		  <td>$call->callernumber</td>
		  <td>$call->calleremail</td>
		  <td>$call->callercity</td>
		  <td>$call->callerprovince</td>
		  <td>$call->callercountry</td>
		  <td>$call->callerage</td>
		  <td>$call->callertype</td>
		  <td>$call->callerlanguage</td>
          </tr>");

}
?>

      </tbody>
    </table>
   </div>
  </body>
 </html>