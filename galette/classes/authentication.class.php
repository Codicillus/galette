<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Abstract authentication class for galette
 *
 * PHP version 5
 *
 * Copyright © 2009-2011 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Authentication
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-02-28
 */

/**
 * Abstract authentication class for galette
 *
 * @category  Classes
 * @name      Authentication
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-02-28
 */

abstract class Authentication
{
    private $_login;
    private $_passe;
    private $_name;
    private $_surname;
    private $_admin = false;
    private $_id; /** FIXME: not used! */
    private $_lang; /** FIXME: not used! */
    private $_logged = false;
    private $_active = false;
    private $_superadmin = false;

    /**
    * Default constructor
    */
    public function __construct()
    {
    }

    /**
    * Logs in user.
    *
    * @param string $user  user's login
    * @param string $passe md5 hashed password
    *
    * @return integer state :
    *     '-1' if there were an error
    *    '-10' if user cannot login (mistake or user doesn't exists)
    *    '1' if user were logged in successfully
    */
    abstract public function logIn($user, $passe);

    /**
    * Does this login already exists ?
    * These function should be used for setting admin login into Preferences
    *
    * @param string $user the username
    *
    * @return true if the username already exists, false otherwise
    */
    abstract public function loginExists($user);

    /**
    * Login for the superuser
    *
    * @param string $login name
    *
    * @return void
    */
    public function logAdmin($login)
    {
        $this->_logged = true;
        $this->_name = 'Admin';
        $this->_login = $login;
        $this->_admin = true;
        $this->_active = true;
        //a flag for super admin only, since it's not a regular user
        $this->_superadmin = true;
    }

    /**
    * Log out user and unset variables
    *
    * @return void
    */
    public function logOut()
    {
        $this->_logged = false;
        $this->_name = null;
        $this->_login = null;
        $this->_admin = false;
        $this->_active = false;
        $this->_superadmin = false;
    }

    /**
    * Is user logged-in?
    *
    * @return bool
    */
    public function isLogged()
    {
        return $this->_logged;
    }

    /**
    * Is user admin?
    *
    * @return bool
    */
    public function isAdmin()
    {
        return $this->_admin;
    }

    /**
    * Is user super admin?
    *
    * @return bool
    */
    public function isSuperAdmin()
    {
        return $this->_superadmin;
    }

    /**
    * Is user active?
    *
    * @return bool
    */
    public function isActive()
    {
        return $this->_active;
    }

    /**
    * Global getter method
    *
    * @param string $name name of the property we want to retrive
    *
    * @return false|object the called property
    */
    public function __get($name)
    {
        $forbidden = array('logged', 'admin', 'active');
        $rname = '_' . $name;
        if ( !in_array($name, $forbidden) && isset($this->$rname) ) {
            return $this->$rname;
        } else {
            return false;
        }
    }

    /**
    * Global setter method
    *
    * @param string $name  name of the property we want to assign a value to
    * @param object $value a relevant value for the property
    *
    * @return void
    */
    public function __set($name, $value)
    {
        $name = '_' . $name;
        $this->$name = $value;
    }
}
?>