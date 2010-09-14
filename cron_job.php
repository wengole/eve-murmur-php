<?php
    require_once 'config.php';
    require_once 'Ice.php';
    require_once 'classes/Pheal.php';
    require_once 'classes/Murmur_1.2.2.php';

    // Initialise ICE, connect to Murmur proxy and get virtual server
    $initData = new Ice_InitializationData;
    $initData->properties = Ice_createProperties();
    $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
    $ICE = Ice_initialize($initData);
    $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($ice_proxy));
    $server = $meta->getServer($vserverid);

    // Load Pheal, turn caching on and create instance
    spl_autoload_register("Pheal::classload");
    PhealConfig::getInstance()->cache = new PhealFileCache($pheal_cache);

    // Connect to MySQL database
    $conn = mysql_connect($mysql_host, $mysql_user, $mysql_pass);
    mysql_select_db($mysql_db, $conn);

    // Get array of registered API users from database
    $qry = 'SELECT murmurUserID, eveUserID, eveApiKey from users;';
    $result = mysql_query($qry);
    while($row = mysql_fetch_assoc($result)) {
        $pheal = new Pheal($row['eveUserID'], $row['eveApiKey'], "eve");
        $murmurUserID = array((int)$row['murmurUserID']);
        $charname = $server->getUserNames($murmurUserID);
        $charname = substr($charname[$row['murmurUserID']], strpos($charname[$row['murmurUserID']], " ") + 1);
        $charid = $pheal->CharacterID(array('names' => $charname));
        $charid = $charid->characters[0]['characterID'];
        $pheal->scope = "char";
	$charsheet = $pheal->CharacterSheet(array('characterID' => $charid));
	$pheal->scope = "corp";
	$corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
        echo "Character: ".$charname."\n";
        echo "Corporation ID: ".$charsheet->corporationID."\n";
        echo "Alliance ID: ".$corpsheet->allianceID."\n";
    }
?>