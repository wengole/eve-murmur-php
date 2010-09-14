<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>EVE Murmur API Registration</title>
    </head>
    <body>
        <?php
            //TODO: Move logic to separate PHP class
	    require_once 'config.php';
            require_once 'classes/Pheal.php';
	    require_once 'Ice.php';
	    require_once 'classes/Murmur_1.2.2.php';
	    
	    // Load Pheal and turn caching on
	    spl_autoload_register("Pheal::classload");
	    PhealConfig::getInstance()->cache = new PhealFileCache($pheal_cache);

            // Connect to MySQL database
            $conn = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
            mysql_select_db($mysql_db, $conn);

	    // We have all requirements, register the user
            if(isset($_POST['password']) && $_POST['password'] == $_POST['password2']) {
		//Intialise ICE
		$initData = new Ice_InitializationData;
		$initData->properties = Ice_createProperties();
		$initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
		$ICE = Ice_initialize($initData);
                // Connect to murmur ICE interface
                $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($ice_proxy));
		// Select virtual server
                $server = $meta->getServer($vserverid);
		// Build userInfo array encrypting the password before giving it to murmur
                $userinfo = array($_POST['username'],null,null,null,sha1($_POST['password']));
                try {
                    $murmur_userid = $server->registerUser($userinfo);
                    echo 'Successfully registered '.$_POST['username'].'<br />
                          Please connect to: '.$server->getConf('host').'<br />
                          Port: '.$server->getConf('port').'<br />';
                } catch (Murmur_ServerBootedException $exc) {
                    echo 'Server not running.<br />';
                } catch (Murmur_InvalidSecretException $exc) {
                    echo 'Wrong ICE secret.<br />';
                } catch (Murmur_InvalidUserException $exc) {
                    echo 'Username already exists.<br />';
                }
                // Save API and returned userID to MySQL database for later cron use
                if(isset($murmur_userid)) {
                    $pheal = new Pheal($_POST['userid'], $_POST['apikey'], "eve");
		    $charname = substr($_POST['username'], strpos($_POST['username'], " ") + 1);
		    $charid = $pheal->CharacterID(array('names' => $charname));
		    $charid = $charid->characters[0]['characterID'];
                    $pheal->scope = "char";
		    $charsheet = $pheal->CharacterSheet(array('characterID' => $charid));
                    $pheal->scope = "corp";
                    $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                    $qry = "INSERT INTO users VALUES (".$murmur_userid.",".$_POST['userid'].",'".$_POST['apikey']."',".
			$charid.",".$charsheet->corporationID.",".$corpsheet->allianceID.")
			ON DUPLICATE KEY UPDATE eveCharID = $charid, eveCorpID = $charsheet->corporationID, eveAllyID = $corpsheet->allianceID";
		    if(!mysql_query($qry, $conn)) {
                        echo 'Failed to INSERT into database.<br />';
                    }
                } else {
                    echo 'Failed to add registration.<br />';
                }
	    } else {
		// Grab API Key from <form>
		if(isset($_POST['userid']))
			$userID = $_POST['userid'];
		if(isset($_POST['apikey']))
			$apiKey = $_POST['apikey'];
		echo "<form method='POST'>
			<table border='0'>
			    <tr>
				<td>UserID:</td>
				<td><input type='text' name='userid' value='$userID' size='12' /></td>
			    </tr>
			    <tr>
				<td>API Key:</td>
				<td><input type='text' name='apikey' value='$apiKey' size='12' /></td>";

		// Create Pheal instance to grab characters on account
		// Loop through characters and output required info
		// Verify characters are in Brick before proceeding
		// Output info as <select><option>'s
		if(isset($userID) && isset($apiKey)) {
		    $pheal = new Pheal($userID, $apiKey);
		    $characters = $pheal->Characters();

		    // TODO: Add toggle for alliance/corp to config and change check accordingly
		    $uname_array = array();
		    foreach($characters->characters as $character) {
			$pheal->scope = "char";
			$charsheet = $pheal->CharacterSheet(array('characterID' => $character->characterID));
			$pheal->scope = "corp";
			$corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                        switch ($corpOnly) {
                            case 1:
                                if($corpsheet->corporationID == $corpID)
                                $uname_array[] = "[".$corpsheet->ticker."]"." ".$character->name;
                                break;
                            case 0:
                                if($corpsheet->allianceID == $allianceID)
                                $uname_array[] = "[".$corpsheet->ticker."]"." ".$character->name;
                                break;
                        }
		    }
		    if(!empty($uname_array)){
			echo "<tr>
				<td>Pick user:</td>
				<td><select name='username'>";
			foreach($uname_array as $username) {
			    echo "<option>$username";
			}
			echo "</select></td>";
		    } else {
			// TODO: Change hardcoded "BricK" to be the corp/allaince name as set in config.php
                        echo '<tr>
				<td colspan=2 align="center"><font color="red">No BricK characters on account!</font></td>
			      </tr>';
		    }
		}
		if(isset($_POST['username']) || $_POST['password'] != $_POST['password2']) {
		    if($_POST['password'] != $_POST['password2'])
			echo '<tr>
				<td colspan=2 align="center"><font color="red">Passwords do not match</font></td>
			      </tr>';
		    echo '<tr>
			    <td>Password:</td>
			    <td><input type="password" name="password" value="" size="12" /></td>
			  </tr>';
		    echo '<tr>
			    <td>Confirm:</td>
			    <td><input type="password" name="password2" value="" size="12" /></td>
			  </tr>';
		}
		echo "<tr>
			<td colspan=2 align='center'><input type='submit' value='Submit' /></td>
		    </form>";
	    }
        ?>
    </body>
</html>