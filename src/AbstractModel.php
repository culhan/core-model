<?php

namespace CoreModel;

use CoreModel\Helper\ModelsHelper;

class AbstractModel
{
    /**
     * db connection of this model
     * @var [type]
     */
    protected $db = '';

    /**
     * connection of this model
     * @var [type]
     */
    protected $connection;

    /**
     * table of this model
     * @var [type]
     */
    protected $table;

    /**
     * primary key of this table
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * [$use_timestampt description]
     * @var boolean
     */
    protected $use_timestampt = false;

    /**
     * [$jwt description]
     * @var [type]
     */
    protected $jwt;

    /**
     * [$id_user description]
     * @var [type]
     */
    protected $id_user;

    public function __construct($conn = null) {
        if($conn) {
            $this->connection = $conn;
            $this->db = $this->connection->createQueryBuilder();
        }

        $this->setJwt();
    }

    /**
     * [setJwt description]
     */
    public function setJwt()
    {
        global $container;
        
        if( isset($container['jwt']) )
        {
            $this->jwt = $container['jwt']; 

            $this->id_user = $this->jwt->id_user;
        }

        return $this;
    }

    /**
     * [getVar description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public function getVar($value)
    {
        return $this->{$value};
    }

    /**
     * [getVar description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public function setVar( $key, $value )
    {
        return $this->{$key} = $value;
    }

    /**
     * setter database
     */
    public function setDatabase()
    {
        if( empty($this->connection) )
        {
            global $container;
            
            if( isset($container['db']) )
            {
                $this->connection = $container['db'];
            }
        }
        $this->db = $this->connection->createQueryBuilder();
    }

    /**
     * getter of connection
     * @return connnection
     */
    public function getConnection()
    {
        if(!$this->connection) {
            $this->setDatabase();
        }
        return $this->connection;
    }

    /**
     * getter of db connection
     * @return db connnection
     */
    public function getQueryBuilder()
    {
        $this->setDatabase();

        return $this->db;
    }

    /**
     * setter table
     * @param [string] table name
     */
    public function setTable( $table )
    {
        $this->table = $table;
    }

    /**
     * getter table
     * @return [string] table name
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * [setData description]
     * @param array  $data [description]
     * @param [type] $qb   [description]
     */
    public function setData( array $data, $qb )
    {

        foreach ($data as $key => $value) {
            $qb = $qb->set( $key, ':'.$key )
                     ->setParameter( $key, $value );
        }

        return $qb;
    }

    /**
     * [whereData description]
     * @param  array  $data [description]
     * @param  [type] $qb   [description]
     * @return [type]       [description]
     */
    public function whereData( array $data, $qb )
    {
        foreach ($data as $key => $value) {

            $column = $key;

            if( isset($value['column']) )
            {
                $column = $value['column'];
            }

            if( $value['type'] == 'IS NULL' or $value['type'] == 'IS NOT NULL')
            {
                if( $value['operator'] == 'and' )
                {
                    $qb->andWhere( $column.' '.$value['type'] );
                }

                if( $value['operator'] == 'or' )
                {
                    $qb->orWhere( $column.' '.$value['type'] );
                }

                continue;
            }

            if( strtolower( $value['operator'] ) == 'and' )
            {
                $qb->andWhere( $column.' '.$value['type'].' :'.str_replace('.', '', $column) );
            }

            if( strtolower( $value['operator'] ) == 'or' )
            {
                $qb->orWhere( $column.' '.$value['type'].' :'.str_replace('.', '', $column) );
            }

            $qb->setParameter( str_replace('.', '', $column), $value['value'] );

        }

        return $qb;
    }

    /**
     * [orderData description]
     * @param  array  $data [description]
     * @param  [type] $qb   [description]
     * @return [type]       [description]
     */
    public function orderData( array $data, $qb )
    {
        foreach ($data as $key => $value) {

            $qb->addOrderBy( $this->table.'.'.$key, $value );

        }

        return $qb;
    }

    public function scopeData( array $data, $qb )
    {
        foreach ($data as $key => $value) {

            $qb = $this->$value($qb);

        }

        return $qb;
    }
    /**
     * [generateQueryByArray description]
     * @param  array  $custom_query [description]
     * @param  [type] $query        [description]
     * @return [type]               [description]
     */
    public function generateQueryByArray( array $custom_query, $query )
    {
        foreach ($custom_query as $key => $value) {

            // select
            if( $key == 'select' )
            {
                $query->select( implode(',', $value) );
            }

            // where
            if( $key == 'where' )
            {
                $query = $this->whereData( $value, $query );
            }

            // order
            if( $key == 'order' )
            {
                $query = $this->orderData( $value, $query );
            }

            // scope
            if( $key == 'scope' )
            {
                $query = $this->scopeData( $value, $query );
            }
        }

        // select
        if( !isset($custom_query['select']) )
        {
            $query->select( '*' );
        }

        return $query;
    }

}