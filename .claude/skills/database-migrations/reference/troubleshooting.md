# Database Migrations Troubleshooting

## Common Issues and Solutions

> Note: This file was written for the MongoDB template. For this service, replace Mongo-specific commands (`doctrine:mongodb:*`) with Doctrine ORM equivalents (`doctrine:schema:validate`, migrations) and use the `database` service instead of `mongodb`.

### Schema Validation Issues

#### Issue: Schema Not Valid

**Symptoms**:

```
[ERROR] Schema is not valid
```

**Diagnosis**:

```bash
docker compose exec php bin/console doctrine:mongodb:schema:validate
```

**Common Causes**:

**1. XML Mapping Errors**:

```xml
<!-- ❌ Missing type attribute -->
<field name="email" fieldName="email"/>

<!-- ✅ Correct -->
<field name="email" fieldName="email" type="string"/>
```

**2. Namespace Mismatch**:

```xml
<!-- ❌ Wrong namespace -->
<document name="App\Customer\Domain\Entity\Customer">

<!-- ✅ Correct namespace -->
<document name="App\Core\Customer\Domain\Entity\Customer">
```

**3. Missing Field in Entity**:

```xml
<!-- XML has field that doesn't exist in entity -->
<field name="nonExistentField" type="string"/>
```

**Solution**: Ensure XML mapping matches entity class exactly.

#### Issue: Mapping File Not Found

**Symptoms**:

```
No mapping file found for class 'App\Core\Customer\Domain\Entity\Customer'
```

**Diagnosis**:

Check `config/packages/doctrine_mongodb.yaml`:

```yaml
doctrine_mongodb:
  document_managers:
    default:
      mappings:
        App:
          type: xml
          dir: '%kernel.project_dir%/config/doctrine'
          prefix: 'App\'
```

**Solutions**:

1. **Verify file location**:

   ```bash
   ls -la config/doctrine/Customer.mongodb.xml
   ```

2. **Check file naming**: Must be `{Entity}.mongodb.xml`

3. **Clear cache**:
   ```bash
   make cache-clear
   ```

### Connection Issues

#### Issue: Connection Refused

**Symptoms**:

```
MongoDB\Driver\Exception\ConnectionTimeoutException: No suitable servers found
```

**Diagnosis**:

```bash
# Check MongoDB container
docker compose ps mongodb

# Check MongoDB logs
docker compose logs mongodb

# Check connection string
echo $DB_URL
```

**Solutions**:

**1. Container Not Running**:

```bash
docker compose up -d mongodb
```

**2. Wrong Connection String**:

Check `.env`:

```
DB_URL=mongodb://mongodb:27017
```

**3. Port Conflict**:

```bash
# Check if port 27017 is in use
lsof -i :27017

# If conflict, change port in docker-compose.yml
```

**4. Network Issues**:

```bash
# Recreate containers
make down
make start
```

#### Issue: Authentication Failed

**Symptoms**:

```
Authentication failed
```

**Solutions**:

**1. Check Credentials**:

In `.env`:

```
DB_URL=mongodb://username:password@mongodb:27017/database?authSource=admin
```

**2. Reset MongoDB**:

```bash
# Remove MongoDB volume
docker compose down -v
docker compose up -d mongodb
```

### Index Issues

#### Issue: Duplicate Key Error

**Symptoms**:

```
E11000 duplicate key error collection: core_service.customers index: email_1 dup key: { email: "test@example.com" }
```

**Cause**: Unique index violated

**Solutions**:

**1. Find Duplicate**:

```javascript
// MongoDB shell
db.customers.aggregate([
  { $group: { _id: '$email', count: { $sum: 1 } } },
  { $match: { count: { $gt: 1 } } },
]);
```

**2. Remove Duplicates**:

```javascript
// Keep first, remove rest
db.customers
  .aggregate([
    { $group: { _id: '$email', docs: { $push: '$_id' } } },
    { $match: { 'docs.1': { $exists: true } } },
  ])
  .forEach(doc => {
    doc.docs.shift(); // Keep first
    db.customers.deleteMany({ _id: { $in: doc.docs } });
  });
```

**3. Update Application Code**:

Ensure unique validation before save:

```php
public function handle(CreateCustomerCommand $command): void
{
    if ($this->repository->existsByEmail($command->email)) {
        throw new CustomerAlreadyExistsException();
    }

    // Create customer...
}
```

#### Issue: Index Creation Failed

**Symptoms**:

```
Index creation failed: ...
```

**Solutions**:

**1. Drop Conflicting Index**:

```javascript
// MongoDB shell
db.customers.dropIndex('email_1');
```

**2. Recreate Index via Doctrine**:

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update --force
```

**3. Check Index Limits**:

MongoDB has index limits (64 indexes per collection max).

```javascript
// MongoDB shell
db.customers.getIndexes().length;
```

### Migration Issues

#### Issue: Migration Already Applied

**Symptoms**:

```
Migration VERSION already executed
```

**Solutions**:

**1. Check Migration Status**:

```bash
docker compose exec php bin/console doctrine:migrations:status
```

**2. Skip Migration**:

```bash
docker compose exec php bin/console doctrine:migrations:version --add VERSION
```

**3. Rollback and Retry**:

```bash
docker compose exec php bin/console doctrine:migrations:migrate prev
docker compose exec php bin/console doctrine:migrations:migrate
```

#### Issue: Migration Failed Midway

**Symptoms**:

```
Migration VERSION failed
```

**Solutions**:

**1. Check Migration Table**:

```javascript
// MongoDB shell
db.migration_versions.find();
```

**2. Manual Rollback**:

```bash
# Mark as not executed
docker compose exec php bin/console doctrine:migrations:version --delete VERSION

# Fix migration script
# Re-run migration
docker compose exec php bin/console doctrine:migrations:migrate
```

**3. Database Backup and Restore**:

```bash
# Restore from backup if available
mongorestore --host localhost:27017 --db core_service /path/to/backup
```

### Entity Hydration Issues

#### Issue: Class Not Found

**Symptoms**:

```
Class "App\Core\Customer\Domain\Entity\Customer" not found
```

**Solutions**:

**1. Clear Cache**:

```bash
make cache-clear
```

**2. Check Autoloader**:

```bash
composer dump-autoload
```

**3. Verify Namespace**:

Ensure entity namespace matches file location:

```
src/Core/Customer/Domain/Entity/Customer.php
→ App\Core\Customer\Domain\Entity\Customer
```

#### Issue: Property Not Accessible

**Symptoms**:

```
Property "name" in class "Customer" is not accessible
```

**Causes**:

**1. Missing Getter**:

```php
// ❌ No getter method
private string $name;

// ✅ Add getter
public function getName(): string
{
    return $this->name;
}
```

**2. Wrong Property Name in XML**:

```xml
<!-- ❌ Wrong property name -->
<field name="fullName" fieldName="name" type="string"/>

<!-- ✅ Match entity property -->
<field name="name" fieldName="name" type="string"/>
```

### Performance Issues

#### Issue: Slow Queries

**Symptoms**:

- API endpoints taking >1 second
- Database CPU high

**Diagnosis**:

Enable MongoDB profiling:

```javascript
// MongoDB shell
db.setProfilingLevel(2); // Profile all operations
db.system.profile.find().sort({ ts: -1 }).limit(10); // View slow queries
```

**Solutions**:

**1. Add Indexes**:

Find missing indexes from slow query log:

```javascript
db.system.profile.find({ millis: { $gt: 100 } }).forEach(doc => {
  printjson({
    query: doc.command,
    time: doc.millis + 'ms',
  });
});
```

Add indexes for frequently queried fields.

**2. Optimize Queries**:

```php
// ❌ BAD: Load all, filter in PHP
$customers = $this->repository->findAll();
foreach ($customers as $customer) {
    if ($customer->getStatus() === 'active') {
        // ...
    }
}

// ✅ GOOD: Filter in database
$customers = $this->repository->findBy(['status' => 'active']);
```

**3. Use Pagination**:

```php
// ❌ BAD: Load all documents
public function findAll(): array
{
    return $this->repository->findAll();
}

// ✅ GOOD: Paginate
public function findPaginated(int $page, int $limit): array
{
    return $this->repository->createQueryBuilder()
        ->limit($limit)
        ->skip(($page - 1) * $limit)
        ->getQuery()
        ->execute()
        ->toArray();
}
```

#### Issue: High Memory Usage

**Symptoms**:

```
Fatal error: Allowed memory size exhausted
```

**Solutions**:

**1. Use Batch Processing**:

```php
// ❌ BAD: Load all at once
$customers = $this->repository->findAll();
foreach ($customers as $customer) {
    // Process...
}

// ✅ GOOD: Batch process
$batchSize = 100;
$offset = 0;

while (true) {
    $customers = $this->repository->createQueryBuilder()
        ->limit($batchSize)
        ->skip($offset)
        ->getQuery()
        ->execute();

    if (count($customers) === 0) {
        break;
    }

    foreach ($customers as $customer) {
        // Process...
    }

    $this->documentManager->clear();  // Free memory
    $offset += $batchSize;
}
```

**2. Use Iterators**:

```php
foreach ($this->repository->createQueryBuilder()->getQuery()->getIterator() as $customer) {
    // Process one at a time
    $this->documentManager->detach($customer);  // Free memory
}
```

### Test Database Issues

#### Issue: Test Data Persists

**Symptoms**:

- Tests fail due to existing data
- Duplicate key errors in tests

**Solutions**:

**1. Reset Test Database**:

```bash
make setup-test-db
```

**2. Add setUp/tearDown in Tests**:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->clearDatabase();
}

protected function tearDown(): void
{
    $this->clearDatabase();
    parent::tearDown();
}

private function clearDatabase(): void
{
    $dm = $this->getContainer()->get(DocumentManager::class);

    // Drop collections
    $db = $dm->getClient()->selectDatabase('test_db');
    $db->drop();

    // Recreate schema
    $schemaManager = $dm->getSchemaManager();
    $schemaManager->createCollections();
    $schemaManager->ensureIndexes();
}
```

#### Issue: APP_ENV Not Set to 'test'

**Symptoms**:

- Tests modify production database
- Unable to connect to test database

**Solution**:

Ensure `APP_ENV=test` in test execution:

```bash
# In Makefile
unit-tests:
    docker compose exec -e APP_ENV=test php vendor/bin/phpunit --testsuite=Unit

integration-tests:
    docker compose exec -e APP_ENV=test php vendor/bin/phpunit --testsuite=Integration
```

Check `.env.test`:

```
APP_ENV=test
DB_URL=mongodb://mongodb:27017/test_db
```

### Docker Issues

#### Issue: Container Exits Immediately

**Symptoms**:

```
mongodb exited with code 1
```

**Diagnosis**:

```bash
docker compose logs mongodb
```

**Solutions**:

**1. Port Already in Use**:

```bash
# Find process using port
lsof -i :27017

# Kill process or change port in docker-compose.yml
```

**2. Volume Permission Issues**:

```bash
# Remove volumes
docker compose down -v

# Recreate
docker compose up -d mongodb
```

**3. Insufficient Resources**:

Check Docker resources (CPU, memory) and increase if needed.

## Debugging Tips

### Enable Query Logging

In `config/packages/doctrine_mongodb.yaml`:

```yaml
doctrine_mongodb:
  document_managers:
    default:
      logging: true # Enable query logging
```

### Use MongoDB Compass

GUI tool for MongoDB:

1. Download: https://www.mongodb.com/products/compass
2. Connect: `mongodb://localhost:27017`
3. View collections, documents, indexes
4. Run queries visually

### Check Doctrine Cache

```bash
# Clear metadata cache
docker compose exec php bin/console cache:pool:clear doctrine.odm.mongodb.metadata_cache
```

### Use Profiler in Development

In `.env`:

```
APP_ENV=dev
APP_DEBUG=true
```

Visit `https://localhost/api/customers` then check profiler toolbar for database queries.

### Manual Database Inspection

```bash
# Access MongoDB shell
docker compose exec mongodb mongosh

# Use database
use core_service

# List collections
show collections

# View documents
db.customers.find().pretty()

# Count documents
db.customers.countDocuments()

# Check indexes
db.customers.getIndexes()
```

## Preventive Measures

### 1. Always Use Unique Test Data

```php
// ✅ GOOD
$customer = new Customer(
    id: $this->faker->uuid(),
    email: $this->faker->unique()->email()  // unique()
);
```

### 2. Add Validation Before Persistence

```php
if ($this->repository->existsByEmail($email)) {
    throw new CustomerAlreadyExistsException();
}
```

### 3. Use Transactions for Multi-Document Operations

```php
$this->documentManager->transactional(function ($dm) use ($customer, $order) {
    $dm->persist($customer);
    $dm->persist($order);
});
```

### 4. Monitor Index Usage

```javascript
// MongoDB shell - find unused indexes
db.customers.aggregate([{ $indexStats: {} }]);
```

### 5. Regular Backups

```bash
# Backup production database
mongodump --host production-host --db core_service --out /backups/$(date +%Y%m%d)
```

## Getting Help

If issues persist:

1. Check Doctrine ODM documentation: https://www.doctrine-project.org/projects/mongodb-odm.html
2. Check MongoDB documentation: https://www.mongodb.com/docs/
3. Review [`AGENTS.md`](../../../../AGENTS.md) for project-specific patterns and workflows
4. Check project GitHub issues
5. Review API Platform MongoDB documentation
