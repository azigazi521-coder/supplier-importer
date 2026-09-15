FROM webdevops/php-nginx-dev:8.4

# Zmiana z --global na --system
RUN git config --system --add safe.directory /app
