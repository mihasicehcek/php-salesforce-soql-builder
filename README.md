# Salesforce SOQL Builder
A SOQL builder for building queries for Salesforce.

## Installation
`composer require mihasicehcek/php_salesforce_soql_builder`

## Features
* Select
* Conditionals (where)
* Conditionals for date values
* Grouped conditional statements
* Where in
* Where not in
* Limit
* Offset
* Order By

## Example usage

```php
$builder
    ->select(['Id', 'Name', 'created_at'])
    ->from('Account')
    ->where('Name', '=', 'Test')
    ->limit(20)
    ->orderBy('created_at', 'DESC')
    ->toSoql();
```
`> SELECT Id, Name, created_at FROM Account WHERE Name = 'Test' ORDER BY created_at DESC LIMIT 20` 
```php
$builder
    ->select(['Id', 'Name'])
    ->from('Account')
    ->where('Name', '=', 'Test')
    ->orWhere('Name', '=', 'Testing')
    ->toSoql();
```
`> SELECT Id, Name FROM Account WHERE Name = 'Test' OR Name = 'Testing'`
```php
$builder
    ->select(['Id', 'Name'])
    ->from('Account')
    ->startWhere()
    ->where('Name', '=', 'Test')
    ->where('Testing__c', '=', true)
    ->endWhere()
    ->orWhere('Email__c', '=', 'test@test.com')
    ->toSoql(); 
```
`> SELECT Id, Name FROM Account WHERE (Name = 'Test' AND Testing__c = true) OR Email__c = 'test@test.com'`

## Testing
This library ships with phpunit.

`phpunit` or `vendor/bin/phpunit`