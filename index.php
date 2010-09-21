<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <link href='apipagecss.css' rel='stylesheet' type='text/css'>
        <script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
        <script type="text/javascript" src="js/overlay.js"></script>
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
        if (isset($_POST['password']) && isset($_POST['password2']) && $_POST['password'] == $_POST['password2']) {
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
            $userinfo = array($_POST['username'], null, null, null, sha1($_POST['password']));
              try {
                 $murmur_userid = $server->registerUser($userinfo);
                 $jsText='Successfully registered ' . $_POST['username'] . '<br />
                          Please connect to: ' . $server->getConf('host') . '<br />
                          Port: ' . $server->getConf('port') . '<br />
                          or click <a href="mumble://'.str_replace(" ", "%20", $_POST['username']).':'.$_POST['password'].'@'.$server->getConf('host').':'.$server->getConf('port').'/?version=1.2.0">here</a><br />';
            } catch (Murmur_ServerBootedException $exc) {
                $jsText="<h4>Server not running.</h4>";
            } catch (Murmur_InvalidSecretException $exc) {
                $jsText="<h4>  Wrong ICE secret.</h4>";
            } catch (Murmur_InvalidUserException $exc) {
                $jsText="<h4>Username already exists</h4>";
            }
            echo "show_overlay($jsText)";
            // Save API and returned userID to MySQL database for later cron use
            if (isset($murmur_userid)) {
                $pheal = new Pheal($_POST['userid'], $_POST['apikey'], "eve");
                $charname = substr($_POST['username'], strpos($_POST['username'], " ") + 1);
                $charid = $pheal->CharacterID(array('names' => $charname));
                $charid = $charid->characters[0]['characterID'];
                $pheal->scope = "char";
                $charsheet = $pheal->CharacterSheet(array('characterID' => $charid));
                $pheal->scope = "corp";
                $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                $qry = "INSERT INTO users VALUES (" . $murmur_userid . "," . $_POST['userid'] . ",'" . $_POST['apikey'] . "'," .
                        $charid . "," . $charsheet->corporationID . "," . $corpsheet->allianceID . ")
			ON DUPLICATE KEY UPDATE eveCharID = $charid, eveCorpID = $charsheet->corporationID, eveAllyID = $corpsheet->allianceID";
                if (!mysql_query($qry, $conn)) {
                    echo "<h3>Failed to INSERT into database.</h3>";
                }
            } else {
                echo "<h3>Failed to add registration</h3>";
            }
        } else {
            echo '<div id="apicontent">
		<form method="POST">
			<h1>Mumble Registration</h1>
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
                    $characters = $pheal->Characters();
                }
                $uname_array = array();
                foreach ($characters->characters as $character) {
                    $pheal->scope = "char";
                    $charsheet = $pheal->CharacterSheet(array('characterID' => $character->characterID));
                    $pheal->scope = "corp";
                    $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                    switch ($corpOnly) {
                        case 1:
                            if ($corpsheet->corporationID == $corpID)
                                $uname_array[] = "[" . $corpsheet->ticker . "]" . " " . $character->name;
                            break;
                        default:
                            if ($corpsheet->allianceID == $allianceID)
                                $uname_array[] = "[" . $corpsheet->ticker . "]" . " " . $character->name;
                            break;
                    }
                }
                if (!empty($uname_array)) {
                    echo "<p>Pick User:</p>
			<select id='userselect' name='username'>";
                    foreach ($uname_array as $username) {
                        echo "<option>$username";
                    }
                    echo "</select>";
                } else {
                    switch ($corpOnly) {
                        case 1:
                            $pheal->scope = "eve";
                            $corpName = $pheal->CharacterName(array("ids" => $corpID));
                            "<h3>No characters in $corpNam on account!</h3>";
                            break;
                        default:
                            $pheal->scope = "eve";
                            $allyName = $pheal->CharacterName(array("ids" => $allianceID));
                            "<h3>No characters in $allyName on account!</h3>";
                            break;
                    }
                }
            }
            if (isset($_POST['username']) || (isset($_POST['password']) && isset($_POST['password2']))) {
                echo "<p>Password:</p>
                        <input type = 'password' id = 'passwordinput' name = 'password' value = ''>";
                echo "<p>Confirm:</p>
                        <input type = 'password' id = 'confirminput' name = 'password2' value = ''>";
                if (isset($_POST['password']) && isset($_POST['password2']))
                        if ($_POST['password'] != $_POST['password2'])
                            echo "<h3>Passwords do not match</h3>";
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
        <a href="#" class="show-overlay">Test Overlay</a>
    </body>
</html>