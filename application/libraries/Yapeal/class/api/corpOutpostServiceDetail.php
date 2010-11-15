<?php
/**
 * Contains OutpostServiceDetail class.
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
 * Class used to fetch and store corp OutpostServiceDetail API.
 *
 * @package Yapeal
 * @subpackage Api_corp
 */
class corpOutpostServiceDetail extends ACorp {
  /**
   * @var integer Holds current Outpost ID.
   */
  private $outpostID;
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
    $outpostList = $this->outpostList();
    if (FALSE === $outpostList) {
      return FALSE;
    };// if FALSE ...
    $cuntil = '1970-01-01 00:00:01';
    $ret = TRUE;
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      // Empty out old data then upsert (insert) new
      $sql = 'delete from `';
      $sql .= YAPEAL_TABLE_PREFIX . $this->section . $this->api . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      $con->Execute($sql);
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    foreach ($outpostList as $this->outpostID) {
      try {
        // Need to add extra stuff to normal parameters to make walking work.
        $apiParams = $this->params;
        // This tells API server which outpost we want.
        $apiParams['itemID'] = (string)$this->outpostID['stationID'];
        // First get a new cache instance.
        $cache = new YapealApiCache($this->api, $this->section, $apiParams);
        // See if there is a valid cached copy of the API XML.
        $result = $cache->getCachedApi();
        // If it's not cached need to try to get it.
        if (FALSE === $result) {
          $proxy = $this->getProxy();
          $con = new YapealNetworkConnection();
          $result = $con->retrieveXml($proxy, $apiParams);
          // FALSE means there was an error and it has already been report so just
          // return to caller.
          if (FALSE === $result) {
            return FALSE;
          };
          // Check if XML is valid.
          if (FALSE === $cache->validateXML($result)) {
            // No use going any farther if the XML isn't valid.
            $ret = FALSE;
            break;
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
                case 'result':
                  // Call the per API parser.
                  $result = $this->parserAPI();
                  if ($result === FALSE) {
                    $ret = FALSE;
                  };// if $result ...
                  break;
                case 'cachedUntil':
                  $this->xr->read();
                  $cuntil = $this->xr->value;
                  break;
                default:// Nothing to do.
              };// switch $this->xr->localName ...
              break;
            case XMLReader::END_ELEMENT:
              break;
            default:// Nothing to do.
          };// switch $this->xr->nodeType
        };// while $xr->read() ...
        // Update CachedUntil time since we should have a new one.
        $data = array( 'api' => $this->api, 'cachedUntil' => $cuntil,
          'ownerID' => $this->ownerID, 'section' => $this->section
        );
        $cu = new CachedUntil($data);
        $cu->store();
        $this->xr->close();
      }
      catch (YapealApiErrorException $e) {
        // Any API errors that need to be handled in some way are handled in
        // this function.
        $this->handleApiError($e);
        $ret = FALSE;
        continue;
      }
      catch (ADODB_Exception $e) {
        $ret = FALSE;
        continue;
      }
    };// foreach $posList ...
    return $ret;
  }// function apiStore
  /**
   * Per API parser for XML.
   *
   * @return bool Returns TRUE if XML was parsered correctly, FALSE if not.
   */
  protected function parserAPI() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . $this->api;
    $defaults = array('ownerID' => $this->ownerID);
    // Get a new query instance.
    $qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    $qb->setDefaults($defaults);
    try {
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
  /**
   * Get per corp list of outposts from corpOutpostList.
   *
   * @return mixed List of stationIDs for this corp's outposts or FALSE if error
   * or no outposts found for corporation.
   */
  protected function outpostList() {
    $tableName = YAPEAL_TABLE_PREFIX . $this->section . 'OutpostList';
    $list = array();
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $sql = 'select `stationID`';
      $sql .= ' from ';
      $sql .= '`' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      $sql .= ' order by rand()';
      $list = $con->GetAll($sql);
    }
    catch (ADODB_Exception $e) {
      // Something wrong with query return FALSE.
      return FALSE;
    }
    if (count($list) == 0) {
      return FALSE;
    };// if count($list) ...
    return $list;
  }// function posList
}
?>
