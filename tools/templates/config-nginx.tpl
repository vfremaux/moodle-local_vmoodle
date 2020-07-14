server {
    listen 80 ;
    listen [::]:80;

    root {$dirroot};

    index index.php index.html index.htm index.nginx-debian.html;

    server_name {$mainhostwwwroot};
{$subservernames}

    location ~ "^/phpmyadmin" {
        root /usr/share;
        location ~ \.php(/|$) {
            fastcgi_split_path_info ^(.\.php)(/.)$;
            include fastcgi_params;
            fastcgi_param PATH_INFO $fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include snippets/fastcgi-php.conf;
            fastcgi_read_timeout 600s;
            fastcgi_pass unix:/var/run/php/php{$phpversion}-fpm.sock;
        }
    }

    location / {
        # First attempt to serve request as file, then
        # as directory, then fall back to displaying a 404.
        try_files $uri $uri/ = 404;
    }

    # pass PHP scripts to FastCGI server
    #
    location ~ \.php(/|$) {
        fastcgi_split_path_info ^(.\.php)(/.)$;
        include fastcgi_params;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include snippets/fastcgi-php.conf;
        fastcgi_read_timeout 600s;
        fastcgi_pass unix:/var/run/php/php{$phpversion}-fpm.sock;
    }

    error_log /var/log/nginx/{$mainhost}_error.log;
    access_log /var/log/nginx/{$mainhost}_access.log;
}