<h1 align="center">abbasghasemi/easy-data-model</h1>

<p align="center">
    <strong>A PHP library for building a data model from array data.</strong>
</p>

abbasghasemi/easy-data-model is a PHP library for easy data model creation and control.

## Installation

The preferred method of installation is via [Composer](https://getcomposer.org). Run the following
command to install the package and add it as a requirement to your project's
`composer.json`:

```bash
composer require abbasghasemi/easy-data-model
```

## Example
```php
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

class Book extends ModelBuilder
{
    private int $id; // Ignore
    #[Ignore]
    public string $name;  // Ignore
    #[Safe(name: 'is_article')]
    public bool $isArticle; // non-null and searches for is_article key
    public ?string $description;  // allow null
    public ?bool $isAvailable;
}

class Items extends ModelBuilder
{
    #[Safe(min: 3, max: 30)]
    public string $name;
    #[Safe(min: 1, max: 1)]
    public int $count;
    public Book $book;
    #[Safe(pattern: '/^\w+$/i', length: 6)]
    public string $text;
    #[Safe(max: 5, type: 'int')]
    public mixed $meta;
    #[Safe(max: 3, type: 'Book')]
    public array $books;
    public int $id;
    public int|string $flag;
    public ?Number2 $number2;
    protected Number $number;
}

echo json_encode(new Items([
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
        ],
        [
            'is_article' => false,
        ]
    ],
    'id' => 3.9,  // convert to 3
    'flag' => '1578788',  // is string
    'number2' => 'two',
    'number' => 'one'
]));
/*
 {
    "name":"test name",
    "count":1,
    "book":{
        "isArticle":true,
        "description":"48999696",
        "isAvailable":true
    },
    "text":"test_t",
    "meta":[
        1,2,3,4,5
    ],
    "books":[
        {
            "isArticle":true,
            "description":null,
            "isAvailable":null
        },
        {
            "isArticle":false,
            "description":null,
            "isAvailable":null
        }
    ],
    "id":3,
    "flag":"1578788",
    "number2":"two"
}
 */

try {
    echo json_encode(new Items([
        'name' => 'te',
        'count' => '2',
        'book' => [
            'is_article' => 'fAlsE',
            'isAvailable' => 'Falsee',
        ],
        'text' => 'test^text',
        'meta' => [1, 2, 3, 4, 5, 6],
        'books' => [1,2],
        'number' => 'three'
    ], true));
} catch (ModelBuilderException $e) {
    echo $e->getMessage();
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
[license]: https://github.com/abbasghasemi/easy-data-model/blob/master/LICENSE