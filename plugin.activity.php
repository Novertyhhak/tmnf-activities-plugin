<?php
/*
Written by Novertyhhak at the end of year 2022.
Feel free to edit this plugin to your needs, just credit me :)

README file at - https://github.com/Novertyhhak/tmnf-activity-plugin





ACTIVITY TABLE EXAMPLE STRUCTURE 

+-------------------+-----------------------+-------
| login             | some_date             |  ...
+-------------------+-----------------------+-------
| login1            | 123456 7891 23 4      |  ...
| login2            | 987654 321 987 65     |  ...
| login3            | 896387 37507 159 31   |  ...
| ...               | ...                   |  ... 

*/

Aseco::registerEvent('onStartup',   				'activity_startup');
Aseco::addChatCommand('activity_savetodb', 			'saves activity data to db');
Aseco::addChatCommand('activity_showcols', 			'shows colums');
Aseco::addChatCommand('activity_compare', 			'compares two columns');
Aseco::addChatCommand('activity_removecol', 		'removes a column');

Aseco::addChatCommand('lastonops', 					'checks laston on members');


// is called at the start
function activity_startup($aseco, $command){

	// checks if activity table exists
	// and if not, creates it with a test column (it must)
	$result = mysql_query('SHOW TABLES LIKE "activity"');
	if(mysql_num_rows($result)==0 || !mysql_num_rows($result)){
		$aseco->client->query('ChatSendServerMessage', '$Fo$w$FOOactivity table doesnt exist');
		mysql_free_result($result);
		$result = mysql_query('CREATE TABLE activity (justremoveit varchar(100))');
		mysql_free_result($result);
	}


	// checks if login col exists and if not, creates it
	$result = mysql_query('SHOW COLUMNS FROM activity LIKE "login"');
	$logincolexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$logincolexists){
		mysql_query('ALTER TABLE activity ADD login VARCHAR(100)');
		$aseco->client->query('ChatSendServerMessage', '$Fo$w$FFFlogin column didnt exist');
	}

}

// a command to save stats to the activity table
function chat_activity_savetodb($aseco, $command) {

	$player = $command['author'];

	// checks if the player has rights to do it
	if(!$aseco->isMasterAdminL($player->login)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no perms!', $player->login);
		return;
	}

	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	// checks for args
	if($command['params'][0] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no args!', $player->login);
		return;
	}

	// checks if a col exists, if not creates it
	$newcolname = $command['params'][0];
	$result = mysql_query('SHOW COLUMNS FROM activity LIKE '.quote($newcolname));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){
		mysql_query('ALTER TABLE activity ADD '.$newcolname.' VARCHAR(100)');
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$fffthe column didnt exist so I created one!', $player->login);
	}else{
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00the column already exists', $player->login);
		return;
	}



	$members = $aseco->operator_list['TMLOGIN'];
	
	// loops thru operators (members)
	foreach ($members as $member) {

		// GETS DATA
		// average, timespent, mostfinished, visits
		// 893950    157085        325        126 

		$avg = getAvgAct($aseco, $member);
		$timespent = getTimeSpent($aseco, $member);
		$mostfinished = getMostFinished($aseco, $member);
		$visits = getVisits($aseco, $member);

		$adata = $avg.' '.$timespent.' '.$mostfinished.' '.$visits;

		// if there is no row with such a login, creates it and inserts the data
		// if there is, updates the row
		$result = mysql_query('SELECT * FROM activity WHERE login='.quote($member));
		$rowexists = (mysql_num_rows($result))?TRUE:FALSE;
		if($rowexists){
			mysql_query('UPDATE activity set '.$newcolname.'='.quote($adata).' WHERE login='.quote($member));
		}else{
			mysql_query('INSERT INTO activity (login, '.$newcolname.') VALUES ('.quote($member).', '.quote($adata).')');
		}


	}

	$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$090Finished saving data to $fff'.$newcolname, $player->login);



	// removes a testing column if it still exists
	$result = mysql_query('SHOW COLUMNS FROM activity LIKE "justremoveit"');
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){ return; }
	$result = mysql_query('ALTER TABLE activity DROP COLUMN justremoveit');


}

// compares two columns of data
function chat_activity_compare($aseco, $command) {

	$player = $command['author'];

	// rights check
	if(!$aseco->isMasterAdminL($player->login)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no perms!', $player->login);
		return;
	}

	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	// args check
	if($command['params'][0] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no args!', $player->login);
		return;
	}

	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no args!', $player->login);
		return;
	}

	$older = $command['params'][0];
	$newer = $command['params'][1];

	// checks if given column names are valid
	$result = mysql_query('SHOW COLUMNS FROM activity LIKE '.quote($older));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$FFF'.$older.' $FF0doesnt exist', $player->login);
		return;
	}

	$result = mysql_query('SHOW COLUMNS FROM activity LIKE '.quote($newer));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;

	if(!$colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$FFF'.$newer.' $FF0doesnt exist', $player->login);
		return;
	}

	$members = $aseco->operator_list['TMLOGIN'];

	$player->playerlist = array();
    $player->msgs = array();


	// makes a multi windowed manialink

    $head = '$c09Comparing $FFF'.$newer.' $c09with $FFF'.$older;
	$msg = array();
	$lines = 0;
	$player->msgs[0] = array(1, $head, array(1.0, 0.3, 0.14, 0.14, 0.14, 0.14, 0.14), array('Icons128x128_1', 'Solo'));

	$msg[] = array('$93CLOGIN', '$93CCUR AVG', '$93CIMPR AVG', '$93CTIME(H)', '$93CMOSTFIN', '$93CVISITS');

	// loops thru operators (members), gets the data 
    foreach ($members as $member) {
		// 893950    157085        325        126 
		$canGo = true;
		$pid = $aseco->getPlayerId($login);

		$curavg = getAvgAct($aseco, $member);
		if(is_numeric($curavg)){
			$curavg = sprintf("%4.2F", $curavg / 10000);
		}

		$result = mysql_query('SELECT * FROM activity WHERE login='.quote($member).'');
		if (mysql_num_rows($result) > 0) {
					
			$row = mysql_fetch_array($result);
		}else{
			$canGo = false;
		}
		$data_o = explode(' ', $row[$older]);

		if(count($data_o) < 4){
			$canGo = false;
		}

		$result = mysql_query('SELECT * FROM activity WHERE login='.quote($member).'');
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);	
		}else{
			$canGo = false;
		}
		$data_n = explode(' ', $row[$newer]);

		if(count($data_n) < 4){
			$canGo = false;
		}
		
		// compare
		if(is_numeric($data_n[0]) && is_numeric($data_n[1]) && is_numeric($data_n[2]) && is_numeric($data_n[3])
		&& is_numeric($data_o[0]) && is_numeric($data_o[1]) && is_numeric($data_o[2]) && is_numeric($data_o[3])){

			$avg_c = $data_o[0] - $data_n[0]; // inverse! cuz yes
			$timespent_c = $data_n[1] - $data_o[1];
			$mostfinished_c = $data_n[2] - $data_o[2];
			$visits_c = $data_n[3] - $data_o[3];
					
					
			$avg_c = sprintf("%4.2F", $avg_c / 10000);
			$timespent_c = $timespent_c / 3600;
			$timespent_c = sprintf("%4.2F", $timespent_c);
		}else{
			$canGo = false;
		}

		
		// if everything is okay and there is no missing data
		if($canGo){
			$msg[] = array('$fff'.$member, '$F00'.$curavg, '$f9f'.$avg_c, '$ff6'.$timespent_c, '$6f6'.$mostfinished_c, '$6ff'.$visits_c);
			if (++$lines > 19) {
				$player->msgs[] = $msg;
				$lines = 0;
				$msg = array();
			}
		}
	}	
	
	// add if last batch exists
	if (count($msg) > 1){
		$player->msgs[] = $msg;
	}

	// display ManiaLink message		
	if (count($player->msgs) > 1) {
		$aseco->client->query('ChatSendServerMessageToLogin', '$fffThe manialink should be sent to you.', $player->login);
		display_manialink_multi($player);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No operator(s) found with valid stats!'), $player->login);
	}
		

}




// returns avaible columns to compare to
function chat_activity_showcols($aseco, $command) {

	$player = $command['author'];

	// rights check
	if(!$aseco->isMasterAdminL($player->login)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no perms!', $player->login);
		return;
	}

	$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$FFFColumns in activity table:', $player->login);

	// loops thru all columns
	$result = mysql_query('SHOW COLUMNS FROM activity');
	while($row = mysqli_fetch_array($result)){
		if(($row['Field']!="login") && ($row['Field']!="justremoveit")){
			$aseco->client->query('ChatSendServerMessageToLogin', '$ccc'.$row['Field'], $player->login);
		}

	}

}

// removes a column with a specific name
function chat_activity_removecol($aseco, $command) {

	$player = $command['author'];

	// rights check
	if(!$aseco->isMasterAdminL($player->login)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$F00no perms!', $player->login);
		return;
	}

	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	$thecol = $command['params'][0];

	// is given name a valid column
	$result = mysql_query('SHOW COLUMNS FROM activity LIKE '.quote($thecol));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$Fo$w$FFF'.$older.' $FF0doesnt exist', $player->login);
		return;
	}

	if($thecol == "login"){
		$aseco->client->query('ChatSendServerMessageToLogin', '$FF0You cant remove the login column', $player->login);
		return;
	}

	$result = mysql_query('ALTER TABLE activity DROP COLUMN '.$thecol);
	if($result){
		$aseco->client->query('ChatSendServerMessageToLogin', '$090Succesfully removed $fff'.$thecol, $player->login);
	}else{
		$aseco->client->query('ChatSendServerMessageToLogin', '$F00something went wrong', $player->login);
	}


}


// displays /laston for each operator in the list

function chat_lastonops($aseco, $command) {
    $admin = $command['author'];

	// rights check
    $canAccess = false;
	if($aseco->isAdminL($admin->login)){
		$canAccess = true;
	}
	if($aseco->isMasterAdminL($admin->login)){
		$canAccess = true;
	}

	if(!$canAccess){
		return;
	}

    $members = $aseco->operator_list['TMLOGIN'];

    
    

    $admin->playerlist = array();
    $admin->msgs = array();

	// makes thw multi windowed manialink
    $head = '$c09/laston on each member';
	$msg = array();
	$lines = 0;
	$admin->msgs[0] = array(1, $head, array(0.9, 0.4, 0.4), array('Icons128x128_1', 'Solo'));
	// foreach ($aseco->operator_list['TMLOGIN'] as $player) {
     foreach ($members as $member) {

        // obtain last online timestamp
        $query = 'SELECT UpdatedAt FROM players
        WHERE login=' . quotedString($member);
        $result = mysql_query($query);
        $laston = mysql_fetch_row($result);
        mysql_free_result($result);
				
					
			$msg[] = array('$3cc'.$member, '$fff'.preg_replace('/:\d\d$/', '', $laston[0]));
			if (++$lines > 19) {
				$admin->msgs[] = $msg;
				$lines = 0;
				$msg = array();
			}
				
	}
	// add if last batch exists
	if (count($msg) > 1)
		$admin->msgs[] = $msg;

	// display ManiaLink message
	if (count($admin->msgs) > 1) {
		display_manialink_multi($admin);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> {#error}No operator(s) found!'), $login);
	}





}


// gets average from db
function getAvgAct($aseco, $login){
    $pid = $aseco->getPlayerId($login);
	$query = 'SELECT avg FROM rs_rank
		          WHERE playerID=' . quote($pid).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['avg'];
}

// gets timespent from db
function getTimeSpent($aseco, $login){
	$query = 'SELECT TimePlayed FROM players
		          WHERE Login=' . quote($login).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['TimePlayed'];
}

// gets mostfinished from db
function getMostFinished($aseco, $login){
    $pid = $aseco->getPlayerId($login);
	$query = 'SELECT mostfinished FROM players_extra
		          WHERE playerID=' . quote($pid).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['mostfinished'];
}

//gets visits from db
function getVisits($aseco, $login){
    $pid = $aseco->getPlayerId($login);
	$query = 'SELECT visits FROM players_extra
		          WHERE playerID=' . quote($pid).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['visits'];
}




// use it to not get lost in mysql queries
function quote($s){
	$r = '"'.$s.'"';
	return $r;
}

?>
