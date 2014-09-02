PHP Simple Framework
==============
Get started Easily
```php
    <?php
    $ppf= include "/library/base.php";
    $ppf->map('GET|POST', '/', function(){ 
                                      echo 'Hello world'; 
                                });
```
Have a Database?
```php
    <?php
    $ppf= include "/library/base.php";
    $ppf->app_etize("db", "mysql:dbname=dbname", "username","password"); // replace dbname, username, and password
    $ppf->map('GET', '/user/[i:id]', 
							function($ppf, $params){
								$id= $params['id'];
								$user = $ppf->db->select('users');
								$user = $user->where('id= ?', $id);
								$user = $user->fetch();
								var_dump($user);
							});
```
Want to create a simple logo?
 ```php   
    $ppf->map('GET', '/images/logo.png', 
							function($ppf, $params){
								$ppf->stock("logo", "Image");
								$ppf->logo->hollow("250","80","");
								$ppf->logo->text('Logo', 'yourfont.otf', 32, '#FFFFFF', 'top', 0, 20); // have a font (replace yourfont.otf)
								$ppf->logo->best_fit(320, 200)->invert();
								$ppf->logo->output();
							});
```							

PHP-Simple-Framework is (inspired by [Sinatra](http://www.sinatrarb.com/))

What does it use?
*[AltoRouter](https://github.com/dannyvankooten/AltoRouter/)
*[FluentPDO](http://fluentpdo.com/)

