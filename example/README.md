Instructions to run example code:

 1. Download and import `world database` from https://dev.mysql.com/doc/index-other.html
 2. Download `example` directory, containing all the example files, and place it in your server's root directory
 3. Run `index.php` from your server's root directory like this, http://localhost/example/index.php. But before that, you may want to change the following statement as per your database's username and password,
 
    $pg = new Pagination('pdo', 'localhost', 'root', 'root', 'world');
