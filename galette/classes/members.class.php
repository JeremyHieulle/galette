<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Members class
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
 * @category  Classes
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.7dev - 2009-02-28
 */

/** @ignore */
require_once 'adherent.class.php';
require_once 'status.class.php';

/**
 * Members class for galette
 *
 * @name Members
 * @category  Classes
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2011 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */
class Members
{
    const TABLE = Adherent::TABLE;
    const PK = Adherent::PK;

    const SHOW_LIST = 0;
    const SHOW_PUBLIC_LIST = 1;
    const SHOW_ARRAY_LIST = 2;
    const SHOW_STAFF = 3;

    const FILTER_NAME = 0;
    const FILTER_ADRESS = 1;
    const FILTER_MAIL = 2;
    const FILTER_JOB = 3;
    const FILTER_INFOS = 4;

    const ORDERBY_NAME = 0;
    const ORDERBY_NICKNAME = 1;
    const ORDERBY_STATUS = 2;
    const ORDERBY_FEE_STATUS = 3;
    const ORDERBY_ID = 4;

    const NON_STAFF_MEMBERS = 30;

    private $_filter = null;
    private $_count = null;

    /**
    * Default constructor
    */
    public function __construct()
    {
    }


    /**
    * Get staff members list
    *
    * @param bool    $as_members return the results as an array of
    *                               Member object.
    * @param array   $fields     field(s) name(s) to get. Should be a string or
    *                               an array. If null, all fields will be
    *                               returned
    * @param boolean $filter     proceed filter, defaults to true
    * @param boolean $count      true if we want to count members
    *
    * @return Adherent[]|ResultSet
    */
    public function getStaffMembersList(
        $as_members=false, $fields=null, $filter=true, $count=true
    ) {
        return $this->getMembersList(
            $as_members,
            $fields,
            $filter,
            $count,
            true
        );
    }

    /**
    * Get members list
    *
    * @param bool    $as_members return the results as an array of
    *                               Member object.
    * @param array   $fields     field(s) name(s) to get. Should be a string or
    *                               an array. If null, all fields will be
    *                               returned
    * @param boolean $filter     proceed filter, defaults to true
    * @param boolean $count      true if we want to count members
    * @param boolean $staff      true if we want only staff members
    *
    * @return Adherent[]|ResultSet
    */
    public function getMembersList(
        $as_members=false, $fields=null, $filter=true, $count=true, $staff=false
    ) {
        global $mdb, $log, $varslist;

        $_mode = self::SHOW_LIST;
        if ( $staff !== false ) {
            $_mode = self::SHOW_STAFF;
        }

        $query = self::_buildSelect(
            $_mode, $fields, $filter, false, $count
        );
        if ( $staff !== false ) {
            $query .= ' WHERE p.priorite_statut < ' . self::NON_STAFF_MEMBERS;
        }

        //add limits to retrieve only relavant rows
        $varslist->setLimit();

        $result = $mdb->query($query);
        if (MDB2::isError($result)) {
            $log->log(
                'Cannot list members | ' . $result->getMessage() . '(' .
                $result->getDebugInfo() . ')', PEAR_LOG_WARNING
            );
            return false;
        }

        $members = array();
        if ( $as_members ) {
            foreach ( $result->fetchAll() as $row ) {
                $members[] = new Adherent($row);
            }
        } else {
            $members = $result->fetchAll();
        }
        return $members;
    }

    /**
     * Remove specified members
     *
     * @param interger|array $ids Members identifiers to delete
     *
     * @return boolean
     */
    public function removeMembers($ids)
    {
        global $log, $mdb;

        $list = array();
        if ( is_numeric($ids) ) {
            //we've got only one identifier
            $list[] = $ids;
        } else {
            $list = $ids;
        }

        if ( is_array($list) ) {
            $qry_list = 'SELECT ' . Adherent::PK . ', nom_adh, prenom_adh FROM ' .
            PREFIX_DB . Adherent::TABLE . ' WHERE ' . Adherent::PK . '=';
            $qry_list .= implode(' or ' . Adherent::PK . '=', $list);

            $result_list = $mdb->query($qry_list);
            if (MDB2::isError($result_list)) {
                $log->log(
                    'Cannot list members to delete | ' .
                    $result_list->getMessage() . '(' .
                    $result_list->getDebugInfo() . ')', PEAR_LOG_WARNING
                );
                return false;
            }

            $qry_del = 'DELETE FROM ' . PREFIX_DB . Adherent::TABLE . ' WHERE ' .
            Adherent::PK . '= ?';
            $stmt = $mdb->prepare($qry_del, array('integer'), MDB2_PREPARE_MANIP);

            if ( MDB2::isError($stmt) ) {
                $log->log(
                    'Unable to delete selected member(s) |' .
                    $stmt->getMessage() . '(' . $stmt->getDebugInfo() . ')',
                    PEAR_LOG_ERR
                );
                return false;
            }

            foreach ( $result_list->fetchAll() as $adh ) {
                //remove adh
                //$del = $stmt->execute($adh->id_adh);
                $str_adh = $adh->id_adh . '(' . $adh->nom_adh . ' ' .
                    $adh->prenom_adh . ')';
                if ( MDB2::isError($del) ) {
                    $log->log(
                        'Unable to delete member ' . $str_adh . ' |' .
                        $DEL->getMessage() . '(' . $DEL->getDebugInfo() . ')',
                        PEAR_LOG_ERR
                    );
                } else {
                    /** TODO: remove contributions */
                    $p = new Picture($m->id_adh);
                    if ( !$p->delete() ) {
                        $log->log(
                            'Unable to delete picture for member ' . $str_adh,
                            PEAR_LOG_ERR
                        );
                    } else {
                        $hist->add(
                            "Member Picture deleted",
                            $str_adh
                        );
                    }
                    $hist->add(
                        "Delete the member card (and dues)",
                        $str_adh,
                        str_replace('?', $adh->id_adh, $qry_del)
                    );
                }
            }
            $stmt->free();
        } else {
            //not numeric and not an array: incorrect.
            $log->log(
                'Asking to remove members, but without providing an array or a single numeric value.',
                PEAR_LOG_WARNING
            );
            return false;
        }

    }

    /**
    * Get members list
    *
    * @param bool    $as_members return the results as an array of
    *                               Member object.
    * @param array   $fields     field(s) name(s) to get. Should be a string or
    *                               an array. If null, all fields will be
    *                               returned
    * @param boolean $filter     proceed filter, defaults to true
    *
    * @return Adherent[]|ResultSet
    * @static
    */
    public static function getList($as_members=false, $fields=null, $filter=true)
    {
        return self::getMembersList($as_members, $fields, $filter, false, false);
    }

    /**
    * Get members list with public informations available
    *
    * @param boolean $with_photos get only members which have uploaded a
    *                               photo (for trombinoscope)
    * @param array   $fields      fields list
    *
    * @return Adherent[]
    * @static
    */
    public static function getPublicList($with_photos, $fields)
    {
        global $mdb, $log;

        $where = ' WHERE bool_display_info=1 AND (date_echeance > \''.
            date("Y-m-d") . '\' OR bool_exempt_adh=1)';

        $query = self::_buildSelect(
            self::SHOW_PUBLIC_LIST, $fields, false, $with_photos
        );
        $query .= $where;

        $result = $mdb->query($query);

        if (MDB2::isError($result)) {
            $log->log(
                'Cannot list members with public informations (photos: '
                . $with_photos . ') | ' . $result->getMessage() . '('
                . $result->getDebugInfo() . ')', PEAR_LOG_WARNING
            );
            return false;
        }

        foreach ( $result->fetchAll() as $row ) {
            $members[] = new Adherent($row);
        }
        return $members;
    }

    /**
    * Get list of members that has been selected
    *
    * @param array  $ids     an array of members id that has been selected
    * @param string $orderby SQL order clause (optionnal)
    *
    * @return Adherent[]
    * @static
    */
    public static function getArrayList($ids, $orderby = null)
    {
        global $mdb, $log;

        if ( !is_array($ids) || count($ids) < 1 ) {
            $log->log('No member selected for labels.', PEAR_LOG_INFO);
            return false;
        }

        $query = self::_buildSelect(self::SHOW_ARRAY_LIST, null, false, false);
        $query .= ' WHERE ' . self::PK . '=';
        $query .= implode(' OR ' . self::PK . '=', $ids);

        if ( $orderby != null && trim($orderby) != '' ) {
            $query .= ' ORDER BY ' . $orderby;
        }

        $result = $mdb->query($query);

        if (MDB2::isError($result)) {
            $log->log(
                'Cannot load members form ids array | '
                . $result->getMessage() . '(' . $result->getDebugInfo() . ')',
                PEAR_LOG_WARNING
            );
            return false;
        }

        $members = array();
        foreach ( $result->fetchAll() as $row ) {
            $members[] = new Adherent($row);
        }
        return $members;
    }

    /**
    * Builds the SELECT statement
    *
    * @param int   $mode   the current mode (see self::SHOW_*)
    * @param array $fields fields list to retrieve
    * @param bool  $filter true if filter is on, false otherwise
    * @param bool  $photos true if we want to get only members with photos
    *                       Default to false, only relevant for SHOW_PUBLIC_LIST
    * @param bool  $count  true if we want to count members
                            (not applicable from static calls), defaults to false
    *
    * @return string SELECT statement
    */
    private function _buildSelect($mode, $fields, $filter, $photos, $count = false)
    {
        global $varslist;

        $fieldsList = ( $fields != null && !$as_members )
                        ? (( !is_array($fields) || count($fields) < 1 ) ? '*'
                        : implode(', ', $fields)) : '*';

        $query = 'SELECT ' . $fieldsList . ' FROM ' . PREFIX_DB . self::TABLE;
        $querycount = 'SELECT count(' . self::PK . ') FROM ' .
            PREFIX_DB . self::TABLE;
        $join = '';

        switch($mode) {
        case self::SHOW_STAFF:
        case self::SHOW_LIST:
            $join = ' a JOIN ' . PREFIX_DB . Status::TABLE .
                ' p ON a.' . Status::PK . '=p.' . Status::PK;
            $query .= $join;
            break;
        case self::SHOW_PUBLIC_LIST:
            if ( $photos ) {
                $join .= ' a JOIN ' . PREFIX_DB . Picture::TABLE .
                    ' p ON a.' . self::PK . '=p.' . self::PK;
                $query .= $join;
            }
            break;
        }

        $where = '';
        if ( $mode == self::SHOW_LIST ) {
            if ( $filter ) {
                $where = self::_buildWhereClause();
                $query .= $where;
            }
            $query .= self::_buildOrderClause();
        }

        if ( $count ) {
            $this->_proceedCount($join, $where);
        }

        return $query;
    }

    /**
    * Count members from the query
    *
    * @param string $join  join clause
    * @param string $where where clause
    *
    * @return void
    */
    private function _proceedCount($join, $where)
    {
        global $mdb, $log;

        $query = 'SELECT count(' . self::PK . ') FROM ' .
            PREFIX_DB . self::TABLE;
        $query .= $join;
        $query .= $where;

        $result = $mdb->query($query);

        if (MDB2::isError($result)) {
            $log->log(
                'Cannot count members | ' . $result->getMessage() .
                '(' . $result->getDebugInfo() . ')',
                PEAR_LOG_WARNING
            );
            return false;
        }

        $this->_count = $result->fetchOne();
    }

    /**
    * Builds the order clause
    *
    * @return string SQL ORDER clause
    */
    private function _buildOrderClause()
    {
        global $varslist;
        $order = ' ORDER BY ';
        switch($varslist->orderby) {
        case self::ORDERBY_NICKNAME:
            $order .= 'pseudo_adh ' . $varslist->getDirection();
            break;
        case self::ORDERBY_STATUS:
            $order .= 'priorite_statut ' . $varslist->getDirection();
            break;
        case self::ORDERBY_ID:
            $order .= 'id_adh ' . $varslist->getDirection();
            break;
        case self::ORDERBY_FEE_STATUS:
            $order .= ' date_crea_adh ' . $varslist->getDirection() .
                ', bool_exempt_adh ' . $varslist->getDirection() .
                ', date_echeance ' . $varslist->getDirection();
            break;
        }
        if ( $order != ' ORDER BY ' ) {
            $order .= ', ';
        }
        //anyways, we want to order by firstname, lastname
        $order .= 'nom_adh ' . $varslist->getDirection() .
            ', prenom_adh ' . $varslist->getDirection();
        return $order;
    }

    /**
    * Builds where clause, for filtering on simple list mode
    *
    * @return string SQL WHERE clause
    */
    private function _buildWhereClause()
    {
        global $varslist, $mdb;
        $where = '';
        if ( $varslist->filter_str != '' ) {
            $where = ' WHERE ';
            $token = ' like \'%' . $varslist->filter_str . '%\'';
            $mdb->getDb()->loadModule('Function');
            switch( $varslist->field_filter ) {
            case self::FILTER_NAME:
                $where .= $mdb->getDb()->concat(
                    'nom_adh', 'prenom_adh', 'pseudo_adh'
                ) . $token;
                $where .= ' OR ';
                $where .= $mdb->getDb()->concat(
                    'prenom_adh', 'nom_adh', 'pseudo_adh'
                ) . $token;
                break;
            case self::FILTER_ADRESS:
                $where .= 'adresse_adh' .$token;
                $where .= ' OR adresse2_adh' .$token;
                $where .= ' OR cp_adh' .$token;
                $where .= ' OR ville_adh' .$token;
                $where .= ' OR pays_adh' .$token;
                break;
            case self::FILTER_MAIL:
                $where .= 'email_adh' . $token;
                $where .= ' OR url_adh' . $token;
                $where .= ' OR msn_adh' . $token;
                $where .= ' OR icq_adh' . $token;
                $where .= ' OR jabber_adh' . $token;
                break;
            case self::FILTER_JOB:
                $where .= 'prof_adh' .$token;
                break;
            case self::FILTER_INFOS:
                $where .= 'info_public_adh' . $token;
                $where .= ' OR info_adh' .$token;
                break;
            }
        }

        if ( $varslist->membership_filter ) {
            $where .= ($where == '') ? ' WHERE ' : ' AND ';
            switch($varslist->membership_filter) {
            case 1:
                $where .= 'date_echeance > \'' . date('Y-m-d', time()) .
                    '\' AND date_echeance < \'' .
                    date('Y-m-d', time() + (30 *24 * 60 * 60)) . '\'';
                    //(30 *24 * 60 * 60) => 30 days
                break;
            case 2:
                $where .= 'date_echeance < \'' . date('Y-m-d', time()) . '\'';
                break;
            case 3:
                $where .= '(date_echeance > \'' . date('Y-m-d', time()) .
                    '\' OR bool_exempt_adh=1)';
                break;
            case 4:
                $where .= 'isnull(date_echeance)';
                break;
            }
        }

        if ( $varslist->account_status_filter ) {
            $where .= ($where == '') ? ' WHERE ' : ' AND ';
            switch($varslist->account_status_filter) {
            case 1:
                $where .= 'activite_adh=1';
                break;
            case 2:
                $where .= 'activite_adh=0';
                break;
            }
        }

        $varslist->setLimit();
        return $where;
    }

    /**
    * Get count for current query
    *
    * @return int
    */
    public function getCount()
    {
        return $this->_count;
    }
}
?>