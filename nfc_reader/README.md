# NFC reader bridge

This small local service reads the UID from a PC/SC-compatible NFC reader. It
does not connect to the database and cannot change a card balance. The cashier
page sends the UID to Laravel, and Laravel validates and deducts the card balance
inside the sale transaction.

## Windows setup

1. Install the NFC reader driver and confirm it appears in Windows Smart Card
   devices.
2. Open PowerShell in this folder and create the environment:

   ```powershell
   py -m venv .venv
   .\.venv\Scripts\Activate.ps1
   pip install -r requirements.txt
   Copy-Item .env.example .env
   ```

3. Set `NFC_ALLOWED_ORIGINS` in `.env` to the exact URL shown in the cashier
   browser. Set `NFC_READER_NAME` only when the computer has more than one smart
   card reader.
4. Start the service before opening the cashier terminal:

   ```powershell
   python reader_service.py
   ```

5. Open `http://127.0.0.1:8765/health`. A ready reader returns `"ready": true`.

The Laravel application uses `NFC_READER_URL` from its own `.env` file. A
keyboard-wedge reader still works when the local service is not running.
