StaticPHP
==========


Directory structure
-------------------

    application/               - Application directory, can be named differently, also multiple application directories will work (just set a correct "document root" in your web server configuration)
        config/                - Contains configurations files. Calling load::config('filename'); will load configuration file with filename "config/filename.php".
            config.php         - Default config file.
            routing.php        - Default router routing file.
        controllers/           - Controller files, below are some examples.
            home.php           - Class will be used when for example http://example.com/home/ uri is requested.
            test2/
                test2.php      - http://example.com/test2[/method].
                test3.php      - http://example.com/test2/test3[/method].
        files/                 - Directory for various types of files, that needs to be protected from downloading, like icc profiles, wsdl schemas, and other resources
        helpers/               - Helpers directory mostly used for different functions. system.php helper is like a startup script for sending headers, initializing database connections, etc. To load a helper use this method load::helper('filename');.
        models/                - Holds php classes, that are widely used in application. For example, image manipulation class should be placed here as well as database class. To load a model: load::model('filename').
        public/                - Public directory of the site. This is the one where you should point your webserver's document root.
            css/               - Directory for stylesheet files.
            .htaccess          - Access file for apache.
            index.php          - Application main/loader file.
            js/                - Directory for javascript files.
        views/                 - Directory for templates
            errors             - Error templates. for example E404.php for 404 Not Found errors.
    system
        core.php               - Core php file to init loading of session
        load.php               - Class for loading files and holding configuration values.
        router.php             - Router class. It determines correct controller and method to load and some other methods like router::redirect().


Basic Nginx configuration
-------------------

    server {
        listen       80;
        listen       443 ssl;
        server_name  staticphp.gm.lv;

        root   /www/sites/gm.lv/staticphp/application/public;
        index index.php index.html index.htm;

        location / {
            if (!-e $request_filename)
            {
                rewrite  ^(.*)$  /index.php?/$1  last;
            }
        }

        location ~ \.php$ {
            if (!-f $request_filename) {
                return 404;
            }
    
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_index  index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include /etc/nginx/fastcgi_params;
        }
    
    
        location ~ /\.ht {
            return 404;
        }
    }



StaticPHP start page
-------------------
http://staticphp.gm.lv/


Example project
-------------------
Simeple todo example application based on sessions. To view the source, checkout the "example" branch.

http://staticphp-example.gm.lv/

