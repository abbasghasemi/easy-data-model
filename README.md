<h1 style="text-align: center">abbasghasemi/easy-data-model</h1>

abbasghasemi/easy-data-model is a PHP library for easy data model creation and control.

## Installation

The preferred method of installation is via [Composer](https://getcomposer.org). Run the following
command to install the package and add it as a requirement to your project's
`composer.json`:

```bash
composer require abbasghasemi/easy-data-model
```

## Description
- Complete tools for modeling and validate inputs
- Helps in building APIs
- Can also simplify working with values & database in PHP
- Support the [Collection library](https://github.com/abbasghasemi/collection)
- See [Safe annotation](https://github.com/abbasghasemi/easy-data-model/blob/master/src/Safe.php)
- See [Ignore annotation](https://github.com/abbasghasemi/easy-data-model/blob/master/src/Ignore.php)
- See [PropertyNullable](https://github.com/abbasghasemi/easy-data-model/blob/master/src/PropertyNullable.php)
- See [ValueConvertor](https://github.com/abbasghasemi/easy-data-model/blob/master/src/ValueConvertor.php)
- See [FinallyAssert](https://github.com/abbasghasemi/easy-data-model/blob/master/src/FinallyAssert.php)
- Use `ModelBuilder::fromArray(array $data,string objectOrClass);`

## Example

```php
<?php

include_once 'vendor/autoload.php';

use AG\DataModel\ArrayList;
use AG\DataModel\EnumBuilder;
use AG\DataModel\Ignore;
use AG\DataModel\ModelBuilder;
use AG\DataModel\ModelBuilderException;
use AG\DataModel\PropertyNullable;
use AG\DataModel\Safe;
use AG\DataModel\ValueConvertor;

enum Number
{
    use EnumBuilder;

    case one;
    case two;
}

enum Number2: string
{
    case one = "one";
    case two = "two";
}

class SplitData
{
    #[Safe(min: 3, max: 30)]
    public string $name;
    #[Safe(min: 1, max: 1)]
    public int $count;
}

class Book
{
    private int $id; // Ignore
    #[Ignore]
    public string $name;  // Ignore
    #[Safe(alternate: ['is_article'])]
    public bool $isArticle; // non-null and searches for is_article key
    public ?string $description;  // allow null
    #[Ignore]
    private ?bool $isAvailable; // Ignore private
}

class Items extends ModelBuilder implements PropertyNullable, ValueConvertor
{
    #[Safe(min: 3, max: 30)]
    public string $name;
    #[Safe(min: 1, max: 1)]
    public int $count;

    public Book $book;
    #[Safe(alternate: [])]
    public SplitData $splitData;
    #[Safe(pattern: '/^\w+$/i', max: 6, overflow: true)]
    public string $text;
    #[Safe(max: 5, type: 'int')]
    public mixed $meta;
    /**
     * @var ArrayList<Book>
     */
    #[Safe(max: 3, type: Book::class)]
    public ArrayList $books;
    public int $id;
    public int|string $flag;
    #[Safe(alternate: ['count'], type: 'int', convertor: true)]
    public Number2 $convertor;
    public ?Number2 $number2;
    protected Number $number;

    public function onNullable(string $propertyName): bool
    {
        if ('number2' === $propertyName) {
            return false; // can't be null
        }
        return true;
    }

    function onConvert(string $propertyName, mixed $propertyValue): mixed
    {
        if ($propertyName === 'convertor') {
            return Number2::one;
        }
        return null;
    }
}

// Valid sample
$data = [
    'name' => 'test name',
    'count' => '1', // try to convert to a number.
    'book' => [
        'is_article' => true,
        'isAvailable' => '1', // try to convert to a boolean,
        'description' => 48999696 // convert to '48999696'
    ],
    'text' => 'test_text', // values greater than 6 are ignored
    'meta' => [1, 2, 3, 4, 5], // allow null,empty and maxLength 5.
    'books' => [
        [
            'is_article' => true,
            'description' => 'Managed by ArrayList'
        ],
        [
            'is_article' => false,
        ]
    ],
    'id' => 3.9,  // convert to 3
    'flag' => '1578788',  // is string
    'number2' => 'two',
    'number' => 'one'
];
$item = new Items($data); // or ModelBuilder::fromArray($data, objectOrClass);
echo '<pre>';
echo $item->books->first()->description . '<br>';
echo json_encode($item, JSON_PRETTY_PRINT);
echo '<br>';
/*
 * Output: Success
{
    "name": "test name",
    "count": 1,
    "book": {
        "isArticle": true,
        "description": "48999696",
        "isAvailable": true
    },
    "splitData": {
        "name": "test name",
        "count": 1
    },
    "text": "test_t",
    "meta": [
        1,
        2,
        3,
        4,
        5
    ],
    "books": [
        {
            "isArticle": true,
            "description": "Managed by ArrayList"
            "isAvailable": null
        },
        {
            "isArticle": false,
            "description": null,
            "isAvailable": null
        }
    ],
    "id": 3,
    "flag": "1578788",
    "convertor": "one",
    "number2": "two"
}
 */

// Invalid sample
echo '<br>Invalid sample<br>';
$data = [
    'name' => 'te',
    'count' => '2',
    'book' => [
        'is_article' => 'fAlsE',
        'isAvailable' => 'Falsee',
    ],
    'text' => 'test^text',
    'meta' => [1, 2, 3, 4, 5, 6],
    'books' => [1, 2],
    'number' => 'three'
];
try {
    $item = new Items($data);
    echo $item->name;
} catch (ModelBuilderException $e) {
//    echo $e->class;
//    echo $e->property;
//    echo $e->propertyValue ?? 'Empty';
    echo $e->getMessage();
    // Output: Failed
    // The value of 'Falsee' is invalid for parameter 'isAvailable' in `Book`.
    // The value of 'te' is invalid for parameter 'name' in `Items`.
    // The value of '2' is invalid for parameter 'count' in `Items`.
    // The value of 'test^text' is invalid for parameter 'text' in `Items`.
    // The value of 'Array' is invalid for parameter 'meta' in `Items`.
    // The value of 'Array' is invalid for parameter 'books' in `Items`.
    // The parameter 'id' is required in `Items`.
    // The parameter 'flag' is required in `Items`.
    // The value of 'three' is invalid for parameter 'number' in `Items`.
    // The value of 'ONE' is invalid for parameter 'number' in `Items`.
}
```

## See also collection
[A collection of complete tools for working with PHP arrays.](https://github.com/abbasghasemi/collection)

## Author & support
This library was created by [Abbas Ghasemi](https://farasource.com/).

You can report issues at the [GitHub Issue Tracker](https://github.com/abbasghasemi/easy-data-model/issues).