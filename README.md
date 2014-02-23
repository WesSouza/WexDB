# WexDB

Simple database manipulation class for MySQL


## Getting Started

```php
$db = new WexDB( 'hostname', 'username', 'password', 'database' );
$db->tablePrefix = 'test_'; // Optional
```

## Querying

```php
$db->query( 'DELETE FROM {product} WHERE price >= ? AND status = ?', 3, 'unavailable' )
```
Runs `DELETE FROM test_product WHERE id >= 3 AND status = 'unavailable'`.

PDO escapes the binded arguments automatically.


## Auto INSERT or UPDATE

```php
$newUser = array(
    'name' => 'foo',
    'email' => 'foo@bar.com',
    'last_login' => time()
);
$db->insertUpdate( 'user', $newUser );

$userId = $db->lastId();

$updatedUser = array(
	'email' => 'baz@bar.com'
);
$db->insertUpdate( 'user', $updatedUser, 'id = ?', $userId );
```

## SELECTing

### With query
```php
$resultset = $db->query( 'SELECT * FROM {user} WHERE active = 1' );
while ( $user = $resultset->fetch() ) {
	// Do something with the user
}
```

### With fetchAll
```php
$fullArray = $db->fetchAll( 'SELECT * FROM {user} WHERE active = 1' );
foreach ( $fullArray as $user ) {
	// Do something with the user
}
```

### With fetchRow
```php
$userOne = $db->fetchRow( 'SELECT * FROM {user} WHERE id = ?', 1 );
// Do something with the user
```

### With fetchAssoc
```php
$settings = $db->fetchAssoc( 'SELECT name, value FROM {settings}' );
// Will fill as $settings[ name ] = value;
```

### With fetchCol
```php
$listOfIds = $db->fetchCol( 'SELECT id FROM {user} WHERE active = 1' );
foreach ( $listOfIds as $id ) {
	// Do something with the id
}
```

### With fetchOne
```php
$totalActiveUsers = $db->fetchOne( 'SELECT Count( 1 ) FROM {user} WHERE active = 1' );
```
