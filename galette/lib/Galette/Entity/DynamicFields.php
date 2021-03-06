<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Dynamic fields handler
 *
 * PHP version 5
 *
 * Copyright © 2011-2014 The Galette Team
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
 * @copyright 2011-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2011-06-20
 */

namespace Galette\Entity;

use Analog\Analog;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\Expression as PredicateExpression;
use Galette\Core\Db;
use Galette\Core\Login;
use Galette\DynamicFieldsTypes\Separator;
use Galette\DynamicFieldsTypes\Text;
use Galette\DynamicFieldsTypes\Line;
use Galette\DynamicFieldsTypes\Choice;
use Galette\DynamicFieldsTypes\Date;
use Galette\DynamicFieldsTypes\Boolean;
use Galette\DynamicFieldsTypes\File;
use Galette\DynamicFieldsTypes\DynamicFieldType;
use Galette\Repository\DynamicFieldsTypes;

/**
 * Dynamic fields handler for Galette
 *
 * @name DynamicFields
 * @category  Entity
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2011-2014 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

class DynamicFields
{
    const TABLE = 'dynamic_fields';

    private $dynamic_fields = [];
    private $current_values = [];
    private $form_name;
    private $item_id;

    private $errors = array();

    private $zdb;

    private $insert_stmt;
    private $update_stmt;
    private $delete_stmt;

    /**
     * Default constructor
     *
     * @param Db    $zdb      Database instance
     * @param Login $login    Login instance
     * @param mixed $instance Object instance
     */
    public function __construct(Db $zdb, Login $login, $instance = null)
    {
        $this->zdb = $zdb;
        $this->login = $login;
        if ($instance !== null) {
            $this->load($instance);
        }
    }

    /**
     * Load dynaic fields values for specified object
     *
     * @param mixed $object Object instance
     *
     * @return array|false
     */
    public function load($object)
    {
        switch (get_class($object)) {
            case 'Galette\Entity\Adherent':
                $this->form_name = 'adh';
                break;
            case 'Galette\Entity\Contribution':
                $this->form_name = 'contrib';
                break;
            case 'Galette\Entity\Transaction':
                $this->form_name = 'trans';
                break;
            default:
                throw new \RuntimeException('Class ' . get_class($object) . ' does not handle dynamic fields!');
                break;
        }

        try {
            $this->item_id = $object->id;
            $fields = new DynamicFieldsTypes($this->zdb);
            $this->dynamic_fields = $fields->getList($this->form_name, $this->login);

            $select = $this->zdb->select(self::TABLE, 'd');
            $select->join(
                array('t' => PREFIX_DB . DynamicFieldType::TABLE),
                'd.' . DynamicFieldType::PK . '=t.' . DynamicFieldType::PK,
                array('field_id')
            )->where(
                array(
                    'item_id'       => $this->item_id,
                    'd.field_form'  => $this->form_name
                )
            );

            if (count($this->dynamic_fields)) {
                $select->where->in('d.' . DynamicFieldType::PK, array_keys($this->dynamic_fields));
            }

            $results = $this->zdb->execute($select);
            if ($results->count() > 0) {
                $dfields = array();

                foreach ($results as $f) {
                    if (isset($this->dynamic_fields[$f->{DynamicFieldType::PK}])) {
                        $field = $this->dynamic_fields[$f->{DynamicFieldType::PK}];
                        if ($field->hasFixedValues()) {
                            $choices = $field->getValues();
                            $f['text_val'] = $choices[$f->field_val];
                        }
                        $this->current_values[$f->{DynamicFieldType::PK}][] = array_filter(
                            (array)$f,
                            function ($k) {
                                return $k != DynamicFieldType::PK;
                            },
                            ARRAY_FILTER_USE_KEY
                        );
                    } else {
                        Analog::log(
                            'Dynamic values found for ' . get_class($object) . ' #' . $this->item_id .
                            '; but no dynamic field configured!',
                            Analog::WARNING
                        );
                    }
                }
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Analog::log(
                __METHOD__ . ' | ' . $e->getMessage(),
                Analog::WARNING
            );
            return false;
        }
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->dynamic_fields;
    }

    /**
     * Get values
     *
     * @param integer $field Field ID
     *
     * @return array
     */
    public function getValues($field)
    {
        if (isset($this->current_values[$field])) {
            return $this->current_values[$field];
        } else {
            $this->current_values[$field][] = [
                'item_id'       => '',
                'field_form'    => $this->dynamic_fields[$field]->getForm(),
                'val_index'     => '',
                'field_val'     => '',
                'is_new'        => true
            ];
        }
    }

    /**
     * Set field value
     *
     * @param integer $item  Item ID
     * @param integer $field Field ID
     * @param integer $index Value index
     * @param mixed   $value Value
     *
     * @return void
     */
    public function setValue($item, $field, $index, $value)
    {
        $idx = $index - 1;
        if (isset($this->current_values[$field][$idx])) {
            $this->current_values[$field][$idx]['field_val'] = $value;
        } else {
            $this->current_values[$field][$idx] = [
                'item_id'       => $item,
                'field_form'    => $this->dynamic_fields[$field]->getForm(),
                'val_index'     => $index,
                'field_val'     => $value,
                'is_new'        => true
            ];
        }
    }

    /**
     * Unset field value
     *
     * @param integer $item  Item ID
     * @param integer $field Field ID
     * @param integer $index Value index
     *
     * @return void
     */
    public function unsetValue($item, $field, $index)
    {
        $idx = $index - 1;
        if (isset($this->current_values[$field][$idx])) {
            unset($this->current_values[$field][$idx]);
        }
    }

    /**
     * Store values
     *
     * @param integer $item_id Curent item id to use (will be used if current item_id is 0)
     *
     * @return boolean
     */
    public function storeValues($item_id = null)
    {
        try {
            if ($item_id !== null && ($this->item_id == null || $this->item_id == 0)) {
                $this->item_id = $item_id;
            }
            $this->zdb->connection->beginTransaction();

            $this->handleRemovals();

            foreach ($this->current_values as $field_id => $values) {
                foreach ($values as $value) {
                    $value[DynamicFieldType::PK] = $field_id;
                    if ($value['item_id'] == 0) {
                        $value['item_id'] = $this->item_id;
                    }

                    if (isset($value['is_new'])) {
                        if ($this->insert_stmt === null) {
                            $insert = $this->zdb->insert(self::TABLE);
                            $insert->values([
                                'item_id'       => ':item_id',
                                'field_id'      => ':field_id',
                                'field_form'    => ':field_form',
                                'val_index'     => ':val_index',
                                'field_val'     => ':field_val'
                            ]);
                            $this->insert_stmt = $this->zdb->sql->prepareStatementForSqlObject($insert);
                        }
                        unset($value['is_new']);
                        $this->insert_stmt->execute($value);
                    } else {
                        if ($this->update_stmt === null) {
                            $update = $this->zdb->update(self::TABLE);
                            $update->set([
                                'field_val'     => ':field_val',
                                'val_index'     => ':val_index'
                            ])->where([
                                'item_id'       => ':item_id',
                                'field_id'      => ':field_id',
                                'field_form'    => ':field_form',
                                'val_index'     => ':val_index'
                            ]);
                            $this->update_stmt = $this->zdb->sql->prepareStatementForSqlObject($update);
                        }
                        $params = [
                            'field_val' => $value['field_val'],
                            'val_index' => $value['val_index'],
                            'where1'    => $value['item_id'],
                            'where2'    => $value['field_id'],
                            'where3'    => $value['field_form'],
                            'where4'    => isset($value['old_val_index']) ?
                                $value['old_val_index'] :
                                $value['val_index']
                        ];
                        $this->update_stmt->execute($params);
                    }
                }
            }

            $this->zdb->connection->commit();
            return true;
        } catch (\Exception $e) {
            $this->zdb->connection->rollBack();
            Analog::log(
                'An error occured storing dynamic field. Form name: ' . $this->form_name .
                ' | Error was: ' . $e->getMessage(),
                Analog::ERROR
            );
            return false;
        }
    }

    /**
     * Handle values that have been removed
     *
     * @return boolean
     */
    private function handleRemovals()
    {
        $fields = new DynamicFieldsTypes($this->zdb);
        $this->dynamic_fields = $fields->getList($this->form_name, $this->login);

        $select = $this->zdb->select(self::TABLE, 'd');
        $select->join(
            array('t' => PREFIX_DB . DynamicFieldType::TABLE),
            'd.' . DynamicFieldType::PK . '=t.' . DynamicFieldType::PK,
            array('field_id')
        )->where(
            array(
                'item_id'       => $this->item_id,
                'd.field_form'  => $this->form_name
            )
        );

        $fromdb = [];
        $results = $this->zdb->execute($select);
        if ($results->count() > 0) {
            foreach ($results as $result) {
                $fromdb[$result->field_id . '_' . $result->val_index] = [
                    'where1'    => $this->item_id,
                    'where2'    => $this->form_name,
                    'where3'    => $result->field_id,
                    'where4'    => $result->val_index
                ];
            }
        }

        if (!count($fromdb)) {
            //no entry in database, nothing to do.
            return;
        }

        foreach ($this->current_values as $field_id => $values) {
            foreach ($values as $value) {
                $key = $field_id . '_' . $value['val_index'];
                if (isset($fromdb[$key])) {
                    unset($fromdb[$key]);
                }
            }
        }

        if (count($fromdb)) {
            foreach ($fromdb as $entry) {
                if ($this->delete_stmt === null) {
                    $delete = $this->zdb->delete(self::TABLE);
                    $delete->where([
                        'item_id'       => ':item_id',
                        'field_form'    => ':field_form',
                        'field_id'      => ':field_id',
                        'val_index'     => ':val_index'
                    ]);
                    $this->delete_stmt = $this->zdb->sql->prepareStatementForSqlObject($delete);
                }
                $this->delete_stmt->execute($entry);
                //update val index
                $field_id = $entry['where3'];
                if (isset($this->current_values[$field_id])
                    && count($this->current_values[$field_id])
                ) {
                    $val_index = (int)$entry['where4'];
                    foreach ($this->current_values[$field_id] as &$current) {
                        if ((int)$current['val_index'] === $val_index + 1) {
                            $current['val_index'] = $val_index;
                            ++$val_index;
                            $current['old_val_index'] = $val_index;
                        }
                    }
                }
            }
        }
    }
}
