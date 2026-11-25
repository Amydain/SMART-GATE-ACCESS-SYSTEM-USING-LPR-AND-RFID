
from http.server import BaseHTTPRequestHandler, HTTPServer
import json
import threading

latest_result = {
    "plate": "",
    "status": "WAITING"
}

class LPRHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/lpr":
            data = json.dumps(latest_result).encode("utf-8")

            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.send_header("Access-Control-Allow-Origin", "*")
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            self.wfile.write(data)
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        print(format % args)

server = HTTPServer(("0.0.0.0", 5000), LPRHandler)

print("LPR server started on port 5000")
print("Endpoint: http://10.153.88.95:5000/lpr")

server.serve_forever()
