<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Fields config handling
 *
 * PHP version 5
 *
 * Copyright © 2009-2014 The Galette Team
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
 * @category  Entity
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-26
 */

namespace Galette\Entity;

use Analog\Analog;
use Zend\Db\Adapter\Adapter;

/**
 * Fields config class for galette :
 * defines fields mandatory, order and visibility
 *
 * @category  Entity
 * @name      FieldsConfig
 * @package   Galette
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-03-26
 */
class FieldsConfig
{
    const HIDDEN = 0;
    const VISIBLE = 1;
    const ADMIN = 2;

    const TYPE_STR = 0;
    const TYPE_HIDDEN = 1;
    const TYPE_BOOL = 2;
    const TYPE_INT = 3;
    const TYPE_DEC = 4;
    const TYPE_DATE = 5;
    const TYPE_TXT = 6;
    const TYPE_PASS = 7;
    const TYPE_EMAIL = 8;
    const TYPE_URL = 9;
    const TYPE_RADIO = 10;
    const TYPE_SELECT = 11;

    private $_all_required;
    private $_all_visibles;
    //private $error = array();
    private $_categorized_fields = array();
    private $_table;
    private $_defaults = null;
    private $_cats_defaults = null;

    private $_form_elements = array();
    private $_hidden_elements = array();

    private $_staff_fields = array(
        'activite_adh',
        'id_statut',
        'bool_exempt_adh',
        'date_crea_adh',
        'info_adh'
    );
    private $_admin_fields = array(
        'bool_admin_adh'
    );

    const TABLE = 'fields_config';

    /*
     * Fields that are not visible in the
     * form should not be visible here.
     */
    private $_non_required = array(
        'id_adh',
        'date_echeance',
        'bool_display_info',
        'bool_exempt_adh',
        'bool_admin_adh',
        'activite_adh',
        'date_crea_adh',
        'date_modif_adh',
        //Fields we do not want to be set as required
        'societe_adh',
        'id_statut',
        'pref_lang',
        'sexe_adh'
    );

    private $_non_form_elements = array(
        'date_echeance',
        'date_modif_adh'
    );

    private $_non_display_elements = array(
        'date_echeance',
        'mdp_adh',
        'titre_adh',
        'sexe_adh',
        'prenom_adh',
        'adresse2_adh'
    );

    /**
     * Default constructor
     *
     * @param string  $table         the table for which to get fields configuration
     * @param array   $defaults      default values
     * @param array   $cats_defaults default categories values
     * @param boolean $install       Are we calling from installer?
     */
    function __construct($table, $defaults, $cats_defaults, $install = false)
    {
        $this->_table = $table;
        $this->_defaults = $defaults;
        $this->_cats_defaults = $cats_defaults;
        $this->_all_required = array();
        $this->_all_visibles = array();
        //prevent check at install time...
        if ( !$install ) {
            $this->load();
            $this->_checkUpdate();
        }
    }

    /**
     * Load current preferences from database.
     *
     * @return boolean
     */
    public function load()
    {
        global $zdb, $preferences;

        try {
            $select = $zdb->select(self::TABLE);
            $select
                ->where(array('table_name' => $this->_table))
                ->order(array(FieldsCategories::PK, 'position ASC'));

            $results = $zdb->execute($select);

            $this->_categorized_fields = null;
            foreach ( $results as $k ) {
                if ($k->field_id === 'id_adh' && !$preferences->pref_show_id) {
                    $k->visible = false;
                }
                $f = array(
                    'field_id'  => $k->field_id,
                    'label'     => $this->_defaults[$k->field_id]['label'],
                    'category'  => $k->id_field_category,
                    'visible'   => $k->visible,
                    'required'  => $k->required,
                    'propname'  => $this->_defaults[$k->field_id]['propname']
                );
                $this->_categorized_fields[$k->id_field_category][] = $f;

                //array of all required fields
                if ( $k->required == 1 ) {
                    $this->_all_required[$k->field_id] = $k->required;
                }

                //array of all fields visibility
                $this->_all_visibles[$k->field_id] = $k->visible;
            }
            return true;
        } catch (\Exception $e) {
            Analog::log(
                'Fields configuration cannot be loaded!',
                Analog::URGENT
            );
            return false;
        }
    }

    /**
     * Is a field set as required?
     *
     * @param string $field Field name
     *
     * @return boolean
     */
    public function isRequired($field)
    {
        return isset($this->_all_required[$field]);
    }

    /**
     * Temporary set a field as not required
     * (password for existing members for example)
     *
     * @param string $field Field name
     *
     * @return void
     */
    public function setNotRequired($field)
    {
        if ( isset($this->_all_required[$field]) ) {
            unset($this->_all_required[$field]);
        }

        foreach ($this->_categorized_fields as &$cat) {
            foreach ( $cat as &$f ) {
                if ( $f['field_id'] === $field ) {
                    $f['required'] = 0;
                    return;
                }
            }
        }
    }

    /**
     * Checks if all fields are present in the database.
     *
     * For now, this function only checks if count matches.
     *
     * @return void
     */
    private function _checkUpdate()
    {
        global $zdb;
        $class = get_class($this);

        try {
            $_all_fields = array();
            if ( is_array($this->_categorized_fields) ) {
                array_walk(
                    $this->_categorized_fields,
                    function ($cat) use (&$_all_fields) {
                        $field = null;
                        array_walk(
                            $cat,
                            function ($f) use (&$field) {
                                $field[$f['field_id']] = $f;
                            }
                        );
                        $_all_fields = array_merge($_all_fields, $field);
                    }
                );
            } else {
                //hum... no records. Let's check if any category exists
                $select = $zdb->select(FieldsCategories::TABLE);
                $results = $zdb->execute($select);

                if ( $results->count() == 0 ) {
                    //categories are missing, add them
                    $categories = new FieldsCategories($this->_cats_defaults);
                    $categories->installInit($zdb);
                }
            }

            if ( count($this->_defaults) != count($_all_fields) ) {
                Analog::log(
                    'Fields configuration count for `' . $this->_table .
                    '` columns does not match records. Is : ' .
                    count($_all_fields) . ' and should be ' .
                    count($this->_defaults),
                    Analog::WARNING
                );

                $params = array();
                foreach ($this->_defaults as $k=>$f) {
                    if ( !isset($_all_fields[$k]) ) {
                        Analog::log(
                            'Missing field configuration for field `' . $k . '`',
                            Analog::INFO
                        );
                        $required = $f['required'];
                        if ( $required === false ) {
                            $required = 'false';
                        }
                        $params[] = array(
                            'field_id'    => $k,
                            'table_name'  => $this->_table,
                            'required'    => $required,
                            'visible'     => $f['visible'],
                            'position'    => $f['position'],
                            'category'    => $f['category'],
                        );
                    }
                }

                if ( count($params) > 0 ) {
                    $this->_insert($zdb, $params);
                    $this->load();
                }
            }
        } catch (\Exception $e) {
            Analog::log(
                '[' . $class . '] An error occured while checking update for ' .
                'fields configuration for table `' . $this->_table . '`. ' .
                $e->getMessage(),
                Analog::ERROR
            );
            throw $e;
        }
    }

    /**
     * Set default fields configuration at install time. All previous
     * existing values will be dropped first, including fields categories.
     *
     * @param Db $zdb Database instance
     *
     * @return boolean|Exception
     */
    public function installInit($zdb)
    {
        try {
            $fields = array_keys($this->_defaults);
            $categories = new FieldsCategories($this->_cats_defaults);

            //first, we drop all values
            $delete = $zdb->delete(self::TABLE);
            $delete->where(
                array('table_name' => $this->_table)
            );
            $zdb->execute($delete);
            //take care of fields categories, for db relations
            $categories->installInit($zdb);

            $fields = array_keys($this->_defaults);
            foreach ( $fields as $f ) {
                //build default config for each field
                $required = $this->_defaults[$f]['required'];
                if ( $required === false ) {
                    $required = 'false';
                }
                $params[] = array(
                    'field_id'    => $f,
                    'table_name'  => $this->_table,
                    'required'    => $required,
                    'visible'     => $this->_defaults[$f]['visible'],
                    'position'    => $this->_defaults[$f]['position'],
                    'category'    => $this->_defaults[$f]['category'],
                );
            }
            $this->_insert($zdb, $params);

            Analog::log(
                'Default fields configuration were successfully stored.',
                Analog::INFO
            );
            return true;
        } catch (\Exception $e) {
            $messages = array();
            do {
                $messages[] = $e->getMessage();
            } while ($e = $e->getPrevious());

            Analog::log(
                'Unable to initialize default fields configuration.' .
                implode("\n", $messages),
                Analog::ERROR
            );
            return $e;
        }
    }

    /**
     * Get non required fields
     *
     * @return array
     */
    public function getNonRequired()
    {
        return $this->_non_required;
    }

    /**
     * Retrieve form elements
     *
     * @param boolean $selfs True if we're called from self subscirption page
     *
     * @return array
     */
    public function getFormElements($selfs = false)
    {
        global $zdb, $log, $login, $members_fields_cats;

        if ( !count($this->_form_elements) > 0 ) {
            //get columns descriptions
            $columns = $zdb->getColumns($this->_table);

            $categories = FieldsCategories::getList();
            try {
                foreach ( $categories as $c ) {
                    $cpk = FieldsCategories::PK;
                    $cat_label = null;
                    foreach ($members_fields_cats as $conf_cat) {
                        if ( $conf_cat['id'] == $c->$cpk ) {
                            $cat_label = $conf_cat['category'];
                            break;
                        }
                    }
                    if ( $cat_label === null ) {
                        $cat_label = $c->category;
                    }
                    $cat = (object) array(
                        'id' => $c->$cpk,
                        'label' => $cat_label,
                        'elements' => array()
                    );

                    $elements = $this->_categorized_fields[$c->$cpk];
                    $cat->elements = array();

                    foreach ( $elements as $elt ) {
                        $o = (object)$elt;

                        if ( in_array($o->field_id, $this->_non_form_elements)
                            || $selfs && $this->isSelfExcluded($o->field_id)
                        ) {
                            continue;
                        }

                        if ( !($o->visible == self::ADMIN
                            && (!$login->isAdmin() && !$login->isStaff()) )
                        ) {
                            if ( $o->visible == self::HIDDEN ) {
                                $o->type = self::TYPE_HIDDEN;
                            } else if (preg_match('/date/', $o->field_id) ) {
                                $o->type = self::TYPE_DATE;
                            } else if (preg_match('/bool/', $o->field_id) ) {
                                $o->type = self::TYPE_BOOL;
                            } else if ( $o->field_id == 'titre_adh'
                                || $o->field_id == 'pref_lang'
                                || $o->field_id == 'id_statut'
                            ) {
                                $o->type = self::TYPE_SELECT;
                            } else if ( $o->field_id == 'sexe_adh' ) {
                                $o->type = self::TYPE_RADIO;
                            } else {
                                $o->type = self::TYPE_STR;
                            }

                            //retrieve field informations from DB
                            foreach ( $columns as $column ) {
                                if ( $column->getName() === $o->field_id ) {
                                    $o->max_length 
                                        = $column->getCharacterMaximumLength();
                                    $o->default = $column->getColumnDefault();
                                    $o->datatype = $column->getDataType();
                                    break;
                                }
                            }

                            if ( $o->type === self::TYPE_HIDDEN ) {
                                $this->_hidden_elements[] = $o;
                            } else {
                                $cat->elements[$o->field_id] = $o;
                            }
                        }
                    }

                    if ( count($cat->elements) > 0 ) {
                        $this->_form_elements[] = $cat;
                    }
                }
            } catch ( Exception $e ) {
                $log->log(
                    'An error occured getting form elements',
                    Analog::ERROR
                );
            }
        }
        return array(
            'fieldsets' => $this->_form_elements,
            'hiddens'   => $this->_hidden_elements
        );
    }

    /**
     * Retrieve display elements
     *
     * @return array
     */
    public function getDisplayElements()
    {
        global $log, $login, $members_fields_cats;

        $display_elements = array();

        if ( !count($this->_form_elements) > 0 ) {
            $categories = FieldsCategories::getList();
            try {
                foreach ( $categories as $c ) {
                    $cpk = FieldsCategories::PK;
                    $cat_label = null;
                    foreach ($members_fields_cats as $conf_cat) {
                        if ( $conf_cat['id'] == $c->$cpk ) {
                            $cat_label = $conf_cat['category'];
                            break;
                        }
                    }
                    if ( $cat_label === null ) {
                        $cat_label = $c->category;
                    }
                    $cat = (object) array(
                        'id' => $c->$cpk,
                        'label' => $cat_label,
                        'elements' => array()
                    );

                    $elements = $this->_categorized_fields[$c->$cpk];
                    $cat->elements = array();

                    foreach ( $elements as $elt ) {
                        $o = (object)$elt;

                        if ( in_array($o->field_id, $this->_non_display_elements) ) {
                            continue;
                        }

                        if ( !($o->visible == self::ADMIN
                            && (!$login->isAdmin() && !$login->isStaff()) )
                        ) {
                            if ( $o->visible == self::HIDDEN ) {
                                continue;
                            }

                            $cat->elements[$o->field_id] = $o;
                        }
                    }

                    if ( count($cat->elements) > 0 ) {
                        $display_elements[] = $cat;
                    }
                }
            } catch ( Exception $e ) {
                $log->log(
                    'An error occured getting display elements',
                    Analog::ERROR
                );
            }
        }
        return $display_elements;
    }

    /**
     * Get required fields
     *
     * @return array of all required fields. Field names = keys
     */
    public function getRequired()
    {
        return $this->_all_required;
    }

    /**
     * Get visible fields
     *
     * @return array of all visibles fields
     */
    public function getVisibilities()
    {
        return $this->_all_visibles;
    }

    /**
     * Get visibility for specified field
     *
     * @param string $field The requested field
     *
     * @return boolean
     */
    public function getVisibility($field)
    {
        return $this->_all_visibles[$field];
    }

    /**
     * Get all fields with their categories
     *
     * @return array
     */
    public function getCategorizedFields()
    {
        return $this->_categorized_fields;
    }

    /**
     * Get all fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set fields
     *
     * @param array $fields categorized fields array
     *
     * @return boolean
     */
    public function setFields($fields)
    {
        $this->_categorized_fields = $fields;
        return $this->_store();
    }

    /**
     * Store config in database
     *
     * @return boolean
     */
    private function _store()
    {
        global $zdb;

        $class = get_class($this);

        try {
            $zdb->connection->beginTransaction();

            $update = $zdb->update(self::TABLE);
            $update->set(
                array(
                    'required'              => ':required',
                    'visible'               => ':visible',
                    'position'              => ':position',
                    FieldsCategories::PK    => ':category'
                )
            )->where(
                array(
                    'field_id'      => ':field_id',
                    'table_name'    => $this->_table
                )
            );
            $stmt = $zdb->sql->prepareStatementForSqlObject($update);

            $params = null;
            foreach ( $this->_categorized_fields as $cat ) {
                foreach ( $cat as $pos=>$field ) {
                    if ( in_array($field['field_id'], $this->_non_required) ) {
                        $field['required'] = 'false';
                    }
                    $params = array(
                        'required'  => $field['required'],
                        'visible'   => $field['visible'],
                        'position'  => $pos,
                        FieldsCategories::PK => $field['category'],
                        'where1'    => $field['field_id']
                    );
                    $stmt->execute($params);
                }
            }

            Analog::log(
                '[' . $class . '] Fields configuration stored successfully! ',
                Analog::DEBUG
            );
            Analog::log(
                str_replace(
                    '%s',
                    $this->_table,
                    '[' . $class . '] Fields configuration for table %s stored ' .
                    'successfully.'
                ),
                Analog::INFO
            );

            $zdb->connection->commit();
            return true;
        } catch (\Exception $e) {
            $zdb->connection->rollBack();
            Analog::log(
                '[' . $class . '] An error occured while storing fields ' .
                'configuration for table `' . $this->_table . '`.' .
                $e->getMessage(),
                Analog::ERROR
            );
            Analog::log(
                $e->getTraceAsString(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Migrate old required fields configuration
     * Only needeed for 0.7.4 upgrade
     * (should have been 0.7.3 - but I missed that.)
     *
     * @param Db $zdb Database instance
     *
     * @return boolean
     */
    public function migrateRequired($zdb)
    {
        $old_required = null;

        try {
            $select = $zdb->select('required');
            $select->from(PREFIX_DB . 'required');

            $old_required = $zdb->execute($select);
        } catch ( \Exception $pe ) {
            Analog::log(
                'Unable to retrieve required fields_config. Maybe ' .
                'the table does not exists?',
                Analog::WARNING
            );
            //not a blocker
            return true;
        }

        $zdb->connection->beginTransaction();
        try {
            $update = $zdb->update(self::TABLE);
            $update->set(
                array(
                    'required'  => ':required'
                )
            )->where(
                array(
                    'field_id'      => ':field_id',
                    'table_name'    => $this->_table
                )
            );

            $stmt = $zdb->sql->prepareStatementForSqlObject($update);

            foreach ( $old_required as $or ) {
                /** Why where parameter is named where1 ?? */
                $stmt->execute(
                    array(
                        'required'  => ($or->required === false) ?  'false' : true,
                        'where1'    => $or->field_id
                    )
                );
            }

            $class = get_class($this);
            Analog::log(
                str_replace(
                    '%s',
                    $this->_table,
                    '[' . $class . '] Required fields for table %s upgraded ' .
                    'successfully.'
                ),
                Analog::INFO
            );

            $zdb->db->query(
                'DROP TABLE ' . PREFIX_DB . 'required',
                Adapter::QUERY_MODE_EXECUTE
            );

            $zdb->connection->commit();
            return true;
        } catch ( \Exception $e ) {
            $zdb->connection->rollBack();
            Analog::log(
                'An error occured migrating old required fields. | ' .
                $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Insert values in database
     *
     * @param Db    $zdb    Database instance
     * @param array $values Values to insert
     *
     * @return void
     */
    private function _insert($zdb, $values)
    {
        $insert = $zdb->insert(self::TABLE);
        $insert->values(
            array(
                'field_id'      => ':field_id',
                'table_name'    => ':table_name',
                'required'      => ':required',
                'visible'       => ':visible',
                FieldsCategories::PK => ':category',
                'position'      => ':position'
            )
        );
        $stmt = $zdb->sql->prepareStatementForSqlObject($insert);

        foreach ( $values as $d ) {
            $stmt->execute(
                array(
                    'field_id'      => $d['field_id'],
                    'table_name'    => $d['table_name'],
                    'required'      => $d['required'],
                    'visible'       => $d['visible'],
                    FieldsCategories::PK => $d['category'],
                    'position'      => $d['position']
                )
            );
        }
    }

    /**
     * Does field should be displayed in self subscription page
     *
     * @param string $name Field name
     *
     * @return boolean
     */
    public function isSelfExcluded($name)
    {
        return in_array(
            $name,
            array_merge(
                $this->_staff_fields,
                $this->_admin_fields
            )
        );
    }
}
