#!/bin/bash

echo "🚀 Starting Ollama HR Model Server..."

# Step 1: Install Python requirements
echo "📦 Installing Python requirements..."
pip install fastapi uvicorn requests pydantic

# Step 2: Start Ollama server (in background)
echo "🔧 Starting Ollama server..."
ollama serve &
OLLAMA_PID=$!

# Wait for Ollama to start
echo "⏳ Waiting for Ollama to start..."
sleep 10

# Step 3: Create/load the model
echo "🤖 Creating HR model..."
ollama create saudi-hr-model -f ollama/Modelfile

if [ $? -eq 0 ]; then
    echo "✅ Model created successfully"
else
    echo "❌ Failed to create model"
    kill $OLLAMA_PID
    exit 1
fi

# Step 4: Start the API server
echo "🌐 Starting API server..."
echo "API will be available at: http://localhost:8000"
echo "Docs available at: http://localhost:8000/docs"
echo ""
echo "Press Ctrl+C to stop all services"

python server.py