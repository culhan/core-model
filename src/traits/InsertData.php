<?php
namespace CoreModel\Traits;

trait InsertData
{
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
}