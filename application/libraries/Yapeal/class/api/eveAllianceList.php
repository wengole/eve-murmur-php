<?php
/**
 * Contains AllianceList class.
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
 * Class used to fetch and store AllianceList API.
 *
 * @package Yapeal
 * @subpackage Api_eve
 */
class eveAllianceList extends AEve {
  /**
   * @var object Query instance for corporation rows to be added to table.
   */
  private $corporations;
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
    // Get a new query instance.
    $this->corporations = new YapealQueryBuilder(
      YAPEAL_TABLE_PREFIX . $this->section . 'MemberCorporations', YAPEAL_DSN);
    try {
      while ($this->xr->read()) {
        switch ($this->xr->nodeType) {
          case XMLReader::ELEMENT:
            switch ($this->xr->localName) {
              case 'row':
                // Grab allianceID for memberCorporation table.
                $allianceID = $this->xr->getAttribute('allianceID');
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
                // Process member corporations.
                if ($this->xr->isEmptyElement != 1) {
                  $this->rowset($allianceID);
                };// if $this->xr->isEmptyElement ...
                break;
              default:// Nothing to do.
            };// switch $this->xr->localName ...
            break;
          case XMLReader::END_ELEMENT:
            if ($this->xr->localName == 'result') {
              // Insert any leftovers.
              if (count($qb) > 0) {
                $qb->store();
              };// if count $rows ...
              $qb = NULL;
              // Insert any leftovers.
              if (count($this->corporations) > 0) {
                $this->corporations->store();
              };// if count $rows ...
              $this->corporations = NULL;
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
   * Used to store XML to rowset tables.
   *
   * @param string $allianceID ID of alliance that member corps belong to.
   *
   * @return Bool Return TRUE if store was successful.
   */
  protected function rowset($allianceID) {
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'row':
              $row['allianceID'] = $allianceID;
              // Walk through attributes and add them to row.
              while ($this->xr->moveToNextAttribute()) {
                $row[$this->xr->name] = $this->xr->value;
              };// while $this->xr->moveToNextAttribute() ...
              $this->corporations->addRow($row);
              // Upsert every YAPEAL_MAX_UPSERT rows to help keep memory
              // use in check.
              if (YAPEAL_MAX_UPSERT == count($this->corporations)) {
                $this->corporations->store();
              };// if YAPEAL_MAX_UPSERT == count($qb) ...
              break;
          };// switch $this->xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          if ($this->xr->localName == 'rowset') {
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
   * Method used to prepare database table(s) before parsing API XML data.
   *
   * If there is any need to delete records or empty tables before parsing XML
   * and adding the new data this method should be used to do so.
   *
   * @return bool Will return TRUE if table(s) were prepared correctly.
   */
  protected function prepareTables() {
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      // Empty out old data then upsert (insert) new.
      $sql = 'truncate table `';
      $sql .= YAPEAL_TABLE_PREFIX . $this->section . $this->api . '`';
      $con->Execute($sql);
      // Empty out old data then upsert (insert) new.
      $sql = 'truncate table `';
      $sql .= YAPEAL_TABLE_PREFIX . $this->section . 'MemberCorporations' . '`';
      $con->Execute($sql);
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    return TRUE;
  }// function prepareTables
}
?>
