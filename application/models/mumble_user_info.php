<?php
class Mumble_user_info extends Model {

    private $murmurUserID;
    private $murmurUserName;
    private $murmurUserEmail;
    private $murmurUserComment;
    private $murmurUserHash;
    private $murmurUserPassword;
    private $murmurUserLastActive;

    function Mumble_user_info() {
        parent::Model();
    }

    public function getMurmurUserID() {
        return $this->murmurUserID;
    }

    public function setMurmurUserID($murmurUserID) {
        $this->murmurUserID = $murmurUserID;
    }

    public function getMurmurUserName() {
        return $this->murmurUserName;
    }

    public function setMurmurUserName($murmurUserName) {
        $this->murmurUserName = $murmurUserName;
    }

    public function getMurmurUserEmail() {
        return $this->murmurUserEmail;
    }

    public function setMurmurUserEmail($murmurUserEmail) {
        $this->murmurUserEmail = $murmurUserEmail;
    }

    public function getMurmurUserComment() {
        return $this->murmurUserComment;
    }

    public function setMurmurUserComment($murmurUserComment) {
        $this->murmurUserComment = $murmurUserComment;
    }

    public function getMurmurUserHash() {
        return $this->murmurUserHash;
    }

    public function setMurmurUserHash($murmurUserHash) {
        $this->murmurUserHash = $murmurUserHash;
    }

    public function getMurmurUserPassword() {
        return $this->murmurUserPassword;
    }

    public function setMurmurUserPassword($murmurUserPassword) {
        $this->murmurUserPassword = $murmurUserPassword;
    }

    public function getMurmurUserLastActive() {
        return $this->murmurUserLastActive;
    }

    public function setMurmurUserLastActive($murmurUserLastActive) {
        $this->murmurUserLastActive = $murmurUserLastActive;
    }

}
?>
