<?php

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

class Register extends Controller {

    private $reg;
    private $blues;
    private $server;

    function __construct() {
        parent::Controller();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
        $this->server = $meta->getServer($this->config->item('vServerID'));
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
            $this->blues[] = $row['contactID'];
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
        $this->reg->selected_user = $this->input->post('username');
        if ($this->input->post('apikey') != $this->reg->apikey || $this->input->post('userid') != $this->reg->userid
                || (empty($this->reg->uname_array) && !empty($this->reg->userid) && !empty($this->reg->apikey)))
            $this->_getCharacters();
        if (!empty($this->reg->uname_array))
            $this->reg->username = $this->reg->getSelectedUser();
        $this->reg->password = $this->input->post('password');
        $this->reg->password2 = $this->input->post('password2');
        if (preg_match("/^[A-Za-z0-9-._]*\z/", $this->reg->password) && $this->reg->password != ""
                && $this->reg->password == $this->reg->password2) {
            $this->reg->host = $this->server->getConf('host');
            $this->reg->port = $this->server->getConf('port');
            try {
                echo "Getting users\n";
                $reg_users = $this->server->getRegisteredUsers('');
                foreach ($reg_users as $userid => $username) {
                    if (preg_match('/.*' . $this->reg->username . '/', $username))
                        throw new Murmur_InvalidUserException;
                }
                $murmur_userid = $this->server->registerUser($userinfo);
                $data['main_content'] = 'registeredview';
                $data['title'] = 'Mumble Registration';
                $data['data'] = $this->reg;
                $this->load->view('includes/template', $data);
            } catch (Murmur_ServerBootedException $exc) {
                $this->reg->error_message = "<h4>Server not running.</h4>";
                $data['main_content'] = 'registerview';
                $data['title'] = 'Mumble Registration';
                $data['data'] = $this->reg;
                $this->load->view('includes/template', $data);
            } catch (Murmur_InvalidSecretException $exc) {
                $this->reg->error_message = "<h4>Wrong ICE secret.</h4>";
                $data['main_content'] = 'registerview';
                $data['title'] = 'Mumble Registration';
                $data['data'] = $this->reg;
                $this->load->view('includes/template', $data);
            } catch (Murmur_InvalidUserException $exc) {
                $this->reg->error_message = "><h4>Username already exists</h4>";
                $data['main_content'] = 'registerview';
                $data['title'] = 'Mumble Registration';
                $data['data'] = $this->reg;
                $this->load->view('includes/template', $data);
            }
        } else {
            $data['main_content'] = 'registerview';
            $data['title'] = 'Mumble Registration';
            $data['data'] = $this->reg;
            $this->load->view('includes/template', $data);
        }
    }

    function _getCharacters() {
        if (preg_match('/^[0-9]*\z/', $this->reg->userid)) {
            $params = array('userid' => $this->reg->userid, 'key' => $this->reg->apikey);
            $this->load->library('pheal/Pheal', $params);
        } else {
            $params = array('userid' => '123456', 'key' => 'abc123');
            $this->load->library('pheal/Pheal', $params);
        }
        spl_autoload_register("Pheal::classload");
        PhealConfig::getInstance()->cache = new PhealFileCache($this->config->item('phealCache'));
        PhealConfig::getInstance()->log = new PhealFileLog($this->config->item('phealLog'));
        // On API errors switch to using cache files only
        try {
            $this->pheal->scope = 'account';
            $characters = $this->pheal->Characters();
        } catch (Exception $exc) {
            PhealConfig::getInstance()->cache = new PhealFileCacheForced($this->config->item('phealCache'));
            try {
                $characters = $this->pheal->Characters();
            } catch (Exception $exc) {
                // TODO: Error handling for no cache
            }
        }

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
                            $this->reg->addUsername($character->name);
                        echo "Blue to corp";
                        break;
                    default:
                        if ($corpsheet->allianceID == $this->config->item('allianceID') ||
                                in_array($corpsheet->corporationID, $this->blues) || in_array($corpsheet->allianceID, $this->blues))
                            $this->reg->addUsername($character->name);
                        break;
                }
            }
        }
    }

}
