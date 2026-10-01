using KoopSchoolDesktop.Forms;
using KoopSchoolDesktop.Services;

namespace KoopSchoolDesktop
{
    static class Program
    {
        [STAThread]
        static void Main(string[] args)
        {
            ApplicationConfiguration.Initialize();

            Application.SetUnhandledExceptionMode(UnhandledExceptionMode.CatchException);
            AppDomain.CurrentDomain.UnhandledException += (s, e) =>
            {
                try { File.WriteAllText(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "crash_log.txt"), e.ExceptionObject?.ToString() ?? "Unknown exception"); } catch { }
            };
            Application.ThreadException += (s, e) =>
            {
                try { File.WriteAllText(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "crash_log.txt"), e.Exception?.ToString() ?? "Unknown thread exception"); } catch { }
            };

            // Ensure configuration is loaded
            AppConfig.Load();

            if (args.Length > 0 && args[0] == "--test")
            {
                DiagnosticTest.Run();
                return;
            }

            // If offline / SQLite is active, ensure local tables exist
            try
            {
                DatabaseService.Instance.InitializeLocalDatabase();
            }
            catch
            {
                // Ignore initialization warnings
            }

            // Start Embedded HTTP API Server for mobile app and cashier POS terminal integration
            if (AppConfig.Current.ApiEnabled)
            {
                try
                {
                    ApiServer.Instance.Start(AppConfig.Current.ApiPort);
                }
                catch { }
            }

            Application.ApplicationExit += (s, e) =>
            {
                ApiServer.Instance.Stop();
            };

            // Show Login Dialog
            using var loginForm = new LoginForm();
            if (loginForm.ShowDialog() == DialogResult.OK && loginForm.IsAuthenticated)
            {
                Application.Run(new MainForm(loginForm.LoggedInUser));
            }

            ApiServer.Instance.Stop();
        }
    }
}