<?php

namespace A8Client\Services;

use A8Client\Services\RequestService;

abstract class ModelServiceFactory
{

    private $resource;

    private $select;

    private $with;

    public function __construct ( String $resource, RequestService $requestService )
    {

        $this->resource = $resource;
        $this->requestService = $requestService;

    }
    
    public function all()
    {

        return $this->allService();

    }

    public function get ( $id, $expand ) 
    {

        return $this->getService ( $id, $expand );

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