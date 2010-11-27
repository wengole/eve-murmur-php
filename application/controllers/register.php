<?php

class Register extends Controller {

    private $reg;
    private $blues;

    function Register() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        $this->load->library('Registration');
        $this->reg = new Registration();
        $this->db->select('corpAllianceContactList.contactID');
        $this->db->from('corpAllianceContactList');
        $this->db->join('corpCorporationSheet', 'corpCorporationSheet.corporationID = corpAllianceContactList.ownerID');
        $this->db->where('corpCorporationSheet.allianceID', $this->config->item('allianceID'));
        $this->db->where('standing >', 0);
        $query = $this->db->get();
        $this->blues = array();
        foreach ($query->result_array() as $row) {
            $blues[] = $row['contactID'];
        }
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
        var_dump($this->reg->uname_array);
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
            $params = array('userid' => $this->reg->userid, 'key' => $this->reg->apikey);
            $this->load->library('pheal/Pheal', $params);
        } else {
            $params = array('userid' => '123456', 'key' => 'abc123');
            $this->load->library('pheal/Pheal', $params);
        }
        spl_autoload_register("Pheal::classload");
        // On API errors switch to using cache files only
        try {
            $characters = $this->pheal->Characters();
        } catch (Exception $exc) {
            PhealConfig::getInstance()->cache = new PhealFileCacheForced($this->config->item('phealCache'));
            try {
                $characters = $this->pheal->Characters();
            } catch (Exception $exc) {
                var_dump($params);
                echo $exc->getMessage()."<br />".$exc->getTraceAsString();
            }
        }
        //var_dump($characters);
        if (isset($characters)) {
            foreach ($characters->characters as $character) {
                $this->pheal->scope = "char";
                $charsheet = $this->pheal->CharacterSheet(array('characterID' => $character->characterID));
                $this->pheal->scope = "corp";
                $corpsheet = $this->pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                switch ($this->config->item('corpOnly')) {
                    case true:
                        if ($corpsheet->corporationID == $this->config->item('corpID') ||
                                in_array($corpsheet->corporationID, $this->blues) || in_array($corpsheet->allianceID, $this->blues))
                            $this->reg->uname_array[] = $character->name;
                        break;
                    default:
                        if ($corpsheet->allianceID == $this->config->item('allianceID') ||
                                in_array($corpsheet->corporationID, $this->blues) || in_array($corpsheet->allianceID, $this->blues))
                            $this->reg->uname_array[] = $character->name;
                        break;
                }
            }
        }
    }

}
