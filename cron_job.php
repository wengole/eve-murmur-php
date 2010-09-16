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

    // Get array of registered users on Murmur server
    $reg_users = $server->getRegisteredUsers('');
    foreach ($reg_users as $key => $user) {
        $query = "SELECT * FROM users WHERE murmurUserID = $key";
        $result = mysql_query($query);
        while($row = mysql_fetch_array($result)){
            $pheal = new Pheal($row['eveUserID'], $row['eveApiKey'], 'corp');
            $pheal->CorporationSheet(array('corporationID' => $row['eveCorpID']));
            echo $user.' '.$row['eveUserID'].' '.$row['eveApiKey'];
        }
    }
?>