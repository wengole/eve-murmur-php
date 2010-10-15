<?php
/**
 * Description of EveMurmurUser
 *
 * @author ben
 */
class User extends Model {

    private $eveUserID;
    private $eveApiKey;
    private $eveCharID;
    private $eveCorpID;
    private $eveAllyID;
    private $regPassword;
    private $regUsername;
    private $lastUpdateTime;
    private $createTime;
    private $regCert;
    private $murmurUserID;

    function User(  ) {
        parent::Model();
    }

    public function getEveUserID() {
        return $this->eveUserID;
    }

    public function setEveUserID($eveUserID) {
        $this->eveUserID = $eveUserID;
    }

    public function getEveApiKey() {
        return $this->eveApiKey;
    }

    public function setEveApiKey($eveApiKey) {
        $this->eveApiKey = $eveApiKey;
    }

    public function getEveCharID() {
        return $this->eveCharID;
    }

    public function setEveCharID($eveCharID) {
        $this->eveCharID = $eveCharID;
    }

    public function getEveCorpID() {
        return $this->eveCorpID;
    }

    public function setEveCorpID($eveCorpID) {
        $this->eveCorpID = $eveCorpID;
    }

    public function getEveAllyID() {
        return $this->eveAllyID;
    }

    public function setEveAllyID($eveAllyID) {
        $this->eveAllyID = $eveAllyID;
    }

    public function getRegPassword() {
        return $this->regPassword;
    }

    public function setRegPassword($regPassword) {
        $this->regPassword = $regPassword;
    }

    public function getRegUsername() {
        return $this->regUsername;
    }

    public function setRegUsername($regUsername) {
        $this->regUsername = $regUsername;
    }

    public function getLastUpdateTime() {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime($lastUpdateTime) {
        $this->lastUpdateTime = $lastUpdateTime;
    }

    public function getCreateTime() {
        return $this->createTime;
    }

    public function setCreateTime($createTime) {
        $this->createTime = $createTime;
    }

    public function getRegCert() {
        return $this->regCert;
    }

    public function setRegCert($regCert) {
        $this->regCert = $regCert;
    }

    public function getMurmurUserID() {
        return $this->murmurUserID;
    }

    public function setMurmurUserID($murmurUserID) {
        $this->murmurUserID = $murmurUserID;
    }

}
?>
