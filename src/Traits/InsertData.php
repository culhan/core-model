<?php
namespace CoreModel\Traits;

use CoreModel\Helper\ModelsHelper;

trait InsertData
{
    /**
     * [$fillable_column description]
     * @var array
     */
    protected $fillable_column = [];

    /**
     * [$createdAtColumn description]
     * @var [type]
     */
    protected $createdAtColumn;

    /**
     * [$createdByColumn description]
     * @var [type]
     */
    protected $createdByColumn;

	/**
     * @param  array data input
     * @return [array] last created data
     */
    public function insertData( array $data = null, array $custom_query = null )
    {
        // transaction start
        $this->getConnection()->beginTransaction();

        $data += $this->checkFillableCreateColumn($data);

        $data += $this->timeStamptCreateColumn();
        $data += $this->userStamptCreateColumn();

        $this->setToModelAttributes($data);

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
     * [timestamptCreateColumn description]
     * @return [type] [description]
     */
    public function timeStamptCreateColumn()
    {
        $data = [];

        if( $this->use_timestampt )
        {
            $this->createdAtColumn = (!empty($this->createdAtColumn)) ? $this->createdAtColumn : 'created_by';

            $data[$this->createdAtColumn] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * [userStamptCreateColumn description]
     * @return [type] [description]
     */
    public function userStamptCreateColumn()
    {
        $data = [];

        if( $this->use_timestampt )
        {
            $this->createdByColumn = (!empty($this->createdByColumn)) ? $this->createdByColumn : 'created_at';

            // get session
            $data[$this->createdByColumn] = isset($data[$this->createdByColumn]) ? $data[$this->createdByColumn] : $this->id_user ;
        }

        return $data;
    }

    /**
     * [checkFillableCreateColumn description]
     * @param  string $data [description]
     * @return [type]       [description]
     */
    public function checkFillableCreateColumn( $data = '' )
    {
        $dataResult = [];
        if( !empty($this->fillable_column) )
        {
            foreach ($this->fillable_column as $fillable_column_key => $fillable_column_value) {
                if( isset($data[$fillable_column_value]) )
                {
                    $dataResult[$fillable_column_value] = $data[$fillable_column_value];                     
                }
            }
        }
        else
        {
            $dataResult = $data;
        }

        return $dataResult;
    }
}