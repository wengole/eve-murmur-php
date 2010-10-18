<?php
/**
 * Contains YapealApiCache class.
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
 * Class used to manage caching of XML from Eve APIs.
 *
 * @package    Yapeal
 * @subpackage YapealAPICache
 */
class YapealApiCache {
  /**
   * @var string Name of the Eve API being cached.
   */
  protected $api;
  /**
   * @var string Holds SHA1 hash of $section, $api, $postParams.
   */
  private $hash;
  /**
   * @var array The list of any required params used in getting API.
   */
  protected $postParams;
  /**
   * @var string The api section that $api belongs to.
   */
  protected $section;
  /**
   * @var string Hold the XML.
   */
  protected $xml;
  /**
   * Constructor
   *
   * @param string $api Name of the Eve API being cached.
   * @param string $section The api section that $api belongs to. For Eve
   * APIs will be one of account, char, corp, eve, map, or server.
   * @param array $postParams The list of required params used in getting API.
   * This maybe empty for some APIs i.e. eve, map, and server.
   *
   * @return object Returns the instance of the class.
   */
  public function __construct($api, $section, $postParams = array()) {
    $params = '';
    if (!empty($postParams)) {
      foreach ($postParams as $k => $v) {
        $params .= $k . '=' . $v;
      };
    };
    $this->api = $api;
    $this->hash = hash('sha1', $section . $api . $params);
    $this->section = $section;
    $this->postParams = $postParams;
  }// function __constructor
  /**
   * Function used to save API XML to cache database table and/or file.
   *
   * @param string $xml The Eve API XML to be cached.
   *
   * @return bool Returns TRUE if XML was cached, FALSE otherwise.
   */
  public function cacheXml($xml) {
    if (TRUE == YAPEAL_CACHE_XML) {
      switch (YAPEAL_CACHE_OUTPUT) {
        case 'both':
          $this->cacheXmlDatabase($xml);
          $this->cacheXmlFile($xml);
          break;
        case 'database':
          $this->cacheXmlDatabase($xml);
          break;
        case 'file':
          $this->cacheXmlFile($xml);
          break;
        default:
          $mess = 'Invalid value of "' . YAPEAL_CACHE_OUTPUT;
          $mess .= '" for YAPEAL_CACHE_OUTPUT.';
          $mess .= ' Check that the setting in config/yapeal.ini is correct.';
          trigger_error($mess, E_USER_WARNING);
          return FALSE;
      };// switch YAPEAL_CACHE_OUTPUT ...
      return TRUE;
    };// if TRUE == YAPEAL_CACHE_XML
    return FALSE;
  }// function cacheXml
  /**
   * Function used to save API XML into database table.
   *
   * @param string $xml The Eve API XML to be cached.
   *
   * @return bool Returns TRUE if XML was cached, FALSE otherwise.
   */
  private function cacheXmlDatabase($xml) {
    if (empty($xml)) {
      $mess = 'XML was empty' . PHP_EOL;
      trigger_error($mess, E_USER_WARNING);
      return FALSE;
    };// if empty($xml) ...
    try {
      // Get a new query instance.
      $qb = new YapealQueryBuilder(YAPEAL_TABLE_PREFIX . 'utilXmlCache', YAPEAL_DSN);
      $row = array('api' => $this->api, 'hash' => $this->hash,
        'section' => $this->section, 'xml' => $xml);
      $qb->addRow($row);
      $qb->store();
    }
    catch (ADODB_Exception $e) {
      return FALSE;
    }
    return TRUE;
  }// function cacheXmlDatabase
  /**
   * Function used to save API XML into file.
   *
   * @param string $xml The Eve API XML to be cached.
   *
   * @return bool Returns TRUE if XML was cached, FALSE otherwise.
   */
  private function cacheXmlFile($xml) {
    if (empty($xml)) {
      $mess = 'XML was empty' . PHP_EOL;
      trigger_error($mess, E_USER_WARNING);
      return FALSE;
    };// if empty($xml) ...
    // Build cache file path
    $cachePath = realpath(YAPEAL_CACHE . $this->section) . DS;
    if (!is_dir($cachePath)) {
      $mess = 'XML cache ' . $cachePath . ' is not a directory or does not exist';
      trigger_error($mess, E_USER_WARNING);
      return FALSE;
    };// if !is_dir $cachePath ...
    if (!is_writable($cachePath)) {
      $mess = 'XML cache directory ' . $cachePath . ' is not writable';
      trigger_error($mess, E_USER_WARNING);
      return FALSE;
    };// if !is_writable $cachePath ...
    $cacheFile = $cachePath . $this->api . $this->hash . '.xml';
    $ret = file_put_contents($cacheFile, $xml);
    if (FALSE == $ret || $ret == -1) {
      $mess = 'Could not cache XML to ' . $cacheFile;
      trigger_error($mess, E_USER_WARNING);
      return FALSE;
    };// if FALSE == $ret ||...
    return TRUE;
  }// function cacheXmlFile
  /**
   * Function used to fetch API XML from database table and/or file.
   *
   * @return mixed Returns XML if cached copy is available and not expired, else
   * returns FALSE.
   */
  public function getCachedApi() {
    if (TRUE == YAPEAL_CACHE_XML) {
      switch (YAPEAL_CACHE_OUTPUT) {
        case 'both':
          $xml = $this->getCachedDatabase();
          // If not cached in DB try file.
          if (FALSE === $xml) {
            $xml = $this->getCachedFile();
            // If was cached to file but not to database add it to database.
            if (FALSE !== $xml) {
              $this->cacheXmlDatabase($xml);
            };// if FALSE != $xml ...
          };// if FALSE === $xml ...
          return $xml;
        case 'database':
          return $this->getCachedDatabase();
        case 'file':
          return $this->getCachedFile();
        default:
          $mess = 'Invalid value of "' . YAPEAL_CACHE_OUTPUT;
          $mess .= '" for YAPEAL_CACHE_OUTPUT.';
          $mess .= ' Check that the setting in config/yapeal.ini is correct.';
          trigger_error($mess, E_USER_WARNING);
          return FALSE;
      };// switch YAPEAL_CACHE_OUTPUT ...
    };// if TRUE == YAPEAL_CACHE_XML
    return FALSE;
  }// function getCachedApi
  /**
   * Function used to fetch API XML from database table.
   *
   * @return mixed Returns XML if record is available and not expired, else
   * returns FALSE.
   */
  private function getCachedDatabase() {
    try {
      $con = YapealDBConnection::connect(YAPEAL_DSN);
      $sql = 'select `xml`';
      $sql .= ' from `' . YAPEAL_TABLE_PREFIX . 'utilXmlCache`';
      $sql .= ' where `section`=' . $con->qstr($this->section);
      $sql .= ' and `api`=' . $con->qstr($this->api);
      $sql .= ' and `hash`=' . $con->qstr($this->hash);
      $result = $con->GetOne($sql);
      if (!empty($result)) {
        // Check if XML is valid.
        if (FALSE === $this->validateXML($result)) {
          // If cached XML isn't valid anymore should delete it.
          $sql = 'delete from `' . YAPEAL_TABLE_PREFIX . 'utilXmlCache`';
          $sql .= ' where `section`=' . $con->qstr($this->section);
          $sql .= ' and `api`=' . $con->qstr($this->api);
          $sql .= ' and `hash`=' . $con->qstr($this->hash);
          $con->Execute($sql);
          return FALSE;
        };
        //$this->xml = $result;
        return $result;
      };// if ! empty $result ...
      return FALSE;
    }
    catch (Exception $e) {
      return FALSE;
    }
  }// function getCachedDatabase
  /**
   * Function used to fetch API XML from file.
   *
   * @return mixed Returns XML if file is available and not expired, else
   * returns FALSE.
   */
  private function getCachedFile() {
    // Build cache file path
    $cachePath = realpath(YAPEAL_CACHE . $this->section) . DS;
    if (!is_dir($cachePath)) {
      $mess = 'XML cache ' . $cachePath . ' is not a directory or does not exist';
      trigger_error($mess, E_USER_WARNING);
      return FALSE;
    };
    $cacheFile = $cachePath . $this->api . $this->hash . '.xml';
    $result = @file_get_contents($cacheFile);
    if (FALSE === $result) {
      $mess = 'Could not read cached XML file';
      trigger_error($mess, E_USER_NOTICE);
      return FALSE;
    };// if FALSE === $result ...
    if (!empty($result)) {
      // Check if XML is valid.
      if (FALSE === $this->validateXML($result)) {
        // If cached XML isn't valid anymore should delete it.
        @unlink($cacheFile);
        return FALSE;
      };
      //$this->xml = $result;
      return $result;
    };// if ! empty $result ...
    return FALSE;
  }// function getCachedFile
  /**
   * Used to validate basic structure of Eve API XML file.
   *
   * @param string $xml Contents either XML data to be checked or name of file
   * to check.
   *
   * @return bool Return TRUE if The XML seems to have correct structure.
   */
  public function validateXML($xml) {
    // Get a XMLReader instance.
    $xr = new XMLReader();
    // Assume it's a filename if there's no XML header.
    if (FALSE === strpos($xml, "?xml version='1.0'")) {
      $fileName = realpath($xml);
      // Check if file exist and can access it.
      if (!is_file($fileName)) {
        $mess = 'Unable to validate ' . $fileName . PHP_EOL;
        $mess .= 'File either does not exist or could not be accessed.';
        trigger_error($mess, E_USER_WARNING);
        return FALSE;
      };// if !is_file $fileName ...
      // Make sure actually got file opened.
      if (FALSE === $xr->open($fileName)) {
        $mess = 'File could not be opened by XMLReader to validate.';
        trigger_error($mess, E_USER_WARNING);
        return FALSE;
      };// if FALSE == $reader->open $fileName ...
    } else {
      // Pass XML data to XMLReader so it can be checked.
      $xr->XML($xml);
    };// else strpos $xml...
    // XML is now available to start going through it.
    $valid = '';
    $vcount = 0;
    while ($xr->read()) {
      // Check elements.
      if (XMLReader::ELEMENT == $xr->nodeType) {
        // Elements currentTime, result, cachedUntil must exist and be in that
        // order to be valid.
        switch ($xr->localName) {
          case 'currentTime':
            // currentTime must be first.
            if (!empty($valid) || $vcount != 0) {
              return FALSE;
            };
            $valid = 'currentTime';
            ++$vcount;
            break;
          case 'result':
            // result must be in the middle and there must only be one.
            if ($valid != 'currentTime' || $vcount != 1) {
              return FALSE;
            };
            $valid = 'result';
            ++$vcount;
            break;
          case 'cachedUntil':
            // cachedUntil must come third.
            if ($valid != 'result' || $vcount != 2) {
              return FALSE;
            };
            $xr->read();
            // Check if expired.
            $cuntil = strtotime((string)$xr->value . ' +0000');
            if (time() > $cuntil) {
              return FALSE;
            };// if time() ...
            break;
          case 'error':
            // API error returned.
            if (FALSE === $xr->moveToAttribute('code')) {
              $mess = 'API error code not available';
              trigger_error($mess, E_USER_WARNING);
              return FALSE;
            };// if FALSE === $xr->moveToAttribute('code') ...
            $code = (string)$xr->value;
            if (FALSE === $xr->moveToElement()) {
              $mess = 'Could not move back to error element';
              trigger_error($mess, E_USER_WARNING);
              return FALSE;
            };// if FALSE === $xr->moveToElement() ...
            $xr->read();
            $mess = 'Eve API error' . PHP_EOL;
            $mess .= 'Error code: ' . $code . PHP_EOL;
            $mess .= 'Error message: ' . (string)$xr->value;
            // Throw exception
            // Have to use API error code for special API error handling to work.
            throw new YapealApiErrorException($mess, $code);
        };// switch $xr->localName ...
      };// if XMLReader::ELEMENT == $xr->nodeType ...
    };// while $xr ...
    // XML passed tests.
    $xr->close();
    return TRUE;
  }// function validateXML
}
?>
