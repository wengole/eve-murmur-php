<?php
/**
 * Contains AssetList class.
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
 * Class used to fetch and store char AssetList API.
 *
 * @package Yapeal
 * @subpackage Api_char
 */
class charAssetList extends AChar {
  /**
   * @var object Holds queryBuilder instance.
   */
  private $qb;
  /**
   * @var array Holds a stack of parent nodes until after their children are
   * proccessed.
   */
  private $stack = array();
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
    $this->qb = new YapealQueryBuilder($tableName, YAPEAL_DSN);
    // Set any column defaults needed.
    $this->qb->setDefault('ownerID', $this->ownerID);
    // Generate owner node as root for tree. It has to be added after all the
    // others to have corrected 'rgt'.
    $row = array('flag' => '0', 'itemID' => $this->ownerID, 'lft' => '0',
      'locationID' => '0', 'lvl' => '0', 'ownerID' => $this->ownerID,
      'quantity' => '1', 'singleton' => '0', 'typeID' => '25'
    );
    $inherit = array('locationID' => '0', 'index' => 2, 'level' => 0);
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      // Empty out old data then upsert (insert) new.
      $sql = 'delete from `' . $tableName . '`';
      $sql .= ' where `ownerID`=' . $this->ownerID;
      $con->Execute($sql);
      // Move through all the rows and add them to database.
      // The returned value is the updated 'rgt' value for the root node.
      $row['rgt'] = $this->nestedSet($inherit);
      // Add the root node with updated 'rgt'.
      $this->qb->addRow($row);
      // Insert root node and any leftovers.
      $this->qb->store();
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    return TRUE;
  }// function parserAPI
  /**
   * Navigates XML and build nested sets to be added to table.
   *
   * The function adds addition columns to preserve the parent child
   * relationships of location->hangers, location->containers, location->items,
   * location->hanger->items, etc. by using the nested set method.
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
                'locationID' => $inherit['locationID']);
              // Walk through attributes and add them to row.
              while ($this->xr->moveToNextAttribute()) {
                $row[$this->xr->name] = $this->xr->value;
                // Save any new location so children can inherit it.
                if ($this->xr->name == 'locationID') {
                  $inherit['locationID'] = $this->xr->value;
                };// if $this->xr->name ...
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
              $this->qb->addRow($row);
              // Upsert every YAPEAL_MAX_UPSERT rows to help keep memory
              // use in check.
              if (YAPEAL_MAX_UPSERT <= count($this->qb)) {
                $this->qb->store();
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
            case 'result':
              // Return the final index value to parserAPI().
              return $inherit['index'];
              break;
            case 'row':
              $row = array_pop($this->stack);
              // Add 'rgt' and increment value.
              $row['rgt'] =  $inherit['index']++;
              // The $row is complete and ready to add.
              $this->qb->addRow($row);
              break;
            case 'rowset':
              // Level decrease with end of each parent rowset.
              --$inherit['level'];
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
}
?>
