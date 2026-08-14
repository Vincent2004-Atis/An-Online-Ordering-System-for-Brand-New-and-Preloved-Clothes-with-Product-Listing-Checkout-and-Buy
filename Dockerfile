# Margaux Collections — Railway deployment image
# Serves the app at /Margaux_Collections/ (same as your XAMPP setup)
# so none of your existing hardcoded "/Margaux_Collections/..." paths
# need to change.

FROM php:8.2-apache

# mysqli is required by config/database.php
RUN docker-php-ext-install mysqli 
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf  
RUN a2enmod mpm_prefork rewrite 
RUN apache2ctl -M

# Copy the whole project into /var/www/html/Margaux_Collections
# (mirrors exactly where it lives inside XAMPP's htdocs)
COPY . /var/www/html/Margaux_Collections/

# Redirect the bare domain ("/") to the app, since all your app code
# expects to live under /Margaux_Collections/
RUN echo '<?php header("Location: /Margaux_Collections/auth/login.php"); exit;' \
    > /var/www/html/index.php

# Give Apache write access to the folders the app writes to (profile photo
# uploads, homepage slot images)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/Margaux_Collections/uploads \
    && chmod -R 755 /var/www/html/Margaux_Collections/images

# Railway assigns a random $PORT at runtime — Apache needs to listen on it
RUN printf '#!/bin/bash\nsed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf\nexec apache2-foreground\n' \
    > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

EXPOSE 80
CMD ["/usr/local/bin/start-apache.sh"]
