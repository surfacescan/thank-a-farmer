<?php 
//First we include the Tropo WebAPI classes.
include_once('lib/TropoClasses.php');
require('lib/limonade.php');
require('config.php');
 

  //$cmd = "/home/ec2-user/.rvm/rubies/ruby-2.0.0-p247/bin/ruby ./push_to_soundcloud.rb af898428acb39380093c52c61433ab89.wav";
 // $cmd = "/usr/bin/ruby ./push_to_soundcloud.rb " . $call_id . ".wav";
  //exec($cmd);
 
function connectToMySQL(){
  $db_user = USERNAME;
  $db_pass = PASS;
  $db_db = DB;
  mysql_connect("localhost", $db_user, $db_pass) or die("ERROR: MySQL cannot connect.");
  mysql_select_db($db_db) or die("ERROR: MySQL cannot select database.");
} 

dispatch_post('/start', 'app_start');
function app_start() {

  $tropoSession = new Session();
  
  
  $tropo = new Tropo();
  
  $callme = $tropoSession->getParameters("num_to_dial");
  
  if($callme){    // Call out from "Call me button"
  
     $callme = preg_replace("/[^0-9,.]/", "", $callme); 
     $callme = ltrim($callme, '0');  //no zeros
	 $callme = ltrim($callme, '+');  //no +
	 $callme = ltrim($callme, '1');  //no 1
	 error_log("trimmed number=" . $callme);
	 
     $tropo->call("+1".$callme);
	 $callertype= "call_out";
	 	 
	  //Now we grab the Thank-a-farmer specific caller details
     $callernumber = $tropoSession->getParameters("callernumber");
     $callerfname = $tropoSession->getParameters("callerfname");
     $callerlname = $tropoSession->getParameters("callerlname");
     $callerage = $tropoSession->getParameters("callerage");
     $callercountry = $tropoSession->getParameters("callercountry");
     $callercity = $tropoSession->getParameters("callercity");
     $callerprovince = $tropoSession->getParameters("callerprovince");
     $calleremail = $tropoSession->getParameters("calleremail");
     $callerchecked = $tropoSession->getParameters("callerchecked");
	 
	 
  }else{   // call from Phono plugin or from a local call
  
     $call_id = $tropoSession->getCallId();
     //The below line sets $caller_from as an array which has all of the FROM data passed accross in the JSON request.
     $caller_from = $tropoSession->getFrom();

     //Below we can see how that information stored in $caller_from can be accessed and pulled out into other variables.
     $caller_number = $caller_from['id'];
     $caller_channel = $caller_from['channel'];
     $caller_network = $caller_from['network'];
  
     $headers = $tropoSession->getHeaders();
	 
	 
	 if($caller_number){
	 
	    $callertype = "call_in";
	    $callernumber = $caller_from['id'];
		 $callerfname = '';
		 $callerlname = '';
		 $callerage = '';
		 $callercountry = '';
		 $callercity = '';
		 $callerprovince = '';
		 $calleremail = '';
		 $callerchecked = '';
		 $callerage= ''; 
	 }else{
	 
		 $callertype = "browser_call";
		 $callernumber = $headers->x_callernumber;
		 $callerfname = $headers->x_callerfname;
		 $callerlname = $headers->x_callerlname;
		 $callerage = $headers->x_callerage;
		 $callercountry = $headers->x_callercountry;
		 $callercity = $headers->x_callercity;
		 $callerprovince = $headers->x_callerprovince;
		 $calleremail = $headers->x_calleremail;
		 $callerchecked = $headers->x_callerchecked;
     }     
  } 
   if($callerage == "Age (optional)") $callerage="-";
   
  $tropo->say("http://aws.farmradio.org/thank-a-farmer/prompts/ThankAFarmer_bilingualintro_part_1.wav");
  $options = array("choices" => "[1 DIGIT]", "name" => "digit", "mode"=>"dtmf","attempts"=>2,'bargein' => 'true', "timeout" => 7); 
  $tropo->ask("http://aws.farmradio.org/thank-a-farmer/prompts/ThankAFarmer_bilingualintro_part_2.wav", $options);

  $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=instructions&callerfname=" . $callerfname . "&callerlname=" . $callerlname . "&callernumber=" . $callernumber . "&calleremail=".$calleremail . "&callerage=" . $callerage ."&callercountry=".$callercountry."&callerprovince=".$callerprovince."&callercity=".$callercity."&callerchecked=".$callerchecked . "&callertype=" . $callertype));
  $tropo->on(array("event" => "incomplete", "next" => "thank-a-farmer_test.php?uri=error"));

  $tropo->RenderJson();
 }
  
dispatch_post('/instructions', 'app_instructions');
function app_instructions() {
     $language = '';
	 
     $tropo = new Tropo();
     @$result = new Result();

     $answer = $result->getValue();
     $call_id = $result->getCallId();
  
     if($answer == 1) $language = "en";
     if($answer == 2) $language = "fr";
     
	 
	 // this app will make an entry into the database
	 connectToMySQL();   

	// gathering data about caller to put in the database
	 $callernumber = $_GET['callernumber'];
     $callerfname = $_GET['callerfname'];
     $callerlname = $_GET['callerlname'];
     $callerage = $_GET['callerage'];
     $callercountry = $_GET['callercountry'];
     $callercity = $_GET['callercity'];
     $callerprovince = $_GET['callerprovince'];
     $calleremail = $_GET['calleremail'];
     $callerchecked = $_GET['callerchecked'];
	 $callertype = $_GET['callertype'];
	
	// Now put this call as an entry in the database
	mysql_query("INSERT INTO `calls` (callId, callertype, callerfname, callerlname, callerage,callercountry,callercity,callerprovince,calleremail,callernumber, callerchecked, callerlanguage) VALUES ('".$call_id."','". $callertype ."','". $callerfname ."','". $callerlname ."','". $callerage ."','" . $callercountry ."','" . $callercity."','". $callerprovince."','". $calleremail ."','". $callernumber. "','" .$callerchecked. "','".$language."')");

	
   
	 
	$tropo->say("http://aws.farmradio.org/thank-a-farmer/prompts/ThankAFarmer_" . $language . "_instructions.wav");
    $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=record&lang=".$language));
    $tropo->RenderJson();
}
  
  
dispatch_post('/record', 'app_record');
function app_record() { 
  

  $language = $_GET['lang'];
  
  $tropo = new Tropo();
  @$result = new Result();

  $call_id = $result->getCallId();
  
  $tropo->record(array(
    'name' => 'recording',
    'say' => 'http://aws.farmradio.org/thank-a-farmer/prompts/ThankAFarmer_' .$language. '_rec_prompt.wav',
    'url' => 'http://aws.farmradio.org/recordings/recording.php?name='.$call_id,
	'terminator' => '#',
    'bargein' => 'false',
    'beep' => 'true',
    'timeout' => 10,
	'attempts'=>2,
    'maxSilence' => 7,
    'maxTime' => 50,
    'format' => 'audio/wav',
  ));
  
  if($language == 'en')
     $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=listen&lang=".$language, "say" => "Thank you, here is the message you have just recorded."));
  elseif($language == 'fr')
     $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=listen&lang=".$language, "say" => "Merci, voici votre message:", "voice"=>"Charlotte"));
	 

     $tropo->on(array("event" => "incomplete", "next" => "thank-a-farmer_test.php?uri=error&lang=".$language)); 
     $tropo->on(array("event" => "hangup", "next" => "thank-a-farmer_test.php?uri=hangup")); 
	 
  //$tropo->on(array("event" => "incomplete", "next" => "thank-a-farmer_test.php?uri=error&lang=".$language));
  
  $tropo->RenderJson(); 
}
dispatch_post('/listen', 'app_listen');
function app_listen() {

  $tropo = new Tropo();
  @$result = new Result();
  $language = $_GET['lang'];

  $call_id = $result->getCallId();
  $tropo->say("http://aws.farmradio.org/recordings/".$call_id.".wav");
  $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=give_prompt&lang=" . $language));

  $tropo->renderJSON();
} 

dispatch_post('/give_prompt', 'app_give_prompt');
function app_give_prompt() {

  $tropo = new Tropo();
  @$result = new Result();
  $language = $_GET['lang'];

  $call_id = $result->getCallId();
  
  $options = array("choices" => "[1 DIGIT],#", "name" => "digit", "mode"=>"dtmf","terminator"=>"*", "attempts"=>2,'bargein' => 'true', "timeout" => 5); 
  $tropo->ask("http://aws.farmradio.org/thank-a-farmer/prompts/ThankAFarmer_" . $language . "_2.wav", $options);
  $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=get_prompt&lang=" . $language));
  $tropo->on(array("event" => "incomplete", "next" => "thank-a-farmer_test.php?uri=error&lang=" . $language));
  
  $tropo->renderJSON();
} 

dispatch_post('/get_prompt', 'app_get_prompt');
function app_get_prompt() {
 
  $tropo = new Tropo();
  @$result = new Result();
  $language = $_GET['lang'];
  $answer = $result->getValue();

  if($answer == '#') $action = "finish";
  if($answer == 1) $action = "listen";
  if($answer == 2) $action = "record";

  $tropo->on(array("event" => "continue", "next" => "thank-a-farmer_test.php?uri=" . $action . "&lang=" . $language));
  
  $tropo->renderJSON();
}

dispatch_post('/finish', 'app_finish');
function app_finish() {

   connectToMySQL(); 

   $tropo = new Tropo();
  @$result = new Result();
  $language = $_GET['lang'];
  $call_id = $result->getCallId();
  
  //Save the recording in the database
  
  mysql_query("INSERT INTO `recordings` (callId, recording_url, rec_type) VALUES ('".$call_id."', 'http://aws.farmradio.org/recordings/".$call_id.".wav','saved')");
  
// Push the recording to Soundcloud account
 
 //$cmd = "/usr/bin/ruby ./push_to_soundcloud.rb af898428acb39380093c52c61433ab89.wav";
 // $cmd = "/usr/bin/ruby ./push_to_soundcloud.rb " . $call_id . ".wav";
 //exec($cmd);
  
  // Send a couple emails?
  
  $tropo->say("http://aws.farmradio.org/thank-a-farmer/prompts/ThankAFarmer_" . $language . "_3.wav");
  
  
  
  $tropo->hangup();
  $tropo->renderJSON();
  mysql_close();
}


dispatch_post('/error', 'app_error');
function app_error() {
  $tropo = new Tropo();
  @$result = new Result();
  
  $language = $_GET['lang'];
  
  if(!$language){
     $tropo->say("Sorry I can't hear anything. Check your microphone isn't on mute and call back to try again. Good Bye.");
     $tropo->say("Désolé, je ne peux rien entendre. Vérifiez que votre microphone n'est pas en sourdine et de rappeler à essayer de nouveau. Au revoir.", array("voice" => "Charlotte"));
  }	 
  elseif($language == 'en') 
    $tropo->say("Sorry I can't hear anything. Check your microphone isn't on mute and call back to try again. Good Bye.");
  elseif($language == 'fr')
     $tropo->say("Désolé, je ne peux rien entendre. Vérifiez que votre microphone n'est pas en sourdine et de rappeler à essayer de nouveau. Au revoir.", array("voice" => "Charlotte"));
  
   //Hang up the call.
  $tropo->hangup();

  //Render the JSON to be read back by the Tropo service.
  $tropo->renderJSON();

}

dispatch_post('/hangup', 'app_hangup'); 
   function app_hangup() { 
   	
     // this app will make an entry into the database
	 connectToMySQL();  
     
     $tropo = new Tropo();
    @$result = new Result();
     $call_id = $result->getCallId();
     
     //DB work
     //Save the recording in the database
  
     mysql_query("INSERT INTO `recordings` (callId, recording_url, rec_type) VALUES ('".$call_id."', 'http://aws.farmradio.org/recordings/".$call_id.".wav','hangup')");

       //Hang up the call.
  $tropo->hangup();

  //Render the JSON to be read back by the Tropo service.
  $tropo->renderJSON();

}



run();
?>  
