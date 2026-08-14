#!/bin/bash
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf
a2enmod mpm_prefork >/dev/null 2>&1 || true
sed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf
ls -la /etc/apache2/mods-enabled/ | grep mpm
exec apache2-foreground
