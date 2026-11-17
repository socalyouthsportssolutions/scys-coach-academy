"""Run the local-only modular preview. No installation or remote service."""
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from functools import partial
from pathlib import Path
import threading, webbrowser
root=Path(__file__).resolve().parent
try:
 server=ThreadingHTTPServer(('127.0.0.1',0),partial(SimpleHTTPRequestHandler,directory=str(root)))
except OSError:
 print('The local preview server could not start. Check Python and local network permissions.')
 raise SystemExit(1)
url=f'http://127.0.0.1:{server.server_address[1]}/'
print(f'Coach Academy preview: {url} — close this window to stop.')
threading.Timer(0.5,lambda:webbrowser.open(url)).start()
try:server.serve_forever()
except KeyboardInterrupt:pass
finally:server.server_close()
