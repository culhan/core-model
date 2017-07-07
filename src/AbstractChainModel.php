<?php

namespace CoreModel;

use Shoop\AbstractModel;
use Helper\ModelsHelper;

class AbstractChainModel extends AbstractModel
{
    /**
     * [$chain_query description]
     * @var [type]
     */
    protected $chain_query;

	/**
	 * [where description]
	 * @param  [type] $column   [description]
	 * @param  [type] $operator [description]
	 * @param  [type] $value    [description]
	 * @return [type]           [description]
	 */
	public function and_where( $column, $operator, $value)
	{
		// check chain query 
        $this->check_chain_query();

		$this->chain_query->andWhere( $column.' '.$operator.' '.$value );

		return $this;
	}

	/**
	 * [or_where description]
	 * @param  [type] $column   [description]
	 * @param  [type] $operator [description]
	 * @param  [type] $value    [description]
	 * @return [type]           [description]
	 */
	public function or_where( $column, $operator, $value)
	{
		// check chain query 
        $this->check_chain_query();

		$this->chain_query->orWhere( $column.' '.$operator.' '.$value );

		return $this;
	}

	/**
	 * [order description]
	 * @param  [type] $column [column table]
	 * @param  [type] $type   [desc or asc]
	 * @return [type]         [description]
	 */
    public function order( $column, $type = 'ASC' )
    {
    	// check chain query 
        $this->check_chain_query();

    	$this->chain_query->addOrderBy( $this->table.'.'.$column, $type );

        return $this;
    }

	/**
     * [get description]
     * @param  array|null $custom_query [description]
     * @return [type]                   [description]
     */
    public function get( array $custom_query = null )
    {
        // check chain query 
        $this->check_chain_query();

        // check timestamp deleted
        $this->check_chain_deleted();
        
        //check custom query variable
        $this->check_custom_query($custom_query);

        // check select default '*'
        $this->check_chain_select();

        $this->chain_query->from( $this->table, $this->table );

        $param = $this->chain_query->getParameters();
        $result = $this->chain_query->execute($param)->fetchAll();

        return $this->setToModelAttributes($result);
    }

	/**
     * [first description]
     * @param  array|null $custom_query [description]
     * @return [type]                   [description]
     */
    public function first( array $custom_query = null )
    {
        // check chain query 
        $this->check_chain_query();

        // check timestamp deleted
        $this->check_chain_deleted();
        
        //check custom query variable
        $this->check_custom_query($custom_query);

        // check select default '*'
        $this->check_chain_select();

        $this->chain_query->from( $this->table, $this->table );

        $param = $this->chain_query->getParameters();
        $result = $this->chain_query->execute($param)->fetch();
        
        $this->setToModelAttributes($result);

        return $this;
    }

    /**
     * @param  array data input
     * @return [array] last created data
     */
    public function insertData( array $data = null, array $custom_query = null )
    {
        // transaction start
        $this->getConnection()->beginTransaction();

        if( $this->use_timestampt )
        {
            // get session
            $data['created_by'] = isset($data['created_by']) ? $data['created_by'] : $this->id_user ;
            $data['created'] = date('Y-m-d H:i:s');
        }

        $valuesColumn = [];
        $valuesData = [];
        foreach ($data as $dataKey => $dataValue) {
            $valuesColumn[$dataKey] = ':'.$dataKey;
            $valuesData[$dataKey] = $dataValue;
        }

        /**over ride if there is setAttributes... method**/
        $all_funct = get_class_methods($this);
        foreach ($all_funct as $all_funct_value) {
            if( substr($all_funct_value, 0, strlen('setAttributes')) === 'setAttributes' )
            {
                $cols_by_funct = substr( $all_funct_value, strlen('setAttributes'));
                $cols_by_funct = ModelsHelper::from_camel_case($cols_by_funct);
                $valuesColumn[$cols_by_funct] = ':'.$cols_by_funct;
                $valuesData[$cols_by_funct] = $this->{$all_funct_value}();
            }
        }
        
        $inserted = $this->getQueryBuilder()
                ->insert( $this->table )
                ->values($valuesColumn)
                ->setParameters($valuesData)
                ->execute();
        
        $lastInsertId = $this->connection->lastInsertId();

        $return = $this->and_where( $this->table.'.id', '=', $lastInsertId )->first( $custom_query );

        // call saved function
        if( method_exists( $this, 'saved' ) )
        {
            $this->saved();
        }

        // transaction commit
        $this->getConnection()->commit();

        return $return;
    }

    /**
     * [setToModelAttributes description]
     * @param [type] $data [description]
     */
    public function setToModelAttributes($data)
    {
        if($data)
        {
            if( is_array($data[0]) )
            {
                $return = new \stdClass();
                foreach ($data as $result_key => $result_value) {
                    $class = get_class($this);
                    $class = new $class; 
                    foreach ($result_value as $result_value_key => $result_value_value) {                                            
                        $class->{$result_value_key} = $result_value_value;                        
                    }
                    $return->{$result_key} = $class;
                }

                return $return;
            }
            else
            {
                foreach ($data as $result_key => $result_value) {
                    $this->{$result_key} = $result_value;
                }
            }
        }
    }

    /**
     * [check_chain_query description]
     * @return [type] [description]
     */
    public function check_chain_query()
    {
    	if( !isset($this->chain_query) )
        {
            $this->chain_query = $this->getQueryBuilder();
        }
    }

    /**
     * [check_deleted description]
     * @return [type] [description]
     */
    public function check_chain_deleted()
    {
    	if( $this->use_timestampt )
        {
            $this->and_where( $this->table.'.deleted_by', 'IS', 'NULL' );
        }
    }

    /**
     * [check_chain_select description]
     * @return [type] [description]
     */
    public function check_chain_select()
    {
    	if( empty($this->chain_query->getQueryParts()['select']) )
    	{
    		$this->chain_query->select("*");
    	}
    }

    /**
     * [check_custom_query description]
     * @param  [type] $custom_query [description]
     * @return [type]               [description]
     */
    public function check_custom_query($custom_query)
    {
        if( !empty($custom_query) )
        {
            $this->chain_query = $this->generateQueryByArray( $custom_query, $this->chain_query );
        }
    }
}
