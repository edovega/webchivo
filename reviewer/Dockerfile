# syntax=docker/dockerfile:1
FROM python:3.12-slim-bookworm

RUN apt-get update \
 && apt-get install -y --no-install-recommends chromium chromium-driver procps \
 && rm -rf /var/lib/apt/lists/*

RUN useradd --create-home --uid 10001 --shell /usr/sbin/nologin reviewer

WORKDIR /opt/reviewer
COPY reviewer/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY reviewer/reviewer.py .

# Never let Selenium Manager look for drivers online.
ENV PYTHONUNBUFFERED=1 \
    SE_OFFLINE=true \
    CHROME_BINARY=/usr/bin/chromium \
    CHROMEDRIVER_PATH=/usr/bin/chromedriver

USER reviewer
CMD ["python", "/opt/reviewer/reviewer.py"]
