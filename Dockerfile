FROM php:8.2-apache

# MySQL uchun kerakli kengaytmalarni o'rnatish
RUN docker-php-ext-install mysqli

# Apache portini Render talab qiladigan tarzda sozlash
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Bot fayllarini konteynerga nusxalash
COPY . /var/www/html/

# Yozish huquqi kerak (log va vaqtinchalik fayllar uchun)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Render PORT muhit o'zgaruvchisini beradi, Apache shu portni tinglashi kerak
RUN echo 'Listen ${PORT}' >> /etc/apache2/ports.conf
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD bash -c "sed -i 's/Listen 80/Listen $PORT/g' /etc/apache2/ports.conf && sed -i 's/:80/:'$PORT'/g' /etc/apache2/sites-available/000-default.conf && apache2-foreground"
