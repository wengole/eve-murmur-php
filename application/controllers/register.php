<?php

class Register extends Controller {

    private $reg;

    function Register() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        $this->load->library('Registration');
        $this->load->library('Pheal/Pheal.php');
        spl_autoload_register("Pheal::classload");
        PhealConfig::getInstance()->cache = new PhealFileCache($pheal_cache);
        $this->reg = new Registration();
    }

    function index() {
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $this->reg;
        $this->load->view('includes/template', $data);
    }

    function add() {
        $this->reg->userid = $this->input->post('userid');
        $this->reg->apikey = $this->input->post('apikey');
        $this->reg->username = $this->input->post('username');
        if (empty($this->reg->uname_array) && !empty($this->reg->userid) && !empty($this->reg->apikey))
            $this->reg->uname_array = $this->getCharacters();
        if (!empty($this->reg->username))
            $this->reg->selected_user = $this->reg->getSelectedUser();
        $this->reg->password = $this->input->post('password');
        $this->reg->password2 = $this->input->post('password2');
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $this->reg;
        $this->load->view('includes/template', $data);
    }

    function getCharacters() {
        if (preg_match('/^[0-9]*\z/', $this->reg->userid)) {
            $pheal = new Pheal($this->reg->userid, $this->reg->apikey);
        } else {
            $pheal = new Pheal('123456', 'abc123');
        }
        // On API errors switch to using cache files only
        try {
            $characters = $pheal->Characters();
        } catch (Exception $exc) {
            PhealConfig::getInstance()->cache = new PhealFileCacheForced($pheal_cache);
            try {
                $characters = $pheal->Characters();
            } catch (Exception $exc) {
                echo '<h3>You must provide a valid userID and API Key</h3>';
                echo '<h3>User ID is a 6+ digit number<br />API Key is a 64 character string</h3>';
                unset($_POST['userid']);
                unset($_POST['apikey']);
            }
        }
        $uname_array = array();
        $query = "SELECT corpAllianceContactList.contactID FROM corpAllianceContactList
                    JOIN corpCorporationSheet
                    ON corpCorporationSheet.corporationID = corpAllianceContactList.ownerID
                    WHERE corpCorporationSheet.allianceID = $allianceID
                    AND standing > 0;";
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
                    if (isset($_POST['username']) && $username == $_POST['username']) {
                        echo "<option selected='selected'>$username";
                    } else {
                        echo "<option>$username";
                    }
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

}
