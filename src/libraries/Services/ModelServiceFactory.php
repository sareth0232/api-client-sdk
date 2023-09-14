<?php

namespace A8Client\libraries\Services;

use A8Client\libraries\Services\RequestService;

abstract class ModelServiceFactory
{

    private $resource;

    private $select;

    private $with;

    public function __construct ( String $resource )
    {

        $this->resource = $resource;
        // $this->requestService = $requestService;

    }
    
    public function all()
    {

        return $this->allService();

    }

    public function get ( $id, $expand ) 
    {

        return $this->getService ( $id, $expand );

    }

    public function find ( array $conditions, array $sort, int $limit, int $offset ) 
    {

        return $this->findService ( $conditions, $sort, $limit, $offset );

    }

    public function create ( array $data ) 
    {

        return $this->createService ( $data );

    }

    public function update ( $id, array $data ) 
    {

        return $this->updateService ( $id, $data );

    }

    public function delete ( $id ) 
    {

        return $this->deleteService ( $id );

    }

    public function allService ()
    {
        
        return [
            'id' => 1,
            "name" => 'Test',
            "description" => 'Test description',
            "company" => [
                'id' => 1
            ]
        ];

    }

    public function getService ( $id, $expand )
    {
        
        return [
            'id' => $id,
            "name" => 'Test',
            "description" => 'Test description',
            "company" => $expand
        ];

    }

    public function findService ( array $conditions, array $sort, int $limit, int $offset ) 
    {

        return [$conditions, $sort, $limit, $offset];

    }

    public function createService ( $data )
    {
        
        return 1;

    }

    public function updateService ( $id, $data )
    {
        
        return [
            'id' => $id,
            "name" => 'Test',
            "description" => 'Test description',
            "company" => $data
        ];

    }

    public function deleteService ( $id )
    {

        return "Ok";

    }

    public function select( array $str )
    {
        $this->select = $str;

        return $this;
    
    }

    public function with( array $arr )
    {

        $this->with = $arr;

        return $this;

    }

}
