<?php
/**
 * Contains abstract class for corp section.
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
 * Abstract class for Corp APIs.
 *
 * @package Yapeal
 * @subpackage Api_corp
 */
abstract class ACorp extends AApiRequest {
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
    // Cut off 'A' and lower case abstract class name to make section name.
    $this->section = strtolower(substr(__CLASS__, 1));
    $required = array('apiKey' => 'C', 'characterID' => 'I',
      'corporationID' => 'I', 'userID' => 'I');
    foreach ($required as $k => $v) {
      if (!isset($params[$k])) {
        $mess = 'Missing required parameter $params["' . $k . '"]';
        $mess .= ' to constructor for ' . $this->api;
        $mess .= ' in ' . basename(__FILE__);
        throw new LengthException($mess, 1);
      };// if !isset $params[$k] ...
      switch ($v) {
        case 'C':
        case 'X':
          if (!is_string($params[$k])) {
            $mess = '$params["' . $k . '"] must be a string for ' . $this->api;
            $mess .= ' in ' . basename(__FILE__);
            throw new LengthException($mess, 2);
          };// if !is_string $params[$k] ...
          break;
        case 'I':
          if (0 != strlen(str_replace(range(0,9),'',$params[$k]))) {
            $mess = '$params["' . $k . '"] must be an integer for ' . $this->api;
            $mess .= ' in ' . basename(__FILE__);
            throw new LengthException($mess, 3);
          };// if 0 == strlen(...
          break;
      };// switch $v ...
    };// foreach $required ...
    $this->ownerID = $params['corporationID'];
    $this->params = $params;
  }// function __construct
  /**
   * Per API section function that returns API proxy.
   *
   * For a description of how to design a format string look at the description
   * from {@link AApiRequest::sprintfn sprintfn}. The 'section' and 'api' will
   * be available as well as anything included in $params for __construct().
   *
   * @return string Returns the URL for proxy as string if found else it will
   * return the default string needed to use API server directly.
   */
  protected function getProxy() {
    $default = 'http://api.eve-online.com/' . $this->section;
    $default .= '/' . $this->api . '.xml.aspx';
    $sql = 'select proxy from ';
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $tables = array(
        '`' . YAPEAL_TABLE_PREFIX . 'utilRegisteredCorporation` where `corporationID`=' .
        $this->params['corporationID'],
        '`' . YAPEAL_TABLE_PREFIX . 'utilRegisteredUser` where `userID`=' .
        $this->params['userID'],
        '`' . YAPEAL_TABLE_PREFIX . 'utilSections` where `section`=' .
        $con->qstr($this->section)
      );
      // Look for a set proxy in each table.
      foreach ($tables as $table) {
        $result = $con->GetOne($sql . $table);
        // 4 is random and not magic. It just sounded good.
        if (strlen($result) > 4) {
          break;
        };
      };// foreach ...
      if (empty($result)) {
        return $default;
      };// if empty $result ...
      // Need to make substitution array by adding api, section, and params.
      $subs = array('api' => $this->api, 'section' => $this->section);
      $subs = array_merge($subs, $this->params);
      $proxy = self::sprintfn($result, $subs);
      if (FALSE === $proxy) {
        return $default;
      };
      return $proxy;
    }
    catch (ADODB_Exception $e) {
      return $default;
    }
  }// function getProxy
  /**
   * Handles some Eve API error codes in special ways.
   *
   * @param object $e Eve API exception returned.
   *
   * @return bool Returns TRUE if handled the error else FALSE.
   */
  protected function handleApiError($e) {
    try {
      switch ($e->getCode()) {
        // All of these codes give a new cachedUntil time to use.
        case 101: // Wallet exhausted: retry after {0}.
        case 103: // Already returned one week of data: retry after {0}.
        case 115: // Assets already downloaded: retry after {0}.
        case 116: // Industry jobs already downloaded: retry after {0}.
        case 117: // Market orders already downloaded. retry after {0}.
        case 119: // Kills exhausted: retry after {0}.
          $cuntil = substr($e->getMessage() , -21, 20);
          $data = array( 'api' => $this->api, 'cachedUntil' => $cuntil,
            'ownerID' => $this->ownerID, 'section' => $this->section
          );
          $cu = new CachedUntil($data);
          $cu->store();
          break;
        case 105:// Invalid characterID.
        case 201:// Character does not belong to account.
        case 202:// API key authentication failure.
        case 203:// Authentication failure.
        case 204:// Authentication failure.
        case 205:// Authentication failure (final pass).
        case 210:// Authentication failure.
        case 212:// Authentication failure (final pass).
          $mess = 'Deactivating corporationID: ';
          $mess .= $this->params['corporationID'];
          $mess .= ' as their Eve API information is incorrect';
          trigger_error($mess, E_USER_WARNING);
          $corp = new RegisteredCorporation($this->params['corporationID'], FALSE);
          $corp->isActive = 0;
          if (!$corp->store()) {
            $mess = 'Could not deactivate corporationID: ';
            $mess .= $this->params['corporationID'];
            trigger_error($mess, E_USER_WARNING);
          };// if !$corp->store() ...
          break;
        //case 114:// Invalid itemID provided. (Bad POS)
        //  $mess = 'Deleted ' . $this->posID['itemID'];
        //  $mess .= ' from StarbaseList for ' . $this->ownerID;
        //  $tableName = YAPEAL_TABLE_PREFIX . $this->section . 'StarbaseList';
        //  try {
        //    $con = YapealDBConnection::connect(YAPEAL_DSN);
        //    $sql = 'delete from ';
        //    $sql .= '`' . $tableName . '`';
        //    $sql .= ' where `ownerID`=' . $this->ownerID;
        //    $sql .= ' and `itemID`=' . $this->posID['itemID'];
        //    $con->Execute($sql);
        //  }
        //  catch (ADODB_Exception $e) {
        //    $mess = 'Could not delete ' . $this->posID['itemID'];
        //    $mess .= ' from StarbaseList for ' . $this->ownerID;
        //    trigger_error($mess, E_USER_WARNING);
        //    // Something wrong with query return FALSE.
        //    return FALSE;
        //  }
        //  trigger_error($mess, E_USER_WARNING);
        //  break;
        case 200:// Current security level not high enough. (Wrong API key)
        case 206:// Character must have Accountant or Junior Accountant roles.
        case 207:// Not available for NPC corporations.
        case 208:// Character must have Accountant, Junior Accountant, or Trader roles.
        case 209:// Character must be a Director or CEO.
        case 213:// Character must have Factory Manager role.
          $mess = 'Deactivating Eve API: ' . $this->api;
          $mess .= ' for corporation ' . $this->params['corporationID'];
          $mess .= ' as character ' .  $this->params['characterID'];
          if ($code != 200) {
            $mess .= ' does not currently have access';
          } else {
            $mess .= ' did not give the required full API key';
          };
          trigger_error($mess, E_USER_WARNING);
          $corp = new RegisteredCorporation($this->params['corporationID'], FALSE);
          $corp->deleteActiveAPI($this->api);
          if (FALSE === $corp->store()) {
            $mess = 'Could not deactivate ' . $this->api;
            $mess .= ' for ' . $this->params['corporationID'];
            trigger_error($mess, E_USER_WARNING);
          };// if !$corp->store() ...
          break;
        case 211:// Login denied by account status.
          // The user's account isn't active deactivate it.
          $mess = 'Deactivating userID: ' . $this->params['userID'];
          $mess .= ' as their Eve account is currently suspended';
          trigger_error($mess, E_USER_WARNING);
          $user = new RegisteredUser($this->params['userID'], FALSE);
          $user->isActive = 0;
          if (!$user->store()) {
            $mess = 'Could not deactivate userID: ' . $this->params['userID'];
            trigger_error($mess, E_USER_WARNING);
          };// if !$user->store() ...
          break;
        case 901:// Web site database temporarily disabled.
        case 902:// EVE backend database temporarily disabled.
          $cuntil = gmdate('Y-m-d H:i:s', strtotime('6 hours'));
          $data = array( 'api' => $this->api, 'cachedUntil' => $cuntil,
            'ownerID' => $this->ownerID, 'section' => $this->section
          );
          $cu = new CachedUntil($data);
          $cu->store();
          break;
        default:
          return FALSE;
          break;
      };// switch $code ...
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    return TRUE;
  }// function handleApiError
  /**
   * Simple <rowset> per API parser for XML.
   *
   * Most common API style is a simple <rowset>. This implementation allows most
   * API classes to be empty except for a constructor which sets $this->api and
   * calls their parent constructor.
   *
   * @return bool Returns TRUE if XML was parsered correctly, FALSE if not.
   */
  protected function parserAPI() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . $this->api;
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    // Set any column defaults needed.
    $qb->setDefault('ownerID', $this->ownerID);
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      // Empty out old data then upsert (insert) new.
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      $con->Execute($sql);
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
            if ($this->xr->localName == 'result') {
              // Insert any leftovers.
              if (count($qb) > 0) {
                $qb->store();
              };// if count $rows ...
              $qb = NULL;
              return TRUE;
            };// if $this->xr->localName == 'row' ...
            break;
        };// switch $this->xr->nodeType
      };// while $xr->read() ...
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return FALSE;
  }// function parserAPI
}
?>
