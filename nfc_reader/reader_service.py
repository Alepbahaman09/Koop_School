"""Local PC/SC bridge used by the Koop School cashier terminal.

The reader service only reads a card UID. Payments and balance changes stay in
Laravel, where they can be validated and saved in one database transaction.
"""

from __future__ import annotations

import json
import logging
import os
import threading
from dataclasses import dataclass
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from time import time
from urllib.parse import parse_qs, urlparse

from dotenv import load_dotenv
from smartcard.CardMonitoring import CardMonitor, CardObserver
from smartcard.System import readers


GET_CARD_UID = [0xFF, 0xCA, 0x00, 0x00, 0x00]


@dataclass(frozen=True)
class Settings:
    host: str
    port: int
    reader_name: str | None
    allowed_origins: tuple[str, ...]

    @classmethod
    def from_environment(cls) -> "Settings":
        origins = tuple(
            origin.strip().rstrip("/")
            for origin in os.getenv("NFC_ALLOWED_ORIGINS", "").split(",")
            if origin.strip()
        )

        if not origins:
            raise ValueError("NFC_ALLOWED_ORIGINS must contain the cashier website URL.")

        reader_name = os.getenv("NFC_READER_NAME", "").strip()

        return cls(
            host=os.getenv("NFC_READER_HOST", "127.0.0.1"),
            port=int(os.getenv("NFC_READER_PORT", "8765")),
            reader_name=reader_name or None,
            allowed_origins=origins,
        )


class CardEvents:
    """Stores the most recent card event for the terminal's short polling."""

    def __init__(self) -> None:
        self._lock = threading.Lock()
        self._sequence = 0
        self._card_uid: str | None = None
        self._read_at: float | None = None

    def add(self, card_uid: str) -> None:
        with self._lock:
            self._sequence += 1
            self._card_uid = card_uid
            self._read_at = time()

    def after(self, sequence: int) -> dict[str, object]:
        with self._lock:
            event_is_new = self._sequence > sequence
            return {
                "sequence": self._sequence,
                "card_uid": self._card_uid if event_is_new else None,
                "read_at": self._read_at if event_is_new else None,
            }


class NfcReader(CardObserver):
    def __init__(self, events: CardEvents, reader_name: str | None = None) -> None:
        self.events = events
        self.reader_name = reader_name
        self.monitor = CardMonitor()

    def start(self) -> None:
        self.monitor.addObserver(self)

    def stop(self) -> None:
        self.monitor.deleteObserver(self)

    def matching_readers(self) -> list[str]:
        available = [str(reader) for reader in readers()]
        if not self.reader_name:
            return available

        wanted = self.reader_name.casefold()
        return [name for name in available if wanted in name.casefold()]

    def update(self, observable, actions) -> None:  # noqa: ANN001 - pyscard callback
        added_cards, _removed_cards = actions

        for card in added_cards:
            self._read_card(card)

    def _read_card(self, card) -> None:  # noqa: ANN001 - pyscard card object
        connection = card.createConnection()

        try:
            connection.connect()
            active_reader = str(connection.getReader())

            if self.reader_name and self.reader_name.casefold() not in active_reader.casefold():
                return

            uid_bytes, status_high, status_low = connection.transmit(GET_CARD_UID)
            if (status_high, status_low) != (0x90, 0x00):
                logging.warning(
                    "Reader %s could not read the card UID (status %02X%02X).",
                    active_reader,
                    status_high,
                    status_low,
                )
                return

            card_uid = "".join(f"{byte:02X}" for byte in uid_bytes)
            self.events.add(card_uid)
            logging.info("Card read on %s: %s", active_reader, card_uid)
        except Exception:
            logging.exception("Unable to read the NFC card.")
        finally:
            try:
                connection.disconnect()
            except Exception:
                pass


class ReaderBridge(ThreadingHTTPServer):
    def __init__(self, settings: Settings, reader: NfcReader, events: CardEvents) -> None:
        super().__init__((settings.host, settings.port), ReaderRequestHandler)
        self.settings = settings
        self.reader = reader
        self.events = events


class ReaderRequestHandler(BaseHTTPRequestHandler):
    server: ReaderBridge

    def do_OPTIONS(self) -> None:
        if not self._origin_is_allowed():
            self._send_json(403, {"message": "Origin is not allowed."})
            return

        self.send_response(204)
        self._send_cors_headers()
        self.send_header("Access-Control-Allow-Methods", "GET, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Accept")
        self.send_header("Access-Control-Max-Age", "600")
        if self.headers.get("Access-Control-Request-Private-Network") == "true":
            self.send_header("Access-Control-Allow-Private-Network", "true")
        self.end_headers()

    def do_GET(self) -> None:
        if not self._origin_is_allowed():
            self._send_json(403, {"message": "Origin is not allowed."})
            return

        request = urlparse(self.path)

        if request.path == "/health":
            matching_readers = self.server.reader.matching_readers()
            self._send_json(
                200,
                {
                    "ready": bool(matching_readers),
                    "readers": matching_readers,
                },
            )
            return

        if request.path == "/card":
            query = parse_qs(request.query)
            try:
                sequence = max(0, int(query.get("after", ["0"])[0]))
            except ValueError:
                self._send_json(400, {"message": "The after value must be a number."})
                return

            self._send_json(200, self.server.events.after(sequence))
            return

        self._send_json(404, {"message": "Not found."})

    def log_message(self, message: str, *args: object) -> None:
        logging.debug(message, *args)

    def _origin_is_allowed(self) -> bool:
        origin = self.headers.get("Origin")
        if not origin:
            return True

        return origin.rstrip("/") in self.server.settings.allowed_origins

    def _send_cors_headers(self) -> None:
        origin = self.headers.get("Origin")
        if origin and origin.rstrip("/") in self.server.settings.allowed_origins:
            self.send_header("Access-Control-Allow-Origin", origin)
            self.send_header("Vary", "Origin")

    def _send_json(self, status: int, payload: dict[str, object]) -> None:
        body = json.dumps(payload).encode("utf-8")
        self.send_response(status)
        self._send_cors_headers()
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Cache-Control", "no-store")
        self.end_headers()
        self.wfile.write(body)


def main() -> None:
    load_dotenv(Path(__file__).with_name(".env"))
    settings = Settings.from_environment()
    events = CardEvents()
    reader = NfcReader(events, settings.reader_name)
    server = ReaderBridge(settings, reader, events)

    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    reader.start()

    logging.info("NFC reader bridge listening at http://%s:%s", settings.host, settings.port)
    if settings.reader_name:
        logging.info("Using readers whose name contains: %s", settings.reader_name)

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        logging.info("Stopping NFC reader bridge.")
    finally:
        reader.stop()
        server.server_close()


if __name__ == "__main__":
    main()
