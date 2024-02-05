This is a fork of [Gregwar/Tex2png](https://github.com/Gregwar/Tex2png) updated to PHP 8.1

Tex2png
=======

This class provides a way to create easily LaTeX formulas.

With it, you can convert raw formulas like:

`\sum_{i = 0}^{i = n} \frac{i}{2}`

To nice images like:

![Sum formula](demo/sum.png)

Requirement
-----------

To use this library you'll need :

* **latex** : to compile formulas (with math support)
* **dvipng** : to convert dvis to png
* **shell_exec** : you need to be able to call the php `shell_exec()` function

You'll also need a temporary folder and, of courses, enough permissions to write to the 
target directory

Installation
------------

```shell
composer require metalinspired/tex2png
```

Usage
-----

```php
<?php

// This will create a formula and save it to sum.png
(new Tex2png('\sum_{i = 0}^{i = n} \frac{i}{2}'))
    ->saveTo('sum.png')
    ->generate();
```

Changing the density
--------------------

The second constructor parameter is the image density :

```php
<?php

(new Tex2png('\sum_{i = 0}^{i = n} \frac{i}{2}', 300))
    ->generate();
```

Default density is **155**, you can choose to generate really big images, this is an example
of the formula with a density of 1000 :

![Sum formula (density=1000)](demo/sum-big.png)

License
-------

This class is under MIT license, for more information, please refer to the [LICENSE](LICENSE) file
