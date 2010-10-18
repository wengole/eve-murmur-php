<?php
/**
 * Contains CorporationSheet class.
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
 * Class used to fetch and store CorporationSheet API.
 *
 * @package Yapeal
 * @subpackage Api_corp
 */
class corpCorporationSheet  extends ACorp {
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
   * Used to store XML to MySQL table(s).
   *
   * @return Bool Return TRUE if store was successful.
   */
  public function apiStore() {
    $cuntil = '1970-01-01 00:00:01';
    // Need to exclude some params when passing them to API server so it doesn't
    // get confused.
    $apiParams = $this->params;
    unset($apiParams['corporationID']);
    // First get a new cache instance.
    $cache = new YapealApiCache($this->api, $this->section, $apiParams);
    try {
      // See if there is a valid cached copy of the API XML.
      $result = $cache->getCachedApi();
      // If it's not cached need to try to get it.
      if (FALSE === $result) {
        $postParams = '';
        $proxy = $this->getProxy();
        // Build http parameter.
        $http = array('method' => 'POST', 'timeout' => YAPEAL_CURL_TIMEOUT,
          'url' => $proxy);
        // Setup for POST query.
        $http['content'] = http_build_query($apiParams, NULL, '&');
        $postParams = 'Post parameters: ' . $http['content'] . PHP_EOL;
        // Setup new cURL connection with options.
        $ch = new CurlRequest($http);
        // Try to get XML.
        $curl = $ch->exec();
        // Now check for errors.
        if ($curl['curl_error']) {
          $mess = 'cURL error for ' . $http['url'] . PHP_EOL;
          $mess .= $postParams;
          if (isset($curl['curl_errno'])) {
            $mess .= 'Error code: ' . $curl['curl_errno'];
          };// if isset $curl['curl_errno'] ...
          $mess .= 'Error message: ' . $curl['curl_error'];
          trigger_error($mess, E_USER_WARNING);
          return FALSE;
        };// if $curl['curl_error'] ...
        if (200 != $curl['http_code']) {
          $mess = 'HTTP error for ' . $http['url'] . PHP_EOL;
          $mess .= $postParams;
          $mess .= 'Error code: ' . $curl['http_code'] . PHP_EOL;
          trigger_error($mess, E_USER_WARNING);
          return FALSE;
        };// if 200 != $curl['http_code'] ...
        if (!$curl['body']) {
          $mess = 'API data empty for ' . $http['url'] . PHP_EOL;
          $mess .= $postParams;
          trigger_error($mess, E_USER_WARNING);
          return FALSE;
        };// if !$curl['body'] ...
        $result = (string)$curl['body'];
        // cURL has returned XML don't need it anymore.
        $ch = NULL;
        // Check if XML is valid.
        if (FALSE === $cache->validateXML($result)) {
          // No use going any farther if the XML isn't valid.
          return FALSE;
        };
        // Cache the recieved XML.
        $cache->cacheXml($result);
      };// if FALSE === $result ...
      // Create XMLReader.
      $this->xr = new XMLReader();
      // Pass XML to reader.
      $this->xr->XML($result);
      // Outer structure of XML is processed here.
      while ($this->xr->read()) {
        switch ($this->xr->nodeType) {
          case XMLReader::ELEMENT:
            switch ($this->xr->localName) {
              case 'currentTime':
                break;
              case 'result':
                // Call the per API parser.
                $result = $this->parserAPI();
                break;
              case 'cachedUntil':
                $this->xr->read();
                $cuntil = $this->xr->value;
                break;
            };// switch $this->xr->localName ...
            break;
          case XMLReader::END_ELEMENT:
            break;
        };// switch $this->xr->nodeType
      };// while $xr->read() ...
      // Update CachedUntil time since we should have a new one.
      $data = array( 'api' => $this->api, 'cachedUntil' => $cuntil,
        'ownerID' => $this->ownerID, 'section' => $this->section
      );
      $cu = new CachedUntil($data);
      $cu->store();
      $this->xr->close();
      return $result;
    }
    catch (YapealApiErrorException $e) {
      // Any API errors that need to be handled in some way are handled in this
      // function.
      $this->handleApiError($e);
      return FALSE;
    }
    catch (ADODB_Exception $e) {
      $mess = 'Uncaught ADOdb exception' . PHP_EOL;
      trigger_error($mess, E_USER_WARNING);
      // Catch any uncaught ADOdb exceptions here.
      return FALSE;
    }
  }// function apiStore
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
      $sql .= ' where `corporationID`=' . $this->params['corporationID'];
      $con->Execute($sql);
      while ($this->xr->read()) {
        switch ($this->xr->nodeType) {
          case XMLReader::ELEMENT:
            switch ($this->xr->localName) {
              case 'allianceID':
              case 'allianceName':
              case 'ceoID':
              case 'ceoName':
              case 'corporationID':
              case 'corporationName':
              case 'description':
              case 'memberCount':
              case 'memberLimit':
              case 'shares':
              case 'stationID':
              case 'stationName':
              case 'taxRate':
              case 'ticker':
              case 'url':
                // Grab node name.
                $name = $this->xr->localName;
                // Move to text node.
                $this->xr->read();
                $row[$name] = $this->xr->value;
                break;
              case 'logo':
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
                $this->rowset($subTable);
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
   * Used to store XML to CorporationSheet's logo table.
   *
   * @return Bool Return TRUE if store was successful.
   */
  protected function logo() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . 'Logo';
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
            case 'color1':
            case 'color2':
            case 'color3':
            case 'graphicID':
            case 'shape1':
            case 'shape2':
            case 'shape3':
              $name = $this->xr->localName;
              $this->xr->read();
              $row[$name] = $this->xr->value;
              break;
          };// switch $xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          if ($this->xr->localName == 'logo') {
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
  }// function logo
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
}
?>
