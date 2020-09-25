<?php

namespace Tests\QueryBuilderTest;

use PHPUnit\Framework\TestCase;
use SalesforceQueryBuilder\Exceptions\InvalidQueryException;
use SalesforceQueryBuilder\QueryBuilder;

class QueryBuilderTest extends TestCase
{

    public function testBaseQuery(){
        $qb = (new QueryBuilder())
            ->from('Account')
            ->select(['Id', 'Name', 'Description'])
            ->where('Name', '=', 'Mikhail')
            ->orderBy('Name')
            ->limit(10)
            ->offset(15);

        $this->assertEquals("SELECT Id, Name, Description FROM Account WHERE Name = 'Mikhail' ORDER BY Name ASC LIMIT 10 OFFSET 15", $qb->toSoql());
    }

    public function testWithBoolen(){
        $qb = (new QueryBuilder())
            ->from('Account')
            ->select(['Id', 'Name', 'Description'])
            ->where('IsChecked', '=', true);

        $this->assertEquals("SELECT Id, Name, Description FROM Account WHERE IsChecked = true", $qb->toSoql());
    }

    public function testWithSeveralOrders()
    {
        $qb = (new QueryBuilder())
            ->from('Account')
            ->select(['Id', 'Name', 'Description'])
            ->orderBy('Name')
            ->orderByDesc('Description');

        $this->assertEquals('SELECT Id, Name, Description FROM Account ORDER BY Name ASC, Description DESC', $qb->toSoql());
    }

    public function testOrWhere()
    {
        $qb = (new QueryBuilder())
            ->from('Account')
            ->select(['Id', 'Name', 'Description'])
            ->orWhere('A', '=', 'B')
            ->orWhere('C', '=', 'D');

        $this->assertEquals('SELECT Id, Name, Description FROM Account WHERE A = \'B\' OR C = \'D\'', $qb->toSoql());
    }

    public function testWhereIn()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->whereIn('Name', ["A", "B", "C"]);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' AND Name IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testWhereNotIn()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->whereNotIn('Name', ["A", "B", "C"]);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' AND Name NOT IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testOrWhereIn()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->orWhereIn('Name', ["A", "B", "C"]);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' OR Name IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testOrWhereNotIn()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->where('G', '=', 'G')
            ->orWhereNotIn('Name', ["A", "B", "C"]);

        $this->assertEquals("SELECT Id, Name FROM Acc WHERE G = 'G' OR Name NOT IN ('A', 'B', 'C')", $qb->toSoql());
    }

    public function testQueryWithoutFields()
    {
        $this->expectException(InvalidQueryException::class);
        (new QueryBuilder())
            ->from('Account')
            ->orderBy('Name')
            ->orderByDesc('Description')
            ->toSoql();
    }

    public function testQueryWithoutSObject()
    {
        $this->expectException(InvalidQueryException::class);
        (new QueryBuilder())
            ->select(['Id', 'Name', 'Description'])
            ->orderBy('Name')
            ->orderByDesc('Description')
            ->toSoql();
    }

    public function testAddSelection()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name']);

        $qb->addSelect('Description');

        $this->assertEquals('SELECT Id, Name, Description FROM Acc', $qb->toSoql());
    }

    public function testWhereColumns()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->whereColumn([['A', '>', 3], ['B', '<', 8]]);

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A > 3 AND B < 8', $qb->toSoql());
    }

    public function testWhereWithNull()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->where("A", "=", null);

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A = null', $qb->toSoql());
    }

    public function testWhereWithDate()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->whereDate("A", "=", "2019-10-10");

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A = 2019-10-10', $qb->toSoql());
    }

    public function testOrWhereWithDate()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->select(['Id', 'Name'])
            ->whereDate("A", "=", "2019-10-10")
            ->orWhereDate("B", "=", "2019-10-09");

        $this->assertEquals('SELECT Id, Name FROM Acc WHERE A = 2019-10-10 OR B = 2019-10-09', $qb->toSoql());
    }

    public function testDuplicateSelect()
    {
        $qb = (new QueryBuilder())
            ->from('Acc')
            ->addSelect("Id");

        $qb->addSelect("Id");

        $this->assertEquals('SELECT Id FROM Acc', $qb->toSoql());
    }

    public function testWhereFunction()
    {
        $actual = (new QueryBuilder())
            ->from('Object')
            ->addSelect('Id')
            ->whereFunction('F', 'func1', 'chs1')
            ->whereFunction('F', 'func2', 'chs2')
            ->whereFunction('F', 'func3', 'chs3', 'OR')
            ->whereFunction('F', 'func4', 'chs4')
            ->toSoql();

        $this->assertEquals(
            "SELECT Id FROM Object WHERE F func1('chs1') AND F func2('chs2') OR F func3('chs3') AND F func4('chs4')",
            $actual
        );
    }

}
