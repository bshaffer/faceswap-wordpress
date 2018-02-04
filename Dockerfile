# Dockerfile extending the generic PHP image with application files for a
# single application.
FROM gcr.io/google-appengine/php:latest

# The Docker image will configure the document root according to this
# environment variable.
ENV DOCUMENT_ROOT /app
ENV WORDPRESS_DB_HOST wordpress-mysql
