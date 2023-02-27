<?php
/*
Written by Novertyhhak.
Feel free to edit this plugin to your needs, just credit me :)

README file at - https://github.com/Novertyhhak/tmnf-activities-plugin

*/

Aseco::registerEvent('onStartup',   				'activities_startup');
Aseco::addChatCommand('activities', 				'manages activities commands');
Aseco::addChatCommand('listassist', 				'manages listassist commands');

global $activities_list, $activities_config, $activities_github, $activities_modetostring;


// read Configuration
if (!$activities_config = simplexml_load_file('activities.xml')) {
	trigger_error('[plugin.activities.php] Could not read/parse config file "activities.xml"!', E_USER_ERROR);
}


// add abbrev commands
if($activities_config->abbrev == 'true'){
	Aseco::console('[plugin.activities.php] adding abbrev commands, if it fails after this point, disable abbrev commands');
	
	Aseco::addChatCommand('acv', 'abbrev for activities');
	function chat_acv($aseco, $command){ chat_activities($aseco, $command); }
	
	Aseco::addChatCommand('la', 'abbrev for listassist');
	function chat_la($aseco, $command){ chat_listassist($aseco, $command); }
}




// is called at the start
function activities_startup($aseco, $command){
	global $activities_list, $activities_config, $activities_github, $activities_modetostring;
    $activities_github = "github.com/Novertyhhak/tmnf-activities-plugin";

	// load list according to the mode
	$activities_modetostring = 'NO';
	switch($activities_config->mode){
		case 0:
			$activities_list = file($activities_config->thelist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			$activities_modetostring = 'LIST';
			if(count($activities_list)==0) $empty = true;
			if(($activities_list == false) && ($empty == false)){
				$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with reading the list file', $player->login);
        		return;
			}
			break;
		case 1:
			$activities_list = $aseco->operator_list['TMLOGIN'];
			$activities_modetostring = 'OPERATORS';
			break;
		case 2:
			$activities_list = array();
			$result = mysql_query('SELECT login FROM players');
			while ($row = mysql_fetch_array($result)) {
				$activities_list[] = $row['login'];
			}
			$activities_modetostring = 'EVERYONE';
			break;
	}

	if($activities_modetostring == 'NO'){
		$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFthere is not such mode as '. $activities_config->mode .'! fix in activities.xml');
		trigger_error('[plugin.activities.php] There is not such mode as '. $activities_config->mode .'! Fix in activities.xml', E_USER_ERROR);
	}

	$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFMode: $F00'.$activities_modetostring);
	$aseco->console('[plugin.activities.php] Mode: '.$activities_modetostring);
	
	$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFList loaded with $F00 '. count($activities_list). '$FFF people');
	$aseco->console('[plugin.activities.php] List loaded with  '.count($activities_list).' people');

	// checks if activities table exists in db, if not just creates it
	$result = mysql_query('SHOW TABLES LIKE "activities"');
	if(mysql_num_rows($result)==0 || !mysql_num_rows($result)){
		$aseco->client->query('ChatSendServerMessage', '$93F[activities] $F6Factivities $FFFtable doesnt exist, will create it');
		mysql_free_result($result);
		$result = mysql_query('CREATE TABLE activities (justremoveit varchar(100))');
		if($result){
			$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFcreated $F6Factivities $ffftable');
			$aseco->console('[plugin.activities.php] successfully created activities table');
		}else{
			$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFsomething went wrong with creating $F6Factivities $ffftable, the plugin won\'t work');
			$aseco->console('[plugin.activities.php] couldn\'t create activities table');
		}
		mysql_free_result($result);
	}

	// checks if login col exists and if not, creates it
	$result = mysql_query('SHOW COLUMNS FROM activities LIKE "login"');
	$logincolexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$logincolexists){
		$result = mysql_query('ALTER TABLE activities ADD login VARCHAR(100)');
		if($result){
			$aseco->console('[plugin.activities.php] successfully created login column');
		}else{
			$aseco->console('[plugin.activities.php] couldn\'t create login column');
		}
	}

	// checks if backup folder exists, and if not creates it
	if (!is_dir($activities_config->listbackupfolder)) {
		$success2 = mkdir($activities_config->listbackupfolder);
		if(!$success2){
			$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFsomething went wrong with creating $F6F'.$activities_config->listbackupfolder.' $ffffolder');
			$aseco->console('[plugin.activities.php] couldn\'t create the list backup folder');
		}else{
			$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFsuccessfully created the list backup folder: $F6F'.$activities_config->listbackupfolder);
			$aseco->console('[plugin.activities.php] created the list backup folder: '.$activities_config->listbackupfolder);
		}
	}

	// a few notes
	if($activities_config->abbrev == 'true'){
		$aseco->client->query('ChatSendServerMessage', '$93F[activities] $F6Fabbrev $FFFcommands enabled');
	}
	if($activities_config->autoreloadlist == 'true'){
		$aseco->client->query('ChatSendServerMessage', '$93F[activities] $F6Fautoreload $FFF enabled');
	}
}





// handles all /activities commands
function chat_activities($aseco, $command){
	$player = $command['author'];
	$tempcommand['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	
	// args check
	if($tempcommand['params'][0] == 'save'){
		activities_save($aseco, $command);
	}elseif($tempcommand['params'][0] == 'compare'){
		activities_compare($aseco, $command);
	}elseif($tempcommand['params'][0] == 'timestamps'){
		activities_timestamps($aseco, $command);
	}elseif($tempcommand['params'][0] == 'remove'){
		activities_remove($aseco, $command);
	}elseif($tempcommand['params'][0] == 'laston'){
		activities_laston($aseco, $command);
	}elseif($tempcommand['params'][0] == 'list'){
		activities_list($aseco, $command);
	}else{
        activities_help($aseco, $command, true);
    }
}




// displays help (actually just directs you to my github haha)
function activities_help($aseco, $command, $fromActivities){
	global $activities_config, $activities_github;

	$player = $command['author'];

    if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->help)){ return; }

	// sending help
	if($fromActivities){
		$aseco->client->query('ChatSendServerMessageToLogin', '$F6F/activities $FFFarg commands: ', $player->login);
		$aseco->client->query('ChatSendServerMessageToLogin', '$FFFsave, compare, timestamps, remove, laston, list', $player->login);
	}else{
		$aseco->client->query('ChatSendServerMessageToLogin', '$F6F/listassist $FFFarg commands: ', $player->login);
		$aseco->client->query('ChatSendServerMessageToLogin', '$FFFadd, remove, clear, reload, backup, load, list, listbackups', $player->login);
	}

	// making manialink window
    $header = '$93F[activities] $FFFhelp';
	$help = array();
	if($fromActivities) $help[] = array('$FFFYour command: $ccc/activities '.$command['params']);
	else $help[] = array('$FFFYour command: $ccc/listassist '.$command['params']);
    $help[] = array(' ');
    $help[] = array('$FFFIf you need help, check plugin\'s github:');
    $help[] = array('$F6F$L'.$activities_github);

	display_manialink($player->login, $header, array('BgRaceScore2', 'Warmup'), $help, array(1.0, 0.05), 'OK');
}




// a command to save stats to the activities table
function activities_save($aseco, $command){
	global $activities_list, $activities_config;

	$player = $command['author'];
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->save)){ return; }

	// args check
	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing a parameter: $F6F<colname>', $player->login);
		return;
	}

	// check if given column name already exists
	$newcolname = $command['params'][1];
	$result = mysql_query('SHOW COLUMNS FROM activities LIKE '.acv_quote($newcolname));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if($colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$newcolname.' $FFFalready exists', $player->login);
		return;
	}

	// create the column
	$success = mysql_query('ALTER TABLE activities ADD '.$newcolname.' VARCHAR(100)');
	if($success){
		$aseco->console('[plugin.activities.php] created '.$newcolname.' column'); 
	}else{
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFcolumn name can\'t be $F6F'.$newcolname, $player->login);
		
		// cases when a column name can't be named as
		if(acv_str_contains($newcolname, '-')){
			$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFdo not use dashes, use $F6F_ $FFFinstead', $player->login);
			return;
		}
		if(is_numeric($newcolname)){
			$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFdon\'t use numbers as the column name', $player->login);
			return;
		}
		
		return;
	}

	// loops thru the list and gets players info
	foreach ($activities_list as $member) {
		$avg = activities_getAvg($aseco, $member);
		$timespent = activities_getTimeSpent($aseco, $member);
		$mostfinished = activities_getMostFinished($aseco, $member);
		$visits = activities_getVisits($aseco, $member);

		$adata = $avg.' '.$timespent.' '.$mostfinished.' '.$visits;

		// if there is no row with such a login, creates it and inserts the data
		// if there is, updates the row
		$result = mysql_query('SELECT * FROM activities WHERE login='.acv_quote($member));
		$rowexists = (mysql_num_rows($result))?TRUE:FALSE;
		if($rowexists){
			mysql_query('UPDATE activities set '.$newcolname.'='.acv_quote($adata).' WHERE login='.acv_quote($member));
		}else{
			mysql_query('INSERT INTO activities (login, '.$newcolname.') VALUES ('.acv_quote($member).', '.acv_quote($adata).')');
		}
	}
	$aseco->console('[plugin.activities.php] '.$player->login.' saved activities data to '.$newcolname);

	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully saved data to $F6F'.$newcolname, $player->login);

	// removes a testing column if it still exists
	$result = mysql_query('SHOW COLUMNS FROM activities LIKE "justremoveit"');
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){ return; }
	$result = mysql_query('ALTER TABLE activities DROP COLUMN justremoveit');
}




// compares two columns of data (timestamps)
function activities_compare($aseco, $command){
	global $activities_list, $activities_config;

	$player = $command['author'];
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->compare)){ return; }

	// args check
	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing parameters: $F6F<oldercol> <newercol>', $player->login);
		return;
	}
	if($command['params'][2] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing second parameter: $F6F<newercol>', $player->login);
		return;
	}

	$older = $command['params'][1];
	$newer = $command['params'][2];

	// checks if given column names are valid
	$result = mysql_query('SHOW COLUMNS FROM activities LIKE '.acv_quote($older));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$older.' $FFFdoesn\'t exist', $player->login);
		return;
	}

	$result = mysql_query('SHOW COLUMNS FROM activities LIKE '.acv_quote($newer));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$newer.' $FFFdoesn\'t exist', $player->login);
		return;
	}

	// makes a multi windowed manialink
	$player->playerlist = array();
    $player->msgs = array();

	$head = '$93F[activities] $FFFcompare $F6F'.$older.' $FFFand $F6F'.$newer;
	$msg = array();
	$lines = 0;
	$player->msgs[0] = array(1, $head, array(1.0, 0.3, 0.14, 0.14, 0.14, 0.14, 0.14), array('Icons128x128_1', 'Solo'));

	$msg[] = array('$93CLOGIN', '$93CCUR AVG', '$93CIMPR AVG', '$93CTIME(H)', '$93CMOSTFIN', '$93CVISITS');

	// loops thru the list (members)
    foreach ($activities_list as $member){
		$canGo = true;
		$pid = $aseco->getPlayerId($login);

		// current average
		$curavg = activities_getAvg($aseco, $member);
		if(is_numeric($curavg)){
			$curavg = sprintf("%4.2F", $curavg / 10000);
		}

		// gets data for login
		$result = mysql_query('SELECT * FROM activities WHERE login='.acv_quote($member).'');
		if (mysql_num_rows($result) > 0) {
			$row = mysql_fetch_array($result);
		}else{
			$canGo = false;
		}

		// older column
		$data_o = explode(' ', $row[$older]);
		if(count($data_o) < 4){
			$canGo = false;
		}

		// newer column
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
					
			// format to human likings
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
		display_manialink_multi($player);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('$93F[activities] $FFFthe list is empty'), $player->login);
	}
}




// returns avaible columns to compare to
function activities_timestamps($aseco, $command) {
	global $activities_config;

	$player = $command['author'];

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->timestamps)){ return; }

    // makes a multi windowed manialink
    $player->playerlist = array();
    $player->msgs = array();

    $head = '$93F[activities] $FFFtimestamps';
	$msg = array();
	$lines = 0;
	$player->msgs[0] = array(1, $head, array(1.0, 1.0), array('Icons128x128_1', 'Solo'));

    $result = mysql_query('SHOW COLUMNS FROM activities');
    if(!$result){
        $aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong and there are no columns', $player->login);
        return;
    }

    $msg[] = array('$F6FTimestamps $FFFin activities table:');
    $msg[] = array(' ');
    while($row = mysqli_fetch_array($result)){
		if(($row['Field']!="login") && ($row['Field']!="justremoveit")){

            $msg[] = array('$ccc'.$row['Field']);
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
		display_manialink_multi($player);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('$93F[activities] $FFFno players on the list with valid stats'), $player->login);
	}
}




// removes a column with a specific name
function activities_remove($aseco, $command) {
	global $activities_config;

	$player = $command['author'];
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->remove)){ return; }

	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing parameter: $F6F<colname>', $player->login);
		return;
	}

	$thecol = $command['params'][1];

	// is given name a valid column
	$result = mysql_query('SHOW COLUMNS FROM activities LIKE '.acv_quote($thecol));
	$colexists = (mysql_num_rows($result))?TRUE:FALSE;
	if(!$colexists){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$thecol.' $FFFdoesn\'t exist', $player->login);
		return;
	}

	// login column is reserved for plugins purposes
	if($thecol == "login"){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFyou can\'t remove $F6Flogin $FFFcolumn', $player->login);
		return;
	}

	// remove the column
	$result = mysql_query('ALTER TABLE activities DROP COLUMN '.$thecol);

	if(!$result){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with removing the column', $player->login);
		return;
	}

	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully removed $F6F'.$thecol, $player->login);
	$aseco->console('[plugin.activities.php] '.$player->login.' removed '.$thecol.' column');
}




// /laston but for each player in the list
function activities_laston($aseco, $command){
	global $activities_list, $activities_config;

    $player = $command['author'];

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->laston)){ return; }

	// makes thw multi windowed manialink
    $player->playerlist = array();
    $player->msgs = array();

    $head = '$93F[activities] $FFFlast connection date of players';
	$msg = array();
	$lines = 0;
	$player->msgs[0] = array(1, $head, array(0.9, 0.4, 0.4), array('Icons128x128_1', 'Solo'));
	
	$msg[] = array(' ', ' ');
    foreach ($activities_list as $member) {

        // obtain last online timestamp
        $query = 'SELECT UpdatedAt FROM players
        WHERE login=' . acv_quote($member);
        $result = mysql_query($query);
        $laston = mysql_fetch_row($result);
        mysql_free_result($result);
		if(!$laston[0]) $date = '$F69never connected';
		else $date = '$9F6'.preg_replace('/:\d\d$/', '', $laston[0]);
						
		$msg[] = array('$99F'.$member, $date);
		if (++$lines > 19) {
			$player->msgs[] = $msg;
			$lines = 0;
			$msg = array();
		}		
	}

	// add if last batch exists
	if (count($msg) > 1)
		$player->msgs[] = $msg;

	// display ManiaLink message
	if (count($player->msgs) > 1) {
		display_manialink_multi($player);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFthe list is empty', $player->login);
	}
}



// /laston but for each player in the list
function activities_list($aseco, $command){
	global $activities_list, $activities_config, $activities_modetostring;

	$player = $command['author'];

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->list)){ return; }

	// makes a multi windowed manialink
    $player->playerlist = array();
    $player->msgs = array();

    $head = '$93F[activities] $FFFlist   $CCC/   $FFFMode: $F6F'.$activities_modetostring;
	$msg = array();
	$lines = 0;
	$player->msgs[0] = array(1, $head, array(1.0, 0.05, 0.4, 0.05, 0.4), array('Icons128x128_1', 'Solo'));

	$msg[] = array(' ', ' ', ' ', ' ');
	$i = 1;
	foreach($activities_list as $member){ 
		$msg[] = array('$99f'.$i.'.', '$fff'.$member, ' ', $aseco->getPlayerNick($member));
		$i++;
		if (++$lines > 19) {
			$player->msgs[] = $msg;
			$lines = 0;
			$msg = array();
		}
	}

    // add if last batch exists
	if (count($msg) > 1){
		$player->msgs[] = $msg;
	}

    // display ManiaLink message		
	if (count($player->msgs) > 1) {
		// $aseco->client->query('ChatSendServerMessageToLogin', '$fffThe manialink should be sent to you.', $player->login);
		display_manialink_multi($player);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('$93F[activities] $FFFthe list is empty!'), $player->login);
	}
}




// manages listassist commands
function chat_listassist($aseco, $command){
    global $activities_config, $activities_modetostring;

    $player = $command['author'];
    $tempcommand['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));

	if(!activities_permissionCheck($aseco, $player, $activities_config->permissions->listassist)){ return; }

	// make sure we are in LIST mode, 
	// otherwise the list might behave in a very weird way aka it will turn into LIST mode, even tho the mode is set to something other
	if($activities_config->mode != 0){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFdon\'t use $F6F/listassist $FFFin $F00'.$activities_modetostring.' $FFFmode', $player->login);
        return;
	}

	// args check
	if($tempcommand['params'][0] == ''){
		activities_help($aseco, $command, false);
	}elseif($tempcommand['params'][0] == 'add'){
        listassist_add($aseco, $command);
    }elseif($tempcommand['params'][0] == 'remove'){
        listassist_remove($aseco, $command);
    }elseif($tempcommand['params'][0] == 'clear'){
        listassist_clear($aseco, $command);
    }elseif($tempcommand['params'][0] == 'reload'){
        listassist_reload($aseco, $command);
    }elseif($tempcommand['params'][0] == 'backup'){
        listassist_backup($aseco, $command);
    }elseif($tempcommand['params'][0] == 'load'){
        listassist_load($aseco, $command);
    }elseif($tempcommand['params'][0] == 'list'){
        listassist_list($aseco, $command);
    }elseif($tempcommand['params'][0] == 'listbackups'){
        listassist_listbackups($aseco, $command);
    }else{
		activities_help($aseco, $command, false);
	}
}




// adds a login to the list
function listassist_add($aseco, $command){
    global $activities_list, $activities_config;

    $player = $command['author'];
    $command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
    $newlogin = $command['params'][1];

    if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing parameter: $F6F<login>', $player->login);
        return;
    }
	
	// reads the list file as array
	$templist = file($activities_config->thelist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if(count($templist)==0) $empty = true;
	if(($templist == false) && ($empty == false)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with reading the list file', $player->login);
        return;
	}

	// checks if the new login is a duplicate
    foreach ($templist as $string) {
        if($string == $newlogin){
            $aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$newlogin.' $FFFalready exists in the list', $player->login);
            return;
        }
    }

	// writes the new login to the list
    $templist[] = $newlogin;
    $tempfile = fopen($activities_config->thelist, 'w');
    foreach ($templist as $string) {
        $success = fwrite($tempfile, $string . "\n");
    }
    fclose($tempfile);

	if(!$success){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with writing the list', $player->login);
        return;
	}

    $aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully added $F6F'.$newlogin.' $FFFto the list', $player->login);
	$aseco->console('[plugin.activities.php] '.$player->login.' added '.$newlogin.' to the list');

	if($activities_config->autoreloadlist == 'true'){
		$aseco->console("got here");
		listassist_reload($aseco, $command);
	}
}




// removes a login from the list
function listassist_remove($aseco, $command){
	global $activities_config;

	$player = $command['author'];
    $command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
    $toremove = $command['params'][1];

	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing parameter: $F6F<login>', $player->login);
        return;
    }

	// reads the list file as array
	$templist = file($activities_config->thelist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if(count($templist)==0) $empty = true;
	if(($templist == false) && ($empty == false)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with reading the list file', $player->login);
        return;
	}

	$index = array_search($toremove, $templist);

	if(!$index && ($index != 0)){ // cant be (!index) cuz index = 0 and if-statement will work
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$toremove.' $FFFdoesn\'t exist in the list', $player->login);
        return;
	}

	unset($templist[$index]);

	// write the list
	$success = file_put_contents($activities_config->thelist, implode(PHP_EOL, $templist));
	if(!$success){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with writing the list', $player->login);
        return;
	}

	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully removed $F6F'.$toremove.' $FFFfrom the list', $player->login);
	$aseco->console('[plugin.activities.php] '.$player->login.' removed '.$toremove.' from the list');

	if($activities_config->autoreloadlist == 'true'){
		listassist_reload($aseco, $command);
	}
}




// clears the list
function listassist_clear($aseco, $command){
	global $activities_config;

	$player = $command['author'];

	// 'w' to truncate
	$templist = fopen($activities_config->thelist, 'w');

	if(count($templist)==0) $empty = true;
	if(($templist == false) && ($empty == false)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with reading the list file', $player->login);
        return;
	}

	fclose($templist);
	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully cleared the list', $player->login);
	$aseco->console('[plugin.activities.php] '.$player->login.' cleared the list');

	if($activities_config->autoreloadlist == 'true'){
		listassist_reload($aseco, $command);
	}
}




// reloads the list
function listassist_reload($aseco, $command){
	global $activities_config, $activities_list;

	$player = $command['author'];

	// reads the list file as array
	$temp = file($activities_config->thelist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if(count($temp)==0) $empty = true;
	if(($temp == false) && ($empty == false)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with reading the list file', $player->login);
        return;
	}

	$activities_list = $temp;
	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFreloaded', $player->login);
}




// backups your list in the backup folder
function listassist_backup($aseco, $command){
	global $activities_config;

	$player = $command['author'];
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	
	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing parameter: $F6F<name>', $player->login);
        return;
    }

	// checks if backup folder exists, and if not creates it (should have already been created at the startup)
	if (!is_dir($activities_config->listbackupfolder)) {
		$s = mkdir($activities_config->listbackupfolder);
		if(!$s){
			$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with creating $F6F'.$activities_config->listbackupfolder.' $FFFfolder', $player->login);
		}
	}

	// make the destination string
	$backupdest = $activities_config->listbackupfolder .'/'. $command['params'][1];
	if(!acv_endsWith($backupdest, '.txt')){
		$backupdest = $backupdest.'.txt';
	}
	
	// makes a backup
	$list_contents = file_get_contents($activities_config->thelist);
	if(strlen($list_contents)==0){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFthe list is empty', $player->login);
        return;
	}

	$success = file_put_contents($backupdest, $list_contents);

	if(!$success){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with making a backup', $player->login);
        return;
	}

	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully backuped to $F6F'.$backupdest, $player->login);
	$aseco->console('[plugin.activities.php] '.$player->login.' backuped to '.$backupdest);
}




// loads some other list into the current list
function listassist_load($aseco, $command){
	global $activities_config, $activities_list;
	
	$player = $command['author'];
	$command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
	
	if($command['params'][1] == ''){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFmissing parameter: $F6F<filedest>', $player->login);
        return;
    }

	$dest = $command['params'][1];

	// checks if file exists
	if(!file_exists($dest) && !acv_endsWith($dest, '.txt')){
		$dest = $dest.'.txt';
		if(!file_exists($dest)){
			if(file_exists($activities_config->listbackupfolder.'/'.$dest)){
				$dest = $activities_config->listbackupfolder.'/'.$dest;
				$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$command['params'][1].' $FFFdoesn\'t exist, but it exists in backup folder so I took the other one instead', $player->login);
			}else{
				$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $F6F'.$command['params'][1].' $fffdoesn\'t exist! ', $player->login);
        		return;
			}
		}
	}
	
	// reads the file list as array
	$templist = file($dest, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	if(count($templist)==0) $empty = true;
	if(($templist == false) && ($empty == false)){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with reading the list file', $player->login);
        return;
	}

	// writes list file
	$success = file_put_contents($activities_config->thelist, implode(PHP_EOL, $templist));

	if(!$success){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsomething went wrong with writing the list', $player->login);
        return;
	}
	
	$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFsuccessfully loaded $F6F'.$dest, $player->login);
	$aseco->console('[plugin.activities.php] '.$player->login.' loaded '.$dest);

	if($activities_config->autoreloadlist == 'true'){
		listassist_reload($aseco, $command);
	}
}




// displays the list
function listassist_list($aseco, $command){
	//redirect to activities list
	activities_list($aseco, $command);
}




// lists your backups
function listassist_listbackups($aseco, $command){
	global $activities_list, $activities_config;

	$player = $command['author'];

	// makes a multi windowed manialink
    $player->playerlist = array();
    $player->msgs = array();

    $head = '$93F[activities] $FFFbackup list $CCC/ $FFFfolder: $F6F'.$activities_config->listbackupfolder;
	$msg = array();
	$lines = 0;
	$player->msgs[0] = array(1, $head, array(1.0, 1.0), array('Icons128x128_1', 'Solo'));

	// get all file names in directory
	$files = scandir($activities_config->listbackupfolder);

	$msg[] = array(' ');
	// loop through the array of file names and echo each one
	foreach ($files as $file) {
  		// ignore the "." and ".." directories
  		if ($file != "." && $file != "..") {
    		$msg[] = array('$99f'.$file);
			$i++;
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
		display_manialink_multi($player);
	} else {  // == 1
		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('$93F[activities] $FFFthe backup folder is empty'), $player->login);
	}
}




// checks permission for specific commands
function activities_permissionCheck($aseco, $player, $perm){
	global $activities_config;

	switch($perm){
		case 0:
			if($aseco->isMasterAdminL($player->login)) $canAccess = true;
			break;
		case 1:
			if($aseco->isMasterAdminL($player->login)) $canAccess = true;
			if($aseco->isAdminL($player->login)) $canAccess = true;
			break;
		case 2:
			if($aseco->isAnyAdminL($player->login)) $canAccess = true;
			break;
	}
	if(!(($perm == 0) || ($perm == 1) || ($perm == 2))){
		$aseco->client->query('ChatSendServerMessage', '$93F[activities] $FFFthere is not such permission as $F6F'. $perm .'! $FFFfix in activities.xml');
		trigger_error('[plugin.activities.php] There is not such permission as '. $perm .'! Fix in activities.xml', E_USER_ERROR);
	}
	if(!$canAccess){
		$aseco->client->query('ChatSendServerMessageToLogin', '$93F[activities] $FFFno permission to this command!', $player->login);
		return false;
	}
	return true;

}


// gets average from db
function activities_getAvg($aseco, $login){
    $pid = $aseco->getPlayerId($login);
	$query = 'SELECT avg FROM rs_rank
		          WHERE playerID=' . acv_quote($pid).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['avg'];
}


// gets timespent from db
function activities_getTimeSpent($aseco, $login){
	$query = 'SELECT TimePlayed FROM players
		          WHERE Login=' . acv_quote($login).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['TimePlayed'];
}


// gets mostfinished from db
function activities_getMostFinished($aseco, $login){
    $pid = $aseco->getPlayerId($login);
	$query = 'SELECT mostfinished FROM players_extra
		          WHERE playerID=' . acv_quote($pid).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['mostfinished'];
}


//gets visits from db
function activities_getVisits($aseco, $login){
    $pid = $aseco->getPlayerId($login);
	$query = 'SELECT visits FROM players_extra
		          WHERE playerID=' . acv_quote($pid).'';
	$res = mysql_query($query);
	$row = -1;
	if (mysql_num_rows($res) > 0) {
		$row = mysql_fetch_array($res);	
	}
	return $row['visits'];
}


// a little helper :)
function acv_quote($s){
	$r = '"'.$s.'"';
	return $r;
}

// pre php 8
function acv_endsWith($haystack, $needle) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}

// pre php 8
function acv_str_contains($str, $substring) {
    return strpos($str, $substring) !== false;
}

?>
