<?php

namespace SalesforceQueryBuilder;


use SalesforceQueryBuilder\Exceptions\InvalidQueryException;

class QueryBuilder
{

    private $fields = [];
    private $object;
    private $where = [];
    private $limit;
    private $offset;
    private $orders = [];

    public function select(array $fields) : self
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function addSelect(string $field) : self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function from(string $object) : self
    {
        $this->object = $object;
        return $this;
    }

    public function where($column, string $operator, $value, $boolean = 'AND') : self
    {
        if ($column instanceof \Closure) {
            return $this->whereNested($column, $boolean);
        }

        $this->where[] = [$column, $operator, $this->prepareWhereValue($value), $boolean];
        return $this;
    }

    public function orWhere($column, string $operator, $value) : self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereColumn(array $conditions, $boolean = 'AND') : self
    {
        foreach ($conditions as $condition){
            $this->where($condition[0], $condition[1], $condition[2], $boolean);
        }
        return $this;
    }

    public function whereIn($column, array $restrictions, $boolean = 'AND', $not = false) : self
    {
        foreach($restrictions as &$restriction){
            $restriction = $this->prepareWhereValue($restriction);
        }

        $operator = !$not ? "IN" : "NOT IN";

        $this->where[] = [$column, $operator, '('.implode(', ', $restrictions).')', $boolean];
        return $this;
    }

    public function whereNotIn($column, array $restrictions) : self
    {
        $this->whereIn($column, $restrictions, "AND", true);
        return $this;
    }

    public function orWhereIn($column, array $restrictions) : self
    {
        $this->whereIn($column, $restrictions, 'OR');
        return $this;
    }

    public function orWhereNotIn($column, array $restrictions) : self
    {
        $this->whereIn($column, $restrictions, 'OR', true);
        return $this;
    }

    private function prepareWhereValue($value)
    {
        if(gettype($value) === "string"){
            $value = "'".$value."'";
        }

        return $value;
    }

    public function limit(int $limit) : self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset) : self
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC') : self
    {
        $this->orders[] = $column.' '.$direction;

        return $this;
    }

    public function orderByDesc(string $column) : self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function toSoql() : string
    {
        if(!$this->object) throw new InvalidQueryException('Query must contains sObject name');
        if(!$this->fields) throw new InvalidQueryException('Query must contains fields for select');

        $soql = 'SELECT ';
        $soql .= implode(', ', $this->fields);
        $soql .= ' FROM '.$this->object;

        if(count($this->where) > 0){
            $soql .= ' WHERE';
            for($i = 0; $i < count($this->where); $i++){
                if($i != 0) {
                    $soql .= ' '.$this->where[$i][3];
                }
                $soql .= ' '.$this->where[$i][0].' '.$this->where[$i][1].' '.$this->where[$i][2];
            }
        }

        if(count($this->orders) > 0){
            $soql .= ' ORDER BY ';
            $soql .= implode(', ', $this->orders);
        }

        if($this->limit){
            $soql .= ' LIMIT '.$this->limit;
        }

        if($this->offset){
            $soql .= ' OFFSET '.$this->offset;
        }

        return $soql;
    }

}
