Couckan

```composer install ```

```openssl req -x509 -out localhost.crt -keyout localhost.key -newkey rsa:4096 -nodes -sha256 -subj '/CN=localhost' -extensions EXT -config <(printf \"[dn]CN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth\") ```

```php start.php start  ```



ou


# Construire l’image
docker-compose build --no-cache
# Lancer la stack
docker-compose up -d
# Vérifier les logs de Workerman
docker logs -f couckan-app
