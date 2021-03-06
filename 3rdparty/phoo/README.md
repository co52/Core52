Phoo Ooyala API PHP Client Library
==================================
Developed by Company52


Requirements
------------
* PHP 5.3.1 or greater (comes bundled with the [hash extension](http://www.php.net/manual/en/book.hash.php) for SHA-256 API signature generation)
* [Ooyala](http://www.ooyala.com/) Account
* Ooyala Provider ID / Partner Code (found in Backlot Account tab under Developers)
* Ooyala Secret Code (found in Backlot Account tab under Developers)


Step 1
------
Require and register the Phoo class autoloader:

    require __DIR__ . '/lib/Phoo/Autoloader.php'; # Your path may vary - replace __DIR__ with correct path
    $autoloader = new \Phoo\Autoloader();
    $autoloader->register();

Step 2
------
Create a new instance of Phoo\Backlot to use in your code, and pass in your Ooyala partner code and secret code.

    $backlot = new \Phoo\Backlot($partnerCode, $secretCode);

Running Tests
-------------
To run the test quite, you need [PHPUnit](http://phpunit.de) installed.
Installation instructions can be found in the README file in the [PHPUnit GitHub Repo](https://github.com/sebastianbergmann/phpunit/)  

To run the tests (from the default project directory), use the command:
    phpunit tests/AllTests.php