@echo off
echo 🚀 Starting Laravel Octane on Docker...

docker-compose up --build -d

echo ✅ Laravel app with Octane running at: http://localhost:8000
echo 🔧 To see logs: docker-compose logs -f app  
echo 🛑 To stop: docker-compose down

pause