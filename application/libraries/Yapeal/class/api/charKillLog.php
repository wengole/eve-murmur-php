<?php
/**
 * Contains killLog class.
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
 * Class used to fetch and store char KillLog API.
 *
 * @package Yapeal
 * @subpackage Api_char
 */
class charKillLog extends AChar {
  /**
   * @var object QueryBuilder instance for attackers table.
   */
  protected $attackers;
  /**
   * @var string Holds the refID from each row in turn to use when walking.
   */
  private $beforeID;
  /**
   * @var string Holds the date from each row in turn to use when walking.
   */
  private $date;
  /**
   * @var object QueryBuilder instance for items table.
   */
  protected $items;
  /**
   * @var integer Hold row count used in walking.
   */
  private $rowCount;
  /**
   * @var array Holds a stack of parent nodes until after their children are
   * proccessed.
   */
  private $stack = array();
  /**
   * @var object QueryBuilder instance for victim table.
   */
  protected $victim;
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
    // This counter is used to insure do ... while can't become infinite loop.
    $counter = 1000;
    $this->date = '1970-01-01 00:00:01';
    $this->beforeID = 0;
    try {
      do {
        // Give each API 60 seconds to finish. This should never happen but is
        // here to catch runaways.
        set_time_limit(60);
        /* Not going to assume here that API servers figure oldest allowed
         * entry based on a saved time from first pull but instead use current
         * time. The few seconds of difference shouldn't cause any missed data
         * and is safer than assuming.
         */
        $oldest = gmdate('Y-m-d H:i:s', strtotime('7 days ago'));
        // Need to add extra stuff to normal parameters to make walking work.
        $apiParams = $this->params;
        // This tells API server where to start from when walking.
        $apiParams['beforeKillID'] = $this->beforeID;
        // First get a new cache instance.
        $cache = new YapealApiCache($this->api, $this->section, $this->ownerID, $apiParams);
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
          // Cache the received result.
          $cache->cacheXml($result);
          // Check if XML is valid.
          if (FALSE === $cache->isValid()) {
            // No use going any farther if the XML isn't valid.
            return FALSE;
          };
        };// if FALSE === $result ...
        // Create XMLReader.
        $this->xr = new XMLReader();
        // Pass XML to reader.
        $this->xr->XML($result);
        // Outer structure of XML is processed here.
        while ($this->xr->read()) {
          if ($this->xr->nodeType == XMLReader::ELEMENT &&
            $this->xr->localName == 'result') {
            $result = $this->parserAPI();
          };// if $this->xr->nodeType ...
        };// while $this->xr->read() ...
        $this->xr->close();
        // Leave loop if already got as many entries as API servers allow.
        if ($this->rowCount != 25 || $this->date < $oldest) {
          break;
        };
      } while ($counter--);
    }
    catch (YapealApiErrorException $e) {
      // Any API errors that need to be handled in some way are handled in this
      // function.
      $this->handleApiError($e);
      return FALSE;
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    return $result;
  }// function apiStore
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
    // Get a new query instance for attackers.
    $this->attackers = new YapealQueryBuilder(
      YAPEAL_TABLE_PREFIX . $this->section . 'Attackers', YAPEAL_DSN);
    // Get a new query instance for items.
    $this->items = new YapealQueryBuilder(
      YAPEAL_TABLE_PREFIX . $this->section . 'Items', YAPEAL_DSN);
    // Get a new query instance for victim.
    $this->victim = new YapealQueryBuilder(
      YAPEAL_TABLE_PREFIX . $this->section . 'Victim', YAPEAL_DSN);
    try {
      while ($this->xr->read()) {
        switch ($this->xr->nodeType) {
          case XMLReader::ELEMENT:
            switch ($this->xr->localName) {
              case 'row':
                $row = array();
                // Walk through attributes and add them to row.
                while ($this->xr->moveToNextAttribute()) {
                  $row[$this->xr->name] = $this->xr->value;
                  switch ($this->xr->name) {
                    case 'killTime':
                      // Save date for walking.
                      $this->date = $this->xr->value;
                      break;
                    case 'killID':
                      // Save killID for walking.
                      $this->beforeID = $this->xr->value;
                      break;
                    default:// Nothing to do here.
                  };// switch $this->xr->name ...
                };// while $this->xr->moveToNextAttribute() ...
                $qb->addRow($row);
                break;
              case 'victim':
                $row = array('killID' => $this->beforeID);
               // Walk through attributes and add them to row.
                while ($this->xr->moveToNextAttribute()) {
                  // Save the ship type to use for root node of items table.
                  if ($this->xr->name == 'shipTypeID') {
                    $typeID = $this->xr->value;
                  };
                  $row[$this->xr->name] = $this->xr->value;
                };// while $this->xr->moveToNextAttribute() ...
                $this->victim->addRow($row);
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
                if ($subTable == 'items') {
                  $inherit = array('killID' => $this->beforeID, 'index' => 2,
                    'level' => 1);
                  $row = array('flag' => 0, 'killID' => $this->beforeID,
                    'lft' => 0, 'lvl' => 0, 'qtyDestroyed' => 1,
                    'qtyDropped' => 0, 'typeID' => $typeID
                  );
                  $row['rgt'] = $this->nestedSet($inherit);
                  $this->items->addRow($row);
                } else if ($subTable == 'attackers') {
                $this->attack();
                };// else $subTable ...
                break;
              default:// Nothing to do here.
            };// switch $this->xr->localName ...
            break;
          case XMLReader::END_ELEMENT:
            if ($this->xr->localName == 'result') {
              // Save row count and store rows.
              if ($this->rowCount = count($qb) > 0) {
                $qb->store();
              };// if count $rows ...
              $qb = NULL;
              // Store rows.
              if (count($this->victim) > 0) {
                $this->victim->store();
              };// if count $this->victim ...
              $this->victim = NULL;
              // Store rows.
              if (count($this->attackers) > 0) {
                $this->attackers->store();
              };// if count $this->items ...
              $this->attackers = NULL;
              // Store rows.
              if (count($this->items) > 0) {
                $this->items->store();
              };// if count $this->items ...
              $this->items = NULL;
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
   * Navigates XML and build nested sets to be added to table.
   *
   * The function adds addition columns to preserve the parent child
   * relationships of killID->items, killID->containers, containers->items, etc.
   * by using the nested set method.
   * For more information about nested set see these project wiki pages:
   * {@link http://code.google.com/p/yapeal/wiki/HierarchicalData HierarchicalData}
   * {@link http://code.google.com/p/yapeal/wiki/HierarchicalData2 HierarchicalData2}
   *
   * @author Michael Cummings <mgcummings@yahoo.com>
   *
   * @param array $inherit An array of stuff that needs to propagate from parent
   * to child.
   *
   * @return integer Current index for lft/rgt counting.
   */
  protected function nestedSet($inherit) {
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'row':
              // Add some of the inherit values to $row and update them as needed.
              $row = array('lft' => $inherit['index']++,
                'lvl' => $inherit['level'],
                'killID' => $inherit['killID']);
              // Walk through attributes and add them to row.
              while ($this->xr->moveToNextAttribute()) {
                $row[$this->xr->name] = $this->xr->value;
              };// while $this->xr->moveToNextAttribute();
              // Move back up to element.
              $this->xr->moveToElement();
              // Check if parent node.
              if ($this->xr->isEmptyElement != 1) {
                // Save parent on stack.
                $this->stack[] = $row;
                // Continue on to process children.
                break;
              };// if $xr->isEmptyElement ...
              // Add 'rgt' and increment value.
              $row['rgt'] =  $inherit['index']++;
              // The $row is complete and ready to add.
              $this->items->addRow($row);
              // Upsert every YAPEAL_MAX_UPSERT rows to help keep memory
              // use in check.
              if (YAPEAL_MAX_UPSERT <= count($this->items)) {
                $this->items->store();
              };// if YAPEAL_MAX_UPSERT == count($qb) ...
              break;
            case 'rowset':
              // Level increases with each parent rowset.
              ++$inherit['level'];
              break;
            default:
              break;
          }// switch $this->xr->localName ...
          break;
        case XMLReader::END_ELEMENT:
          switch ($this->xr->localName) {
            case 'row':
              $row = array_pop($this->stack);
              // Add 'rgt' and increment value.
              $row['rgt'] =  $inherit['index']++;
              // The $row is complete and ready to add.
              $this->items->addRow($row);
              break;
            case 'rowset':
              // Level decrease with end of each parent rowset.
              --$inherit['level'];
              if ($inherit['level'] == 0) {
                // Return the final index value to parserAPI().
                return $inherit['index'];
              };// if $inherit['level'] ...
              break;
            default:
              break;
          }// switch $this->xr->localName ...
          break;
      };// switch $this->xr->nodeType
    };// while $xr->read() ...
    $mess = 'Function ' . __FUNCTION__ . ' did not exit correctly' . PHP_EOL;
    trigger_error($mess, E_USER_WARNING);
    return $inherit['index'];
  }// function nestedSet
  /**
   * Used to store XML to attackers table.
   *
   * @return Bool Return TRUE if store was successful.
   */
  protected function attack() {
    while ($this->xr->read()) {
      switch ($this->xr->nodeType) {
        case XMLReader::ELEMENT:
          switch ($this->xr->localName) {
            case 'row':
              $row = array('killID' => $this->beforeID);
              // Walk through attributes and add them to row.
              while ($this->xr->moveToNextAttribute()) {
                $row[$this->xr->name] = $this->xr->value;
              };// while $this->xr->moveToNextAttribute() ...
              $this->attackers->addRow($row);
              // Upsert every YAPEAL_MAX_UPSERT rows to help keep memory
              // use in check.
              if (YAPEAL_MAX_UPSERT == count($this->attackers)) {
                $this->attackers->store();
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
}
?>
