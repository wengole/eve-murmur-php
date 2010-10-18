<?php
/**
 * Contains YapealDBConnection class.
 *
 *
 * PHP version 5
 *
 * LICENSE: This file is part of Yet Another Php Eve Api library also know
 * as Yapeal.
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
 * @internal Only let this code be included or required not ran directly.
 */
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
  exit();
};
/**
 * A factory to manage ADOdb connections to databases.
 *
 * @package Yapeal
 * @subpackage ADOdb
 */
class YapealDBConnection {
  /**
   * @var object Hold instance of the class.
   */
  private static $instance;
  /**
   * @var resource List of ADOdb connection resources.
   */
  private static $connections = array();
  /**
   * Only way to make instance is through
   * {@link YapealDBConnection::getInstance() getInstance()}.
   */
  private function __construct() {
    self::$connections = array();
  }// function __construct
  /**
   * No backdoor through cloning either.
   */
  private function __clone() {}// function __clone
  /**
   * Used to get an instance of the class.
   *
   * @return YapealDBConnection Returns an instance of the class.
   */
  protected static function getInstance() {
    if (!(self::$instance instanceof self)) {
      self::$instance = new self();
    };
    return self::$instance;
  }// function getInstance
  /**
   * Use to get a ADOdb connection object.
   *
   * This method will create a new ADOdb connection for each DSN it is passed and
   * return the same connection for any other requests for the same DSN. It was
   * developed to get around some problems with how ADOdb handles connections
   * that wasn't compatable with what I needed.
   *
   * @param string $dsn An ADOdb compatible connection string.
   *
   * @return object Returns ADOdb connection object.
   *
   * @throws InvalidArgumentException if $dsn is empty or if $dsn isn't a string
   * it will throw InvalidArgumentException.
   * @throws ADODB_Exception Passes through any problem with actual connection
   * from ADOdb.
   */
  public static function connect($dsn) {
    $instance = self::getInstance();
    if (empty($dsn) || !is_string($dsn)) {
      throw new InvalidArgumentException('Bad value passed for $dsn');
    };
    $hash = hash('sha1', $dsn);
    if (!array_key_exists($hash, self::$connections)) {
      require_once YAPEAL_CLASS . 'ADODB_Exception.php';
      require_once YAPEAL_ADODB . 'adodb.inc.php';
      $ado = NewADOConnection($dsn);
      $ado->debug = FALSE;
      $ado->SetFetchMode(ADODB_FETCH_ASSOC);
      $ado->Execute('set names utf8');
      $ado->Execute('set time_zone="+0:00"');
      self::$connections[$hash] = $ado;
    };
    return self::$connections[$hash];
  }// function connect
/**
 * Function to close and release all existing ADOdb connections.
 *
 * @throws ADODB_Exception Passes through any problem with actual connection
 * from ADOdb.
 */
public static function releaseAll() {
  if (!empty(self::$connections)) {
    foreach (self::$connections as $k => $v) {
      self::$connections[$k]->Close();
      self::$connections[$k] = NULL;
      unset(self::$connections[$k]);
    };// foreach self::$connections ....
  };// if !empty...
}// function releaseAll
}
?>
