<?php
/**
 * Contains CharacterSheet class.
 *
 * PHP version 5
 *
 * LICENSE: This file is part of Yet Another Php Eve Api library also know
 * as Yapeal which will be used to refer to it in the rest of this license.
 *
 *  Yapeal is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Yapeal is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with Yapeal. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Michael Cummings <mgcummings@yahoo.com>
 * @copyright  Copyright (c) 2008-2010, Michael Cummings
 * @license    http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @package    Yapeal
 * @link       http://code.google.com/p/yapeal/
 * @link       http://www.eve-online.com/
 */
/**
 * @internal Allow viewing of the source code in web browser.
 */
if (isset($_REQUEST['viewSource'])) {
  highlight_file(__FILE__);
  exit();
};
/**
 * @internal Only let this code be included or required not ran directly.
 */
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  exit();
};
/**
 * Class used to fetch and store CharacterSheet API.
 *
 * @package Yapeal
 * @subpackage Api_char
 */
class charCharacterSheet  extends AChar {
  /**
   * Constructor
   *
   * @param array $params Holds the required parameters like userID, apiKey, etc
   * used in HTML POST parameters to API servers which varies depending on API
   * 'section' being requested.
   *
   * @throws LengthException for any missing required $params.
   */
  public function __construct(array $params) {
    parent::__construct($params);
    $this->api = str_replace($this->section, '', __CLASS__);
  }// function __construct
  /**
   * Per API parser for XML.
   *
   * @return bool Returns TRUE if XML was parsered correctly, FALSE if not.
   */
  protected function parserAPI() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . $this->api;
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    $qb->setDefault('allianceName', '');
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      // Empty out old data then upsert (insert) new
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `characterID`=' . $this->params['characterID'];
      $con->Execute($sql);
      while ($this->xr->read()) {
        switch ($this->xr->nodeType) {
          case XMLReader::ELEMENT:
            switch ($this->xr->localName) {
              case 'allianceID':
              case 'allianceName':
              case 'ancestry':
              case 'balance':
              case 'bloodLine':
              case 'characterID':
              case 'cloneName':
              case 'cloneSkillPoints':
              case 'corporationID':
              case 'corporationName':
              case 'gender':
              case 'name':
              case 'race':
                // Grab node name.
                $name = $this->xr->localName;
                if ($name == 'allianceName' && $this->xr->isEmptyElement == TRUE) {
                  $row[$name] = '';
                } else {
                  // Move to text node.
                  $this->xr->read();
                  $value = $this->xr->value;
                  $row[$name] = $this->xr->value;
                };
                break;
              case 'attributes':
              case 'attributeEnhancers':
                // Check if empty.
                if ($this->xr->isEmptyElement == 1) {
                  break;
                };// if $this->xr->isEmptyElement ...
                // Grab node name.
                $subTable = $this->xr->localName;
                // Check for method with same name as node.
                if (!is_callable(array($this, $subTable))) {
                  $mess = 'Unknown what-to-be rowset ' . $subTable;
                  $mess .= ' found in ' . $this->api;
                  trigger_error($mess, E_USER_WARNING);
                  return FALSE;
                };
                $this->$subTable();
                break;
              case 'rowset':
                // Check if empty.
                if ($this->xr->isEmptyElement == 1) {
                  break;
                };// if $this->xr->isEmptyElement ...
                // Grab rowset name.
                $subTable = $this->xr->getAttribute('name');
                if (empty($subTable)) {
                  $mess = 'Name of rowset is missing in ' . $this->api;
                  trigger_error($mess, E_USER_WARNING);
                  return FALSE;
                };
                if ($subTable == 'skills') {
                  $this->$subTable();
                } else {
                $this->rowset($subTable);
                };// else $subTable ...
                break;
              default:// Nothing to do here.
            };// $this->xr->localName ...
            break;
          case XMLReader::END_ELEMENT:
            if ($this->xr->localName == 'result') {
              $qb->addRow($row);
              if (count($qb) > 0) {
                $qb->store();
              };// if count $rows ...
              $qb = NULL;
              return TRUE;
            };// if $this->xr->localName == 'row' ...
            break;
          default:// Nothing to do.
        };// switch $this->xr->nodeType ...
      };// while $this->xr->read() ...
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return FALSE;
  }// function parserAPI
  /**
   * Handles attributes table.
   *
   * @return bool Returns TRUE if data stored to database table.
   */
  protected function attributes() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . ucfirst(__FUNCTION__);
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      $con->Execute($sql);
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    $row = array('ownerID' => $this->ownerID);
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'charisma':
            case 'intelligence':
            case 'memory':
            case 'perception':
            case 'willpower':
              $name = $this->xr->localName;
              $this->xr->read();
              $row[$name] = $this->xr->value;
              break;
          };// switch $xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          if ($this->xr->localName == 'attributes') {
            $qb->addRow($row);
            return $qb->store();
          };// if $this->xr->localName ...
          break;
        default:// Nothing to do here.
      };// switch $this->xr->nodeType ...
    };// while $xr->read() ...
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return FALSE;
  }// function attributes
  /**
   * Used to store XML to CharacterSheet's attributeEnhancers table.
   *
   * @return Bool Return TRUE if store was successful.
   */
  protected function attributeEnhancers() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . ucfirst(__FUNCTION__);
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      $con->Execute($sql);
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'charismaBonus':
            case 'intelligenceBonus':
            case 'memoryBonus':
            case 'perceptionBonus':
            case 'willpowerBonus':
              $row = array('ownerID' => $this->ownerID);
              $row['bonusName'] = $this->xr->localName;
              break;
            case 'augmentatorName':
            case 'augmentatorValue':
              $name = $this->xr->localName;
              $this->xr->read();
              $row[$name] = $this->xr->value;
              break;
            default:// Nothing to do here.
          };// switch $xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          switch ($this->xr->localName) {
            case 'charismaBonus':
            case 'intelligenceBonus':
            case 'memoryBonus':
            case 'perceptionBonus':
            case 'willpowerBonus':
              $qb->addRow($row);
              break;
            case 'attributeEnhancers':
              return $qb->store();
            default:// Nothing to do here.
          };// switch $xr->localName ...
          break;
        default:// Nothing to do here.
      };// switch $this->xr->nodeType ...
    };// while $xr->read() ...
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return FALSE;
  }// function attributeEnhancers
  /**
   * Used to store XML to rowset tables.
   *
   * @param string $table Name of the table for this rowset.
   *
   * @return Bool Return TRUE if store was successful.
   */
  protected function rowset($table) {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . ucfirst($table);
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      // Clear out old info for this owner.
      $con->Execute($sql);
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    $qb->setDefault('ownerID', $this->ownerID);
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'row':
              // Walk through attributes and add them to row.
              while ($this->xr->moveToNextAttribute()) {
                $row[$this->xr->name] = $this->xr->value;
              };// while $this->xr->moveToNextAttribute() ...
              $qb->addRow($row);
              // Upsert every YAPEAL_MAX_UPSERT rows to help keep memory
              // use in check.
              if (YAPEAL_MAX_UPSERT == count($qb)) {
                $qb->store();
              };// if YAPEAL_MAX_UPSERT == count($qb) ...
              break;
          };// switch $this->xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          if ($this->xr->localName == 'rowset') {
            // Insert any leftovers.
            if (count($qb) > 0) {
              $qb->store();
            };// if count $rows ...
            $qb = NULL;
            return TRUE;
          };// if $this->xr->localName == 'row' ...
          break;
      };// switch $this->xr->nodeType
    };// while $this->xr->read() ...
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return FALSE;
  }// function rowset
  /**
   * Used to store XML to CharacterSheet's skills table.
   *
   * @return Bool Return TRUE if store was successful.
   */
  protected function skills() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . ucfirst(__FUNCTION__);
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      // Clear out old info for this owner.
      $con->Execute($sql);
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    $defaults = array('level' => 0, 'ownerID' => $this->ownerID,
      'unpublished' => 0
    );
    $qb->setDefaults($defaults);
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'row':
              // Walk through attributes and add them to row.
              while ($this->xr->moveToNextAttribute()) {
                $row[$this->xr->name] = $this->xr->value;
              };// while $this->xr->moveToNextAttribute() ...
              $qb->addRow($row);
              // Upsert every YAPEAL_MAX_UPSERT rows to help keep memory
              // use in check.
              if (YAPEAL_MAX_UPSERT == count($qb)) {
                $qb->store();
              };// if YAPEAL_MAX_UPSERT == count($qb) ...
              break;
          };// switch $this->xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          if ($this->xr->localName == 'rowset') {
            // Insert any leftovers.
            if (count($qb) > 0) {
              $qb->store();
            };// if count $rows ...
            $qb = NULL;
            return TRUE;
          };// if $this->xr->localName == 'row' ...
          break;
      };// switch $this->xr->nodeType
    };// while $this->xr->read() ...
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return FALSE;
  }// function skills
}
?>
