<?php

namespace A8Client\libraries\Services;

use A8Client\libraries\Services\RequestService;

class ModelService
{

    protected $resource;
    protected $select;
    protected $with;
    protected $request_service;
    
    public function __construct ( $resource, $cred )
    {
        $this->resource = $resource;
        $this->select = '';
        $this->with = '';

        $this->request_service = new RequestService( $cred );
    }
    
    public function all()
    {

        return $this->request_service->all($this->resource, $this->select, $this->with);

    }

    public function get ( $id ) 
    {

        return $this->request_service->get($this->resource, $id, $this->select, $this->with);

    }

    public function find ( array $conditions, array $sort, int $limit, int $offset ) 
    {

        return $this->request_service->find($this->resource, $conditions, $sort, $limit, $offset, $this->select, $this->with);;
    
    }

    public function create ( array $data ) 
    {

        return '';

    }

    public function update ( $id, array $data ) 
    {

        return '';

    }

    public function delete ( $id ) 
    {

        return '';

    }

    public function select( String $str )
    {
        $this->select = $str;

        return $this;
    
    }

    public function with( String $arr )
    {

        $this->with = $arr;

        return $this;

    }

}