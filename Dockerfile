FROM python:3.11-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Data directory for the persistent SQLite database
RUN mkdir -p data

CMD ["python", "-m", "src.main"]
