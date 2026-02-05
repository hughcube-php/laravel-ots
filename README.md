<h1 align="center"> Laravel OTS </h1>

<p>
    <a href="https://github.com/hughcube-php/laravel-ots/actions?query=workflow%3ATest">
        <img src="https://github.com/hughcube-php/laravel-ots/workflows/Test/badge.svg" alt="Test Actions status">
    </a>
    <a href="https://styleci.io/repos/374948059">
        <img src="https://github.styleci.io/repos/374948059/shield?branch=master" alt="StyleCI">
    </a>
    <a href="https://scrutinizer-ci.com/g/hughcube-php/laravel-ots/?branch=master">
        <img src="https://scrutinizer-ci.com/g/hughcube-php/laravel-ots/badges/coverage.png?b=master" alt="Code Coverage">
    </a>
    <a href="https://scrutinizer-ci.com/g/hughcube-php/laravel-ots/?branch=master">
        <img src="https://scrutinizer-ci.com/g/hughcube-php/laravel-ots/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality">
    </a>
    <a href="https://scrutinizer-ci.com/g/hughcube-php/laravel-ots/?branch=master">
        <img src="https://scrutinizer-ci.com/g/hughcube-php/laravel-ots/badges/code-intelligence.svg?b=master" alt="Code Intelligence Status">
    </a>
    <a href="https://github.com/hughcube-php/laravel-ots">
        <img src="https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg" alt="PHP Versions Supported">
    </a>
    <a href="https://packagist.org/packages/hughcube/laravel-ots">
        <img src="https://poser.pugx.org/hughcube-php/laravel-ots/version" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/hughcube/laravel-ots">
        <img src="https://poser.pugx.org/hughcube-php/laravel-ots/downloads" alt="Total Downloads">
    </a>
    <a href="https://github.com/hughcube-php/laravel-ots/blob/master/LICENSE">
        <img src="https://img.shields.io/badge/license-MIT-428f7e.svg" alt="License">
    </a>
    <a href="https://packagist.org/packages/hughcube/laravel-ots">
        <img src="https://poser.pugx.org/hughcube-php/laravel-ots/v/unstable" alt="Latest Unstable Version">
    </a>
    <a href="https://packagist.org/packages/hughcube/laravel-ots">
        <img src="https://poser.pugx.org/hughcube-php/laravel-ots/composerlock" alt="composer.lock available">
    </a>
</p>

Laravel OTS 是一个功能完整的阿里云表格存储 (Tablestore/OTS) Laravel 适配器，提供了优雅的 API 来操作 OTS 服务。

## 功能特性

- 完整的数据 CRUD 操作（单行、批量、范围查询）
- 搜索索引支持（10+ 种查询类型）
- Schema 管理（表、二级索引、搜索索引）
- 本地事务支持
- 并行扫描 (ParallelScan)
- 数据流 (Stream) 处理
- Laravel 缓存驱动 + 分布式锁
- SQL 查询支持（同步/异步）
- Eloquent ORM 模型支持
- Laravel Sanctum 集成

## 安装

```shell
composer require hughcube/laravel-ots -vvv
```

## 配置

在 `config/database.php` 的 `connections` 数组中添加 OTS 连接配置：

```php
'ots' => [
    'driver' => 'ots',
    'endpoint' => env('OTS_ENDPOINT'),
    'instance' => env('OTS_INSTANCE'),
    'access_key_id' => env('OTS_ACCESS_KEY_ID'),
    'access_key_secret' => env('OTS_ACCESS_KEY_SECRET'),
],
```

在 `.env` 文件中配置：

```env
OTS_ENDPOINT=https://instance.cn-hangzhou.ots.aliyuncs.com
OTS_INSTANCE=instance
OTS_ACCESS_KEY_ID=your_access_key_id
OTS_ACCESS_KEY_SECRET=your_access_key_secret
```

## 使用指南

### 获取连接

```php
use HughCube\Laravel\OTS\Ots;

// 获取默认连接
$connection = Ots::connection();

// 获取指定连接
$connection = Ots::connection('ots');

// 直接获取 OTSClient 实例
$otsClient = $connection->getOts();
```

---

## 数据操作

### Query Builder

#### 按主键查询单行

```php
use HughCube\Laravel\OTS\Ots;

$row = Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->findByPrimaryKey();
```

#### 按主键查询多行

```php
$rows = Ots::connection()->table('users')
    ->findMany([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ]);
```

#### 插入数据

```php
// 方式1：通过 primaryKeyColumns 定义主键结构
Ots::connection()->table('users')
    ->primaryKeyColumns([
        'id' => PrimaryKeyTypeConst::CONST_INTEGER,
    ])
    ->insert([
        'id' => 1,
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

// 方式2：通过 primaryKey 指定主键值（其余字段自动作为属性列）
Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->insert([
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

// 多主键表插入
Ots::connection()->table('orders')
    ->primaryKeyColumns([
        'user_id' => PrimaryKeyTypeConst::CONST_INTEGER,
        'order_id' => PrimaryKeyTypeConst::CONST_INTEGER,
    ])
    ->insert([
        'user_id' => 100,
        'order_id' => 1,
        'amount' => 99.99,
        'status' => 'pending',
    ]);

// 插入并返回自增ID
$id = Ots::connection()->table('users')
    ->primaryKeyColumns([
        'partition' => PrimaryKeyTypeConst::CONST_STRING,
        'id' => PrimaryKeyTypeConst::CONST_PK_AUTO_INCR,  // 自增主键
    ])
    ->insertGetId([
        'partition' => 'user',
        'name' => 'John',
        'email' => 'john@example.com',
    ]);
```

#### 更新数据

```php
Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->update([
        'name' => 'John Doe',
        'updated_at' => time(),
    ]);
```

#### 删除数据

```php
Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->delete();
```

#### 条件写入（乐观并发控制）

```php
// 期望行存在才更新
Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->expectExist()
    ->update(['name' => 'New Name']);

// 期望行不存在才插入
Ots::connection()->table('users')
    ->expectNotExist()
    ->insert(['id' => 1, 'name' => 'John']);

// 忽略行存在性检查
Ots::connection()->table('users')
    ->rowExistence(RowExistenceExpectationConst::CONST_IGNORE)
    ->insert(['id' => 1, 'name' => 'John']);
```

#### 指定返回的列

```php
$row = Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->attributeColumns(['name', 'email'])
    ->findByPrimaryKey();
```

---

## 范围查询

#### 基础范围查询

```php
$rows = Ots::connection()->table('logs')
    ->startKey(['user_id' => 1, 'timestamp' => 0])
    ->endKey(['user_id' => 1, 'timestamp' => INF_MAX])
    ->limit(100)
    ->getRange();
```

#### 正向/反向查询

```php
// 正向查询（从小到大）
$rows = Ots::connection()->table('logs')
    ->forward()
    ->startKey(['id' => 1])
    ->endKey(['id' => 100])
    ->getRange();

// 反向查询（从大到小）
$rows = Ots::connection()->table('logs')
    ->backward()
    ->startKey(['id' => 100])
    ->endKey(['id' => 1])
    ->getRange();
```

---

## 搜索索引查询

### 使用搜索索引

```php
$query = Ots::connection()->table('products')
    ->useSearchIndex('products_index');
```

### 精确查询 (Term)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchTerm('status', 'active')
    ->searchQuery();
```

### IN 查询 (Terms)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchTerms('category', ['electronics', 'books', 'clothing'])
    ->searchQuery();
```

### 模糊匹配查询 (Match)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchMatch('description', '手机')
    ->searchQuery();
```

### 短语查询 (MatchPhrase)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchMatchPhrase('title', 'iPhone 15')
    ->searchQuery();
```

### 前缀查询 (Prefix)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchPrefix('name', 'App')
    ->searchQuery();
```

### 通配符查询 (Wildcard)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchWildcard('name', '*phone*')
    ->searchQuery();
```

### 范围查询 (Range)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchRange('price', 100, 500)  // 100 <= price <= 500
    ->searchQuery();

// 开区间
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchRange('price', 100, 500, false, false)  // 100 < price < 500
    ->searchQuery();
```

### 匹配所有 (MatchAll)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchMatchAll()
    ->searchQuery();
```

### 存在性查询 (Exists)

检查字段是否存在值。

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchExists('description')  // 只返回有 description 字段的记录
    ->searchQuery();
```

### 嵌套查询 (Nested)

用于查询嵌套类型的字段。

```php
// 使用回调构建嵌套查询
$rows = Ots::connection()->table('orders')
    ->useSearchIndex('orders_index')
    ->searchNested('items', function ($query) {
        $query->searchTerm('items.product_id', 'P001')
              ->searchRange('items.quantity', 1, 10);
    })
    ->searchQuery();

// 使用原生查询数组
$rows = Ots::connection()->table('orders')
    ->useSearchIndex('orders_index')
    ->searchNested('items', [
        'query_type' => QueryTypeConst::TERM_QUERY,
        'query' => [
            'field_name' => 'items.name',
            'term' => 'iPhone',
        ],
    ])
    ->searchQuery();
```

### 地理距离查询 (GeoDistance)

查找指定圆形范围内的地理位置。

```php
$rows = Ots::connection()->table('stores')
    ->useSearchIndex('stores_index')
    ->searchGeoDistance('location', 31.2304, 121.4737, 5000)  // 上海市中心 5000 米范围内
    ->searchQuery();
```

### 地理边界框查询 (GeoBoundingBox)

查找矩形范围内的地理位置。

```php
$rows = Ots::connection()->table('stores')
    ->useSearchIndex('stores_index')
    ->searchGeoBoundingBox(
        'location',
        31.3, 121.3,   // 左上角 (纬度, 经度)
        31.1, 121.6    // 右下角 (纬度, 经度)
    )
    ->searchQuery();
```

### 地理多边形查询 (GeoPolygon)

查找多边形范围内的地理位置。

```php
$rows = Ots::connection()->table('stores')
    ->useSearchIndex('stores_index')
    ->searchGeoPolygon('location', [
        [31.2, 121.4],  // 点1 (纬度, 经度)
        [31.3, 121.5],  // 点2
        [31.2, 121.6],  // 点3
        [31.1, 121.5],  // 点4
    ])
    ->searchQuery();
```

### 组合查询 (简单 AND)

多个条件默认使用 AND 逻辑组合。

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchTerm('status', 'active')
    ->searchRange('price', 100, 500)
    ->searchMatch('description', '手机')
    ->limit(20)
    ->searchQuery();
```

### 布尔查询 (BoolQuery)

支持完整的布尔逻辑：must (AND)、should (OR)、must_not (NOT)、filter (过滤)。

```php
// 复杂布尔查询示例：
// (status = 'active' OR status = 'pending') AND price > 100 AND NOT (category = 'test')
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchShould(function ($query) {
        $query->searchTerm('status', 'active');
    })
    ->searchShould(function ($query) {
        $query->searchTerm('status', 'pending');
    })
    ->minimumShouldMatch(1)  // 至少匹配一个 should 条件
    ->searchMust(function ($query) {
        $query->searchRange('price', 100, null);
    })
    ->searchMustNot(function ($query) {
        $query->searchTerm('category', 'test');
    })
    ->searchQuery();
```

#### must 条件 (AND)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchMust(function ($query) {
        $query->searchTerm('status', 'active');
    })
    ->searchMust(function ($query) {
        $query->searchRange('price', 100, 500);
    })
    ->searchQuery();
```

#### should 条件 (OR)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchShould(function ($query) {
        $query->searchTerm('category', 'electronics');
    })
    ->searchShould(function ($query) {
        $query->searchTerm('category', 'computers');
    })
    ->minimumShouldMatch(1)  // 至少匹配一个
    ->searchQuery();
```

#### must_not 条件 (NOT)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchTerm('status', 'active')
    ->searchMustNot(function ($query) {
        $query->searchTerm('is_deleted', true);
    })
    ->searchQuery();
```

#### filter 条件 (无评分过滤)

```php
$rows = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchMatch('description', '手机')  // 参与评分
    ->searchFilter(function ($query) {
        $query->searchTerm('status', 'active');  // 不参与评分，仅过滤
    })
    ->searchQuery();
```

### 异步搜索查询

```php
$context = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchTerm('status', 'active')
    ->asyncSearchQuery();

// 稍后获取结果
$rows = $context->HWait();
```

---

## SQL 查询

### 同步 SQL 查询

```php
// 直接执行 SQL
$rows = Ots::connection()->sql("SELECT * FROM users WHERE id = 1");

// 使用 Query Builder 构建 SQL
$rows = Ots::connection()->table('users')
    ->where('status', 'active')
    ->where('age', '>', 18)
    ->sqlGet();
```

### 异步 SQL 查询

```php
$context = Ots::connection()->asyncSql("SELECT * FROM users WHERE id = 1");

// 稍后获取结果
$rows = $context->HWait();
```

### 构建 SQL 字符串

```php
$sql = Ots::connection()->table('users')
    ->where('status', 'active')
    ->toSql();
// 输出: SELECT * FROM users WHERE status = 'active'
```

---

## Schema 操作

### 表操作

#### 创建表

```php
use HughCube\Laravel\OTS\Ots;
use HughCube\Laravel\OTS\Schema\Blueprint;

Ots::connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
    // 定义主键
    $table->integer('id')->primaryKey()->autoIncrement();
    $table->string('partition_key')->primaryKey();
});
```

#### 创建表（带预定义属性列）

预定义属性列用于创建二级索引，必须在创建表时定义。

```php
Ots::connection()->getSchemaBuilder()->create('orders', function (Blueprint $table) {
    // 定义主键
    $table->string('user_id')->primaryKey();
    $table->integer('order_id')->primaryKey()->autoIncrement();

    // 预定义属性列（用于二级索引）
    $table->definedString('status');       // 字符串类型
    $table->definedInteger('amount');      // 整数类型
    $table->definedDouble('price');        // 浮点数类型
    $table->definedBoolean('is_paid');     // 布尔类型
    $table->definedBlob('data');           // 二进制类型

    // 或者使用通用方法指定类型
    $table->definedColumn('category', DefinedColumnTypeConst::DCT_STRING);
});
```

#### 删除表

```php
Ots::connection()->getSchemaBuilder()->drop('users');
```

#### 检查表是否存在

```php
if (Ots::connection()->getSchemaBuilder()->hasTable('users')) {
    // 表存在
}
```

#### 获取所有表

```php
$tables = Ots::connection()->getSchemaBuilder()->getAllTables();
```

### 二级索引

#### 创建全局二级索引

```php
Ots::connection()->getSchemaBuilder()->createGlobalIndex(
    'users',           // 表名
    'users_email_idx', // 索引名
    function (Blueprint $table) {
        $table->string('email')->primaryKey();
        $table->integer('id')->primaryKey();

        // 定义索引包含的属性列
        $table->definedColumn('name');
        $table->definedColumn('created_at');
    }
);
```

#### 创建本地二级索引

```php
Ots::connection()->getSchemaBuilder()->createLocalIndex(
    'users',
    'users_status_idx',
    function (Blueprint $table) {
        $table->integer('id')->primaryKey();
        $table->string('status')->primaryKey();
    }
);
```

#### 删除二级索引

```php
Ots::connection()->getSchemaBuilder()->dropSecondaryIndex('users', 'users_email_idx');
```

### 搜索索引

#### 创建搜索索引

```php
use Aliyun\OTS\ProtoBuffer\Protocol\FieldType;

Ots::connection()->getSchemaBuilder()->createSearchIndex(
    'products',       // 表名
    'products_index', // 索引名
    [
        'field_schemas' => [
            ['field_name' => 'name', 'field_type' => FieldType::TEXT, 'analyzer' => 'single_word'],
            ['field_name' => 'description', 'field_type' => FieldType::TEXT, 'analyzer' => 'max_word'],
            ['field_name' => 'price', 'field_type' => FieldType::DOUBLE],
            ['field_name' => 'status', 'field_type' => FieldType::KEYWORD],
            ['field_name' => 'created_at', 'field_type' => FieldType::LONG],
        ],
    ]
);
```

#### 列出搜索索引

```php
$indexes = Ots::connection()->getSchemaBuilder()->listSearchIndexes('products');
```

#### 检查是否有搜索索引

```php
// 检查表是否有任何搜索索引
if (Ots::connection()->getSchemaBuilder()->hasSearchIndex('products')) {
    // 有搜索索引
}

// 检查是否有特定名称的搜索索引
if (Ots::connection()->getSchemaBuilder()->hasSearchIndex('products', 'products_index')) {
    // 指定的搜索索引存在
}
```

#### 检查是否有二级索引

```php
// 检查表是否有任何二级索引
if (Ots::connection()->getSchemaBuilder()->hasSecondaryIndex('users')) {
    // 有二级索引
}

// 检查是否有特定名称的二级索引
if (Ots::connection()->getSchemaBuilder()->hasSecondaryIndex('users', 'users_email_idx')) {
    // 指定的二级索引存在
}
```

#### 查看搜索索引详情

```php
$info = Ots::connection()->getSchemaBuilder()->describeSearchIndex('products', 'products_index');
```

#### 删除搜索索引

```php
Ots::connection()->getSchemaBuilder()->dropSearchIndex('products', 'products_index');
```

---

## 事务支持

OTS 支持本地事务，用于同一分区键下的多行原子操作。

### 基础事务用法

```php
use HughCube\Laravel\OTS\Ots;

$connection = Ots::connection();

// 开始事务（需要指定分区键）
$transaction = $connection->beginLocalTransaction('users', ['partition_key' => 'pk_value']);

try {
    // 事务内操作
    $transaction->putRow([
        'table_name' => 'users',
        'primary_key' => [['id', 1], ['partition_key', 'pk_value']],
        'attribute_columns' => [['name', 'John']],
    ]);

    $transaction->updateRow([
        'table_name' => 'users',
        'primary_key' => [['id', 2], ['partition_key', 'pk_value']],
        'update_of_attribute_columns' => [
            'PUT' => [['balance', 100]],
        ],
    ]);

    // 提交事务
    $transaction->commit();
} catch (\Exception $e) {
    // 回滚事务
    $transaction->rollback();
    throw $e;
}
```

### 使用回调方式

```php
$connection->localTransaction('users', ['partition_key' => 'pk_value'], function ($transaction) {
    $transaction->putRow([...]);
    $transaction->updateRow([...]);
    // 回调结束自动提交，异常自动回滚
});
```

---

## 并行扫描

用于大数据量的高效扫描，支持多线程并行处理。

### 基础用法

```php
use HughCube\Laravel\OTS\ParallelScanner;

$scanner = new ParallelScanner(Ots::connection(), 'products', 'products_index');

// 设置查询条件
$scanner->matchAll();

// 获取所有结果
$rows = $scanner->get();

// 获取计数
$count = $scanner->count();
```

### 高级配置

```php
$scanner = new ParallelScanner(Ots::connection(), 'products', 'products_index');

// 设置扫描参数
$scanner->limit(1000)           // 每次扫描的行数
    ->aliveTime(60)             // 会话存活时间（秒）
    ->columns(['id', 'name'])   // 指定返回的列
    ->query($customQuery);      // 自定义查询条件

// 计算分片
$scanner->computeSplits();

// 获取分片扫描器
$partitionScanners = $scanner->getPartitionScanners();

// 扫描特定分片
$rows = $scanner->getPartition(0);
```

---

## 数据流处理

用于监听表数据变更，实现实时数据同步。

### 启用数据流

```php
Ots::connection()->getSchemaBuilder()->enableStream('users', 24); // 24小时过期
```

### 读取数据流

```php
use HughCube\Laravel\OTS\Stream\StreamReader;

$reader = new StreamReader(Ots::connection(), 'users');

// 获取所有分片的记录
$records = $reader->readAll();

// 收集所有记录
$allRecords = $reader->collect();
```

### 监听数据变更

```php
$reader = new StreamReader(Ots::connection(), 'users');

// 长轮询监听（阻塞式）
$reader->watch(function ($records) {
    foreach ($records as $record) {
        // 处理变更记录
        $actionType = $record['action_type']; // PUT_ROW, UPDATE_ROW, DELETE_ROW
        $primaryKey = $record['primary_key'];
        $columns = $record['columns'] ?? [];
    }
});
```

### 禁用数据流

```php
Ots::connection()->getSchemaBuilder()->disableStream('users');
```

---

## 缓存驱动

Laravel OTS 提供了完整的缓存驱动实现。

### 配置缓存

在 `config/cache.php` 中添加：

```php
'stores' => [
    'ots' => [
        'driver' => 'ots',
        'connection' => 'ots',
        'table' => 'cache',
    ],
],
```

### 基础缓存操作

```php
use Illuminate\Support\Facades\Cache;

// 设置缓存
Cache::store('ots')->put('key', 'value', 3600);

// 获取缓存
$value = Cache::store('ots')->get('key');

// 永久缓存
Cache::store('ots')->forever('key', 'value');

// 删除缓存
Cache::store('ots')->forget('key');

// 批量操作
Cache::store('ots')->putMany(['key1' => 'value1', 'key2' => 'value2'], 3600);
$values = Cache::store('ots')->many(['key1', 'key2']);
```

### 原子操作

```php
// 自增/自减
Cache::store('ots')->increment('counter', 1);
Cache::store('ots')->decrement('counter', 1);

// 条件设置（不存在才设置）
Cache::store('ots')->add('key', 'value', 3600);
```

### 分布式锁

```php
$lock = Cache::store('ots')->lock('processing', 10); // 10秒超时

if ($lock->get()) {
    try {
        // 执行需要锁保护的操作
    } finally {
        $lock->release();
    }
}

// 阻塞式获取锁
$lock->block(5, function () {
    // 获取到锁后执行
});
```

### 清理过期缓存

```php
// 手动清理过期行
Cache::store('ots')->flushExpiredRows();
```

---

## Eloquent ORM

### 定义模型

```php
use HughCube\Laravel\OTS\Eloquent\Model;

class User extends Model
{
    protected $connection = 'ots';
    protected $table = 'users';

    // 定义主键列
    protected function getPrimaryKeyColumns(): array
    {
        return [
            ['id', PrimaryKeyTypeConst::CONST_INTEGER],
        ];
    }

    // 定义搜索索引（可选）
    protected $searchIndex = 'users_index';
}
```

### 使用模型

```php
// 按主键查询
$user = User::find(['id' => 1]);

// 创建
$user = new User();
$user->id = 1;
$user->name = 'John';
$user->save();

// 更新
$user->name = 'John Doe';
$user->save();

// 删除
$user->delete();
```

---

## Laravel Sanctum 集成

支持将 Sanctum 的 Personal Access Token 存储在 OTS 中，提供两种实现方式。

### 方式1：纯 OTS 表查询（推荐）

不依赖 Eloquent Model，直接操作 OTS 表。

```php
use HughCube\Laravel\OTS\Sanctum\PersonalAccessToken;

// 配置（可选）
PersonalAccessToken::setConnectionName('ots');      // 设置 OTS 连接
PersonalAccessToken::setTableName('my_tokens');     // 设置表名

// 创建 Token
$token = PersonalAccessToken::createToken($user, 'api-token', ['read', 'write']);
echo $token->plainTextToken;  // 返回明文 token

// 查找 Token
$token = PersonalAccessToken::findToken($plainTextToken);
if ($token && $token->isValidAccessToken()) {
    $user = $token->tokenable();  // 获取关联用户
}

// 检查权限
if ($token->can('write')) {
    // 有写入权限
}

// 更新 Token
$token->last_used_at = now();
$token->save();

// 删除 Token
$token->delete();
```

### 方式2：Eloquent Model 方式

继承自 Laravel Sanctum 的 Model，兼容 Sanctum 的所有特性。

```php
use HughCube\Laravel\OTS\Sanctum\EloquentPersonalAccessToken;

// 查找 Token
$token = EloquentPersonalAccessToken::findToken($plainTextToken);

// 其他操作与标准 Sanctum Model 相同
```

### Sanctum 配置

在 `AppServiceProvider` 中注册：

```php
use Laravel\Sanctum\Sanctum;
use HughCube\Laravel\OTS\Sanctum\PersonalAccessToken;

public function boot()
{
    Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
}
```

### 创建 Token 表

```php
Ots::connection()->getSchemaBuilder()->create('personal_access_tokens', function (Blueprint $table) {
    $table->string('token')->primaryKey();    // token hash
    $table->string('app')->primaryKey();      // 应用名（分区键）
});
```

---

## 异步操作

大多数操作都支持异步模式，返回 `RequestContext` 对象，可以通过 `HWait()` 方法等待结果。

### 异步查询

```php
// 异步单行查询
$context = Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->async()
    ->findByPrimaryKey(['id' => 1]);

// 异步搜索
$context = Ots::connection()->table('products')
    ->useSearchIndex('products_index')
    ->searchTerm('status', 'active')
    ->asyncSearchQuery();

// 异步 SQL
$context = Ots::connection()->asyncSql("SELECT * FROM users");

// 等待结果
$result = $context->HWait();
```

### 异步写入操作

```php
// 异步插入
$context = Ots::connection()->table('users')
    ->primaryKeyColumns(['id' => PrimaryKeyTypeConst::CONST_INTEGER])
    ->asyncInsert(['id' => 1, 'name' => 'John']);

// 或者使用 async() 链式调用
$context = Ots::connection()->table('users')
    ->async()
    ->insert(['id' => 1, 'name' => 'John']);

// 异步插入并获取自增 ID
$context = Ots::connection()->table('users')
    ->primaryKeyColumns([
        'partition' => PrimaryKeyTypeConst::CONST_STRING,
        'id' => PrimaryKeyTypeConst::CONST_PK_AUTO_INCR,
    ])
    ->asyncInsertGetId(['partition' => 'user', 'name' => 'John']);

// 获取结果后解析自增 ID
$response = $context->HWait();
$id = $response['primary_key'][1][1] ?? null;

// 异步更新
$context = Ots::connection()->table('users')
    ->primaryKey(['id' => 1])
    ->asyncUpdate(['name' => 'John Doe']);

// 异步删除
$context = Ots::connection()->table('users')
    ->asyncDelete(['id' => 1]);
```

### 并行执行多个异步操作

```php
// 同时发起多个异步请求
$contexts = [];
$contexts[] = Ots::connection()->table('users')->async()->findByPrimaryKey(['id' => 1]);
$contexts[] = Ots::connection()->table('users')->async()->findByPrimaryKey(['id' => 2]);
$contexts[] = Ots::connection()->table('users')->async()->findByPrimaryKey(['id' => 3]);

// 等待所有结果
$results = [];
foreach ($contexts as $context) {
    $results[] = $context->HWait();
}
```

### 通用异步处理

```php
use HughCube\Laravel\OTS\OTS\Handlers\OTSHandlers;

$context = OTSHandlers::asyncDoHandle(
    Ots::connection()->getOts(),
    'getRow',
    $request
);

$response = $context->HWait();
```

---

## 直接访问 OTS SDK

对于未封装的高级功能，可以直接访问底层 OTSClient：

```php
$otsClient = Ots::connection()->getOts();

// 调用任意 SDK 方法
$response = $otsClient->describeTable(['table_name' => 'users']);

// 或者通过 Connection 的魔术方法
$response = Ots::connection()->describeTable(['table_name' => 'users']);
```

---

## 工具方法

### 解析行数据

```php
use HughCube\Laravel\OTS\Ots;

// 解析单行
$row = Ots::parseRow($response['row']);

// 解析自增 ID
$id = Ots::parseRowAutoId($response);
```

### 检查批量写入结果

```php
if (!Ots::isBatchWriteSuccess($response)) {
    Ots::throwBatchWriteException($response);
}
```

---

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/hughcube-php/laravel-ots/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/hughcube-php/laravel-ots/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT
