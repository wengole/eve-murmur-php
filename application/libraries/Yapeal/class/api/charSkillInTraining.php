<?php
/**
 * Contains SkillInTraining class.
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
class charSkillInTraining  extends AChar {
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
    $row = array('currentTQTime' => YAPEAL_START_TIME, 'offset' => 0,
      'ownerID' => $this->params['characterID'], 'skillInTraining' => 0,
      'trainingDestinationSP' => 0, 'trainingEndTime' => YAPEAL_START_TIME,
      'trainingStartSP' => 0, 'trainingStartTime' => YAPEAL_START_TIME,
      'trainingToLevel' => 0, 'trainingTypeID' => 0);
    try {
      while ($this->xr->read()) {
        switch ($this->xr->nodeType) {
          case XMLReader::ELEMENT:
            switch ($this->xr->localName) {
              case 'skillInTraining':
              case 'trainingDestinationSP':
              case 'trainingEndTime':
              case 'trainingStartSP':
              case 'trainingStartTime':
              case 'trainingToLevel':
              case 'trainingTypeID':
                // Grab node name.
                $name = $this->xr->localName;
                // Move to text node.
                $this->xr->read();
                $row[$name] = $this->xr->value;
                break;
              case 'currentTQTime':
                $row['offset'] = $this->xr->getAttribute('offset');
                // Move to text node.
                $this->xr->read();
                $row['currentTQTime'] = $this->xr->value;
                break;
              default:// Nothing to do.
            };// switch $this->xr->localName ...
            break;
          case XMLReader::END_ELEMENT:
            if ($this->xr->localName == 'result') {
              $qb->addRow($row);
              $qb->store();
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
}
?>
