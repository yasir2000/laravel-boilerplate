# Multi-stage build using Maven image
FROM maven:3.9-openjdk-21 AS builder

# Set working directory
WORKDIR /app

# Copy POM file first to leverage Docker layer caching
COPY pom.xml .

# Download dependencies
RUN mvn dependency:go-offline

# Copy source code
COPY src src

# Build the application
RUN mvn clean package -DskipTests

# Use OpenJDK runtime image for final stage
FROM openjdk:21-jdk-slim

WORKDIR /app

# Copy the built JAR from builder stage
COPY --from=builder /app/target/integration-service-1.0.0.jar app.jar

# Expose port
EXPOSE 8083

# Run the application
CMD ["java", "-jar", "app.jar"]