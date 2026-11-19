#!/usr/bin/env python3
"""Simulated portal administrator for the CIB-203 web lab.

Every REVIEWER_INTERVAL seconds it logs in as admin from inside the lab
network, reads the supplier messages, then opens the order paths that
suppliers reported. Never prints cookies, the admin password or the note.
"""

import logging
import os
import secrets
import shutil
import signal
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

import psycopg
from selenium import webdriver
from selenium.common.exceptions import TimeoutException, WebDriverException
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By

# Lab credentials
DB_USER = "reviewer_rw"
DB_PASSWORD = "Reviewer-Lab-2026"
DB_NAME = "suppliers"

APP_URL = os.environ.get("APP_URL", "http://app").rstrip("/")
DB_HOST = os.environ.get("DB_HOST", "db")
CHROME_BINARY = os.environ.get("CHROME_BINARY", "/usr/bin/chromium")
CHROMEDRIVER_PATH = os.environ.get("CHROMEDRIVER_PATH", "/usr/bin/chromedriver")

VISIT_SECONDS = 5
MAX_REPORTS = 5
MAX_PATH_BYTES = 500
PAGE_LOAD_TIMEOUT = 15
SCRIPT_TIMEOUT = 10

# Replaces blocking dialogs, so one alert() payload cannot stall the others.
DIALOG_GUARD = """
(() => {
  const noop = () => undefined;
  try {
    window.alert = noop;
    window.confirm = () => false;
    window.prompt = () => null;
    window.print = noop;
  } catch (err) {}
})();
"""

log = logging.getLogger("reviewer")


def nav_base() -> str:
    """Base URL for the browser, with the app host resolved to an IP.

    Chromium's built-in resolver does not resolve single-label Docker service
    names such as "app", so navigating to http://app fails with
    ERR_CONNECTION_REFUSED. Python's resolver handles it, so we resolve the
    host here and let the browser connect by IP. The origin stays consistent
    across the reviewer's own visits, which is all flags 5 and 6 need.
    """
    parts = urllib.parse.urlsplit(APP_URL)
    host = parts.hostname or "app"
    port = parts.port or (443 if parts.scheme == "https" else 80)
    ip = socket.gethostbyname(host)
    return f"{parts.scheme}://{ip}:{port}"


def interval_seconds() -> int:
    try:
        value = int(os.environ.get("REVIEWER_INTERVAL", "60"))
    except ValueError:
        value = 60
    return max(value, 15)


def handle_sigterm(signum, frame):  # noqa: ARG001
    log.info("stopping")
    sys.exit(0)


def db_connect() -> psycopg.Connection:
    return psycopg.connect(
        host=DB_HOST,
        dbname=DB_NAME,
        user=DB_USER,
        password=DB_PASSWORD,
        connect_timeout=5,
        autocommit=True,
    )


def wait_for_app() -> None:
    while True:
        try:
            with urllib.request.urlopen(APP_URL + "/", timeout=5) as response:
                if response.status < 500:
                    log.info("app is up")
                    return
        except (urllib.error.URLError, OSError):
            pass
        time.sleep(2)


def set_admin_password() -> str:
    password = secrets.token_urlsafe(18)
    while True:
        try:
            with db_connect() as conn:
                cur = conn.execute(
                    "UPDATE users SET password_md5 = md5(%s) WHERE username = 'admin'",
                    (password,),
                )
                if cur.rowcount == 1:
                    log.info("admin password rotated")
                    return password
                log.warning("admin user not found yet, retrying")
        except psycopg.Error as exc:
            log.warning("database not ready (%s), retrying", type(exc).__name__)
        time.sleep(3)


def read_note(conn: psycopg.Connection):
    row = conn.execute(
        "SELECT value FROM reviewer_notes WHERE name = 'internal_note'"
    ).fetchone()
    return row[0] if row else None


def valid_path(path) -> bool:
    """Same rules as report.php."""
    return (
        isinstance(path, str)
        and path.startswith("/")
        and not path.startswith("//")
        and "\\" not in path
        and len(path.encode("utf-8")) <= MAX_PATH_BYTES
    )


def new_browser(profile_dir: str) -> webdriver.Chrome:
    options = Options()
    options.binary_location = CHROME_BINARY
    for arg in (
        "--headless=new",
        "--no-sandbox",
        "--disable-gpu",
        "--disable-dev-shm-usage",
        f"--user-data-dir={profile_dir}",
        "--no-first-run",
        "--no-default-browser-check",
        "--disable-extensions",
        "--disable-sync",
        "--disable-default-apps",
        "--disable-background-networking",
        "--disable-component-update",
        "--disable-domain-reliability",
        "--disable-client-side-phishing-detection",
        "--metrics-recording-only",
        "--no-pings",
        "--safebrowsing-disable-auto-update",
        "--disable-features=Translate,OptimizationHints,MediaRouter,"
        "LocalNetworkAccessChecks,PrivateNetworkAccessRespectPreflightResults,"
        "BlockInsecurePrivateNetworkRequests",
        "--window-size=1280,900",
    ):
        options.add_argument(arg)
    options.set_capability("unhandledPromptBehavior", "dismiss")

    driver = webdriver.Chrome(service=Service(executable_path=CHROMEDRIVER_PATH), options=options)
    driver.set_page_load_timeout(PAGE_LOAD_TIMEOUT)
    driver.set_script_timeout(SCRIPT_TIMEOUT)
    driver.execute_cdp_cmd("Page.addScriptToEvaluateOnNewDocument", {"source": DIALOG_GUARD})
    return driver


def visit(driver: webdriver.Chrome, url: str) -> None:
    try:
        driver.get(url)
    except TimeoutException:
        log.warning("page load timeout")
        try:
            driver.execute_script("window.stop();")
        except WebDriverException:
            pass


def login_admin(driver: webdriver.Chrome, base: str, password: str) -> None:
    visit(driver, base + "/login.php")
    driver.find_element(By.ID, "username").send_keys("admin")
    driver.find_element(By.ID, "password").send_keys(password)
    driver.find_element(By.ID, "login-submit").click()
    deadline = time.monotonic() + 10
    while time.monotonic() < deadline:
        if "/admin/dashboard.php" in driver.current_url:
            return
        time.sleep(0.5)
    raise RuntimeError("admin login failed")


def review_messages(driver: webdriver.Chrome, base: str) -> int:
    visit(driver, base + "/admin/messages.php")
    count = -1
    try:
        count = len(driver.find_elements(By.CSS_SELECTOR, "tr[data-message-id]"))
    except WebDriverException:
        pass
    time.sleep(VISIT_SECONDS)
    return count


def handle_reports(driver: webdriver.Chrome, base: str, conn: psycopg.Connection, note: str) -> int:
    visit(driver, base + "/")
    driver.execute_script("localStorage.setItem('internal_note', arguments[0]);", note)

    rows = conn.execute(
        "SELECT id, path FROM reports WHERE handled = false ORDER BY id LIMIT %s",
        (MAX_REPORTS,),
    ).fetchall()

    handled = 0
    for report_id, path in rows:
        try:
            if valid_path(path):
                # about:blank forces a full load even when only the #fragment differs.
                visit(driver, "about:blank")
                visit(driver, base + path)
                time.sleep(VISIT_SECONDS)
                handled += 1
            else:
                log.info("report %s discarded", report_id)
        except WebDriverException as exc:
            log.warning("report %s failed (%s)", report_id, type(exc).__name__)
        finally:
            conn.execute("UPDATE reports SET handled = true WHERE id = %s", (report_id,))
    return handled


def close_browser(driver) -> None:
    if driver is None:
        return
    try:
        driver.quit()
    except Exception:  # noqa: BLE001
        subprocess.run(["pkill", "-9", "-f", "chromium"], check=False)


def run_cycle(password: str) -> None:
    driver = None
    profile_dir = tempfile.mkdtemp(prefix="reviewer-")
    messages = 0
    reports = 0
    try:
        with db_connect() as conn:
            note = read_note(conn)
            if note is None:
                log.warning("internal note not available yet, skipping cycle")
                return
            base = nav_base()
            driver = new_browser(profile_dir)
            login_admin(driver, base, password)
            messages = review_messages(driver, base)
            reports = handle_reports(driver, base, conn, note)
            del note
    finally:
        close_browser(driver)
        shutil.rmtree(profile_dir, ignore_errors=True)
    log.info("cycle done, messages reviewed: %d, reports handled: %d", messages, reports)


def main() -> None:
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s %(name)s %(levelname)s %(message)s",
        datefmt="%Y-%m-%dT%H:%M:%S",
    )
    signal.signal(signal.SIGTERM, handle_sigterm)
    interval = interval_seconds()
    log.info("starting, interval %ss", interval)

    wait_for_app()
    password = set_admin_password()

    while True:
        started = time.monotonic()
        try:
            run_cycle(password)
        except Exception as exc:  # noqa: BLE001
            detail = str(exc).splitlines()[0][:160] if str(exc) else ""
            log.error("cycle failed: %s %s", type(exc).__name__, detail)
        elapsed = time.monotonic() - started
        time.sleep(max(0.0, interval - elapsed))


if __name__ == "__main__":
    main()
