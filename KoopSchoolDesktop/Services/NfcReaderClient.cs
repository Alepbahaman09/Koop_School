using System.Text.Json;

namespace KoopSchoolDesktop.Services
{
    public class NfcCardEvent
    {
        public int Sequence { get; set; }
        public string? CardUid { get; set; }
        public double? ReadAt { get; set; }
    }

    public class NfcReaderClient : IDisposable
    {
        private static readonly HttpClient HttpClient = new HttpClient { Timeout = TimeSpan.FromSeconds(2) };
        private readonly System.Windows.Forms.Timer _pollTimer;
        private int _lastSequence = 0;
        private bool _isPolling = false;

        public event Action<string>? OnCardTapped;

        public NfcReaderClient()
        {
            _pollTimer = new System.Windows.Forms.Timer { Interval = 800 };
            _pollTimer.Tick += async (s, e) => await PollReaderAsync();
        }

        public void Start()
        {
            _pollTimer.Start();
        }

        public void Stop()
        {
            _pollTimer.Stop();
        }

        private async Task PollReaderAsync()
        {
            if (_isPolling) return;
            _isPolling = true;

            try
            {
                string url = $"{AppConfig.Current.NfcReaderUrl}/events?after={_lastSequence}";
                var response = await HttpClient.GetAsync(url);
                if (response.IsSuccessStatusCode)
                {
                    string json = await response.Content.ReadAsStringAsync();
                    using var doc = JsonDocument.Parse(json);
                    var root = doc.RootElement;

                    if (root.TryGetProperty("sequence", out var seqProp))
                    {
                        _lastSequence = seqProp.GetInt32();
                    }

                    if (root.TryGetProperty("card_uid", out var uidProp) && uidProp.ValueKind == JsonValueKind.String)
                    {
                        string? uid = uidProp.GetString();
                        if (!string.IsNullOrWhiteSpace(uid))
                        {
                            OnCardTapped?.Invoke(uid.Trim());
                        }
                    }
                }
            }
            catch
            {
                // Silently ignore if reader service is not running locally
            }
            finally
            {
                _isPolling = false;
            }
        }

        public void Dispose()
        {
            _pollTimer.Stop();
            _pollTimer.Dispose();
        }
    }
}
