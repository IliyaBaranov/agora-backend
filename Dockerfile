FROM php:8.2-cli

# Copy app
WORKDIR /app
COPY . .

# Expose port
EXPOSE 8080

# Run PHP server
CMD php -S 0.0.0.0:8080 -t public
