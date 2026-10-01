using System.Text.Json;

namespace KoopSchoolDesktop.Services
{
    public class AppSettings
    {
        public string DatabaseType { get; set; } = "PostgreSQL"; // "PostgreSQL" or "SQLite"
        public string PgHost { get; set; } = "aws-1-ap-northeast-2.pooler.supabase.com";
        public int PgPort { get; set; } = 5432;
        public string PgDatabase { get; set; } = "postgres";
        public string PgUsername { get; set; } = "postgres.aozubsvfcflmutlicrzo";
        public string PgPassword { get; set; } = "Alepmans09@";
        public string SqlitePath { get; set; } = "koop_school_local.db";
        public string NfcReaderUrl { get; set; } = "http://127.0.0.1:8765";
        public string SchoolName { get; set; } = "KOPERASI SEKOLAH";
        public string CurrencySymbol { get; set; } = "RM";
        public int ApiPort { get; set; } = 8000;
        public bool ApiEnabled { get; set; } = true;
        public string ReceiptHeader { get; set; } = "KOPERASI SEKOLAH MENENGAH\nJALAN DUTA, KUALA LUMPUR\nTEL: 03-12345678";
        public string ReceiptFooter { get; set; } = "TERIMA KASIH! SILA DATANG LAGI.\nBARANG YANG DIBELI TIDAK BOLEH DIKEMBALIKAN.";
    }

    public static class AppConfig
    {
        private static readonly string ConfigPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "appsettings.json");
        public static AppSettings Current { get; private set; } = new();

        static AppConfig()
        {
            Load();
        }

        public static void Load()
        {
            try
            {
                if (File.Exists(ConfigPath))
                {
                    string json = File.ReadAllText(ConfigPath);
                    var settings = JsonSerializer.Deserialize<AppSettings>(json);
                    if (settings != null)
                    {
                        Current = settings;
                        return;
                    }
                }
            }
            catch
            {
                // Fall back to default
            }

            Current = new AppSettings();
            Save();
        }

        public static void Save()
        {
            try
            {
                string json = JsonSerializer.Serialize(Current, new JsonSerializerOptions { WriteIndented = true });
                File.WriteAllText(ConfigPath, json);
            }
            catch
            {
                // Ignore save error on restricted environments
            }
        }

        public static string GetPostgreSqlConnectionString()
        {
            return $"Host={Current.PgHost};Port={Current.PgPort};Database={Current.PgDatabase};Username={Current.PgUsername};Password={Current.PgPassword};SSL Mode=Require;Trust Server Certificate=true;Timeout=15;Command Timeout=30";
        }

        public static string GetSqliteConnectionString()
        {
            return $"Data Source={Current.SqlitePath}";
        }
    }
}
