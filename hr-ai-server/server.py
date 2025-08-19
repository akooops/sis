from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import requests
import json

app = FastAPI()

# Request model matching your training data format exactly
class CVRequest(BaseModel):
    application: dict  # This should contain BOTH application data AND job_posting

@app.post("/api/score-cv")
async def score_cv(request: CVRequest):
    # Format exactly like your training data
    instruction = "Rate this job application against the job posting on a scale of 1-10 and provide a brief explanation for the Saudi HR market context."
    
    # The input should be the full application data (which includes job_posting)
    input_data = f"Job Application Data: {json.dumps(request.application)}"
    
    # Send to Ollama with the exact template format from your Modelfile
    ollama_request = {
        "model": "saudi-hr-model", 
        "prompt": f"Below is an instruction that describes a task, paired with an input that provides further context. Write a response that appropriately completes the request.\n\n### Instruction:\n{instruction}\n\n### Input:\n{input_data}\n\n### Response:\n",
        "stream": False,
        "options": {
            "temperature": 0.7,
            "top_p": 0.9,
            "top_k": 40,
            "num_ctx": 4096
        }
    }
    
    try:
        response = requests.post("http://localhost:11434/api/generate", json=ollama_request, timeout=60)
        result = response.json()
        
        output = result["response"]
        
        # Extract numerical score
        import re
        score_match = re.search(r'Score:\s*(\d+)/10', output)
        score = score_match.group(1) if score_match else "N/A"
        
        return {"score": score, "explanation": output}
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)