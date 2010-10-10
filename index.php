<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <link href='apipagecssr2.css' rel='stylesheet' type='text/css'>
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
        if (isset($_POST['password']) && isset($_POST['password2']) && $_POST['password'] == $_POST['password2']
                && preg_match("/^[A-Za-z0-9-._]*\z/", $_POST['password'])) {
            //Intialise ICE
            $initData = new Ice_InitializationData;
            $initData->properties = Ice_createProperties();
            $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
            $ICE = Ice_initialize($initData);
            // Connect to murmur ICE interface
            $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($ice_proxy));
            // Select virtual server
            $server = $meta->getServer($vserverid);
            // Build userInfo array
            // Encrypting password doesn't work
            $userinfo = array($_POST['username'], null, null, null, $_POST['password']);
            try {
//                if ("[".$corpsheet->ticker."] ".$username == $username)
//                        throw Murmur_InvalidUserException;
                $murmur_userid = $server->registerUser($userinfo);
                echo '<div id="apicontent"><h2>Successfully registered ' . $_POST['username'] . '</h2>
                    <p>Please connect to: ' . $server->getConf('host') . '</p>
                    <p>Port: ' . $server->getConf('port') . '</p>
                    <p>or click <a href="mumble://' . str_replace(".", "%2E", rawurlencode($_POST['username'])) .
                    ':' . $_POST['password'] . '@' . $server->getConf('host') . ':' . $server->getConf('port') . '/?version=1.2.2">here</a> once you have Mumble installed</p>
                    <p>Once connected go to Server->Connect, hit Add New..., give it a label (e.g. Brick Squad) then click OK to save the connection as a favourite</p>';
            } catch (Murmur_ServerBootedException $exc) {
                echo "<div id='apicontent'><h4>Server not running.</h4></div>";
            } catch (Murmur_InvalidSecretException $exc) {
                echo "<div id='apicontent'><h4>Wrong ICE secret.</h4></div>";
            } catch (Murmur_InvalidUserException $exc) {
                echo "<div id='apicontent'><h4>Username already exists.</h4></div>";
            }

            // Save API and returned userID to MySQL database for later cron use
            // Add new user to appropriate group
            if (isset($murmur_userid)) {
                $pheal = new Pheal($_POST['userid'], $_POST['apikey'], "eve");
                $charid = $pheal->CharacterID(array('names' => $_POST['username']));
                $charid = $charid->characters[0]['characterID'];
                $pheal->scope = "char";
                $charsheet = $pheal->CharacterSheet(array('characterID' => $charid));
                $pheal->scope = "corp";
                $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                $qry = "INSERT INTO users (`murmurUserID`, `eveUserID`, `eveApiKey`, `eveCharID`, `eveCorpID`, `eveAllyID`)
                        VALUES (" . $murmur_userid . "," . $_POST['userid'] . ",'" . $_POST['apikey'] . "'," .
                        $charid . "," . $charsheet->corporationID . "," . $corpsheet->allianceID . ")
			ON DUPLICATE KEY UPDATE eveCharID = $charid, eveCorpID = $charsheet->corporationID, eveAllyID = $corpsheet->allianceID";
                if (!mysql_query($qry, $conn))
                    echo "<h4>Failed to INSERT into database.</h4>";
                $server->getACL(0, $acls, $groups, $inherit);
                foreach ($groups as $group) {
                        if ($group->name == "blues" && $corpsheet->allianceID != $allianceID) {
                            echo "<p>You have been added as a blue</p>";
                            $add = $group->members;
                            $add[] = $murmur_userid;
                            $group->add = $add;
                            $server->setACL(0, $acls, $groups, $inherit);
                            continue;
                        } elseif ($group->name == "admin" && $corpsheet->ceoID == $charid && $corpsheet->allianceID == $allianceID) {
                            echo "<p>You have been added as an admin</p>";
                            $add = $group->members;
                            $add[] = $murmur_userid;
                            $group->add = $add;
                            $server->setACL(0, $acls,$groups, $inherit);
                        }
                }
            }
            echo "</div>";
        } else {
            echo '<div id="apicontent">
                    <form method="POST">
                    <h1>Mumble Registration</h1>
                    <p>1. <a href="http://mumble.sourceforge.net" target="_blank">Download Mumble</a></p>
                    <p>2. <a href="http://www.eveonline.com/api/" target="_blank">Get your limited API Key</a></p>
                    <p>User ID:</p>
                    <input type = "text" id="useridinput" name="userid" value="';
            if (isset($_POST['userid']))
                echo $_POST['userid'];
            echo '">
                <p>Limited API:</p>
		<input type = "text" id="apiinput" name="apikey" value="';
            if (isset($_POST['apikey']))
                echo $_POST['apikey'];
            echo '">';

            // Create Pheal instance to grab characters on account
            // Loop through characters and output required info
            // Verify characters are in Brick before proceeding
            // Output info as <select><option>'s
            if (isset($_POST['userid']) && isset($_POST['apikey'])) {
                $pheal = new Pheal($_POST['userid'], $_POST['apikey']);
                // On API errors switch to using cache files only
                try {
                    $characters = $pheal->Characters();
                } catch (Exception $exc) {
                    PhealConfig::getInstance()->cache = new PhealFileCacheForced($pheal_cache);
                    try {
                        $characters = $pheal->Characters();
                    } catch (PhealAPIException $exc) {
                        echo '<h3>You must provide a valid userID and API Key</h3>';
                        unset($_POST['userid']);
                        unset($_POST['apikey']);
                    }
                }
                $uname_array = array();
                $query = "SELECT * FROM contacts WHERE standing > 0;";
                $result = mysql_query($query, $conn);
                $blues = array();
                while ($row = mysql_fetch_array($result)) {
                    $blues[] = $row['contactID'];
                }
                if (isset($characters)) {
                    foreach ($characters->characters as $character) {
                        $pheal->scope = "char";
                        $charsheet = $pheal->CharacterSheet(array('characterID' => $character->characterID));
                        $pheal->scope = "corp";
                        $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                        switch ($corpOnly) {
                            case true:
                                if ($corpsheet->corporationID == $corpID || in_array($corpsheet->corporationID, $blues) || in_array($corpsheet->allianceID, $blues))
                                    $uname_array[] = $character->name;
                                break;
                            default:
                                if ($corpsheet->allianceID == $allianceID || in_array($corpsheet->corporationID, $blues) || in_array($corpsheet->allianceID, $blues))
                                    $uname_array[] = $character->name;
                                break;
                        }
                    }
                    if (!empty($uname_array)) {
                        echo "<p>Pick Character:</p>
			<select id='userselect' name='username'>";
                        foreach ($uname_array as $username) {
                            echo "<option>$username";
                        }
                        echo "</select>";
                    } else {
                        switch ($corpOnly) {
                            case true:
                                $pheal->scope = "eve";
                                $corpName = $pheal->CharacterName(array("ids" => $corpID));
                                $corpName = $corpName->characters[0]['name'];
                                echo "<h3>No characters in or blue to $corpName on account!</h3>";
                                break;
                            default:
                                $pheal->scope = "eve";
                                $allyName = $pheal->CharacterName(array("ids" => $allianceID));
                                $allyName = $allyName->characters[0]['name'];
                                echo "<h3>No characters in or blue to $allyName on account!</h3>";
                                break;
                        }
                    }
                }
            }
            if (isset($_POST['username']) || (isset($_POST['password']) && isset($_POST['password2']))) {
                echo "<p>Password:</p>
                        <input type = 'password' id = 'passwordinput' name = 'password' value = ''>";
                echo "<p>Confirm:</p>
                        <input type = 'password' id = 'confirminput' name = 'password2' value = ''>";
                if (isset($_POST['password']) && isset($_POST['password2']))
                    if ($_POST['password'] != $_POST['password2']) {
                        echo "<h3>Passwords do not match</h3>";
                    } elseif (preg_match("/^[A-Za-z0-9-._]*\z/", $_POST['password']) == 0) {
                        echo "<h3>Only letters, numbers - . _ allowed</h3>";
                    }
            }
            echo "<div class='buttons'>
				<button type='submit' class='positive' name='save' value='Submit'>
				<img src='images/apply2.png' alt=''/>
				Submit
				</button>
			</div>
                        </form>";
        }
        ?>
    </body>
</html>