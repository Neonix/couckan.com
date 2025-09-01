COMMAND="openssl req -x509 -out localhost.crt -keyout localhost.key -newkey rsa:4096 -nodes -sha256 -subj '/CN=localhost' -extensions EXT -config <(printf \"[dn]CN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth\")"

echo $COMMAND



# Stopper tous les conteneurs en cours
docker stop $(docker ps -aq)

# Supprimer tous les conteneurs
docker rm -f $(docker ps -aq)

# (optionnel) Nettoyer images/volumes/réseaux inutilisés
docker system prune -af            # sans les volumes
# ou bien, pour tout raser y compris VOLUMES (perte de données) :
docker system prune -af --volumes


docker compose down -v || true
# Construire l’image
docker-compose build --no-cache
# Lancer la stack
docker-compose up -d
# Vérifier les logs de Workerman
docker logs -f couckan-app