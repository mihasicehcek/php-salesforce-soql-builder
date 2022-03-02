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
    private $groupedConditionalStart = [];
    private $groupedConditionalEnd = [];

    public function select(array $fields): self
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function addSelect(string $field): self
    {
        $this->fields[] = $field;
        return $this;
    }

    public function from(string $object): self
    {
        $this->object = $object;
        return $this;
    }

    public function startWhere(): self
    {
        if(empty($this->where)) {
            $this->groupedConditionalStart[] = 0;
        } else {
            $this->groupedConditionalStart[] = array_key_last($this->where) + 1;
        }
        return $this;
    }

    public function endWhere(): self
    {
        $this->groupedConditionalEnd[] = array_key_last($this->where);
        return $this;
    }

    public function where($column, string $operator, $value, $boolean = 'AND'): self
    {
        $this->where[] = [$column, $operator, $this->prepareWhereValue($value), $boolean];
        return $this;
    }

    public function whereDate($column, string $operator, $value, $boolean = 'AND'): self
    {
        $this->where[] = [$column, $operator, $this->prepareWhereValue($value, "date"), $boolean];
        return $this;
    }

    public function orWhereDate($column, string $operator, $value): self
    {
        return $this->whereDate($column, $operator, $value, 'OR');
    }

    public function orWhere($column, string $operator, $value): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereColumn(array $conditions, $boolean = 'AND'): self
    {
        foreach ($conditions as $condition) {
            $this->where($condition[0], $condition[1], $condition[2], $boolean);
        }
        return $this;
    }

    public function whereIn($column, array $restrictions, $boolean = 'AND', $not = false): self
    {
        foreach ($restrictions as &$restriction) {
            $restriction = $this->prepareWhereValue($restriction);
        }

        $operator = !$not ? "IN" : "NOT IN";

        $this->where[] = [$column, $operator, '(' . implode(', ', $restrictions) . ')', $boolean];
        return $this;
    }

    public function whereNotIn($column, array $restrictions): self
    {
        $this->whereIn($column, $restrictions, "AND", true);
        return $this;
    }

    public function orWhereIn($column, array $restrictions): self
    {
        $this->whereIn($column, $restrictions, 'OR');
        return $this;
    }

    public function orWhereNotIn($column, array $restrictions): self
    {
        $this->whereIn($column, $restrictions, 'OR', true);
        return $this;
    }

    public function whereFunction($column, string $function, $value, $boolean = 'AND')
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->prepareWhereValue($item);
            }
            $value = implode(', ', $value);
        } else {
            $value = $this->prepareWhereValue($value);
        }

        $this->where[] = [$column, null, $function . '(' . $value . ')', $boolean];
        return $this;
    }

    private function getGroupExpressionsAtIndex(array $expressionLocations, int $index) : int
    {
        if(empty($expressionLocations)) {
            return 0;
        }
        return count(array_filter($expressionLocations, function($expressionLocation) use ($index) {
            return $expressionLocation === $index;
        }));
    }

    private function prepareWhereValue($value, $forceType = null)
    {
        if ($forceType === "date") {
            return $value;
        }

        if (is_string($value)) {
            $value = "'" . $value . "'";
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = "null";
        }

        return $value;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = $column . ' ' . $direction;

        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    public function toSoql(): string
    {
        if (!$this->object) {
            throw new InvalidQueryException('Query must contains sObject name');
        }
        if (!$this->fields) {
            throw new InvalidQueryException('Query must contains fields for select');
        }
        if(count($this->groupedConditionalStart) !== count($this->groupedConditionalEnd)) {
            throw new InvalidQueryException('Unmatched parenthesis for grouped expressions. Make sure to call startWhere() and endWhere().');
        }

        $soql = 'SELECT ';
        $soql .= implode(', ', array_unique($this->fields));
        $soql .= ' FROM ' . $this->object;

        if (count($this->where) > 0) {
            $soql .= ' WHERE ';
        }

        foreach ($this->where as $i => $iValue) {
            $iValue[0] = str_repeat('(', $this->getGroupExpressionsAtIndex($this->groupedConditionalStart, $i)) . $iValue[0];
            $iValue[2] .= str_repeat(')', $this->getGroupExpressionsAtIndex($this->groupedConditionalEnd, $i));
            if ($i !== 0) {
                $soql .= ' ' . $iValue[3] . ' ';
            }
            $soql .= implode(' ', array_filter([$iValue[0], $iValue[1], $iValue[2]], function ($item) {
                    return $item !== null;
                })
            );
        }

        if (count($this->orders) > 0) {
            $soql .= ' ORDER BY ';
            $soql .= implode(', ', $this->orders);
        }

        if ($this->limit) {
            $soql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset) {
            $soql .= ' OFFSET ' . $this->offset;
        }

        return $soql;
    }
}
