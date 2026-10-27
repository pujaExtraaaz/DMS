using System.Net.Http.Json;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;
using System.Text.RegularExpressions;

var exeDir = Path.GetDirectoryName(Environment.ProcessPath) ?? AppContext.BaseDirectory;
var configPath = Path.Combine(exeDir, "config.json");
if (!File.Exists(configPath))
{
    Console.WriteLine("config.json was not next to tally_connector.exe.");
    Console.WriteLine("Copy config.example.json to config.json in this same folder, then open the exe again.");
    Console.WriteLine("Press Enter to close.");
    Console.ReadLine();
    return 1;
}

var config = JsonSerializer.Deserialize<ConnectorConfig>(File.ReadAllText(configPath))
    ?? throw new InvalidOperationException("config.json is empty.");
config.PollSeconds = Math.Clamp(config.PollSeconds <= 0 ? 8 : config.PollSeconds, 5, 10);
config.DmsUrl = config.DmsUrl.TrimEnd('/');
config.TallyUrl = config.TallyUrl.TrimEnd('/');

using var dms = new HttpClient { Timeout = TimeSpan.FromSeconds(60) };
dms.DefaultRequestHeaders.Add("X-Tally-Connector-Token", config.ConnectorToken);
using var tally = new HttpClient { Timeout = TimeSpan.FromSeconds(90) };
try
{
    Directory.CreateDirectory(Path.Combine(exeDir, "logs"));
}
catch (Exception exception)
{
    Console.WriteLine("Log folder was not created: " + exception.Message);
}

Console.WriteLine($"DMS {config.DmsUrl}");
Console.WriteLine($"Tally {config.TallyUrl}");
Console.WriteLine($"Company {config.Company}");
Console.WriteLine($"Polling every {config.PollSeconds} seconds. Close this window to stop.");

while (true)
{
    try
    {
        await PollAsync(config, dms, tally);
    }
    catch (Exception exception)
    {
        Log(exception.Message);
    }

    await Task.Delay(TimeSpan.FromSeconds(config.PollSeconds));
}

static async Task PollAsync(ConnectorConfig config, HttpClient dms, HttpClient tally)
{
    var health = await dms.GetAsync($"{config.DmsUrl}/api/tally-connector/health");
    var healthBody = await health.Content.ReadAsStringAsync();
    if (!health.IsSuccessStatusCode)
    {
        Log($"DMS health {(int)health.StatusCode}: {Trim(healthBody, 300)}");
        return;
    }

    var pending = await dms.GetFromJsonAsync<PendingResponse>($"{config.DmsUrl}/api/tally-connector/pending?limit=20");
    var sent = 0;
    var failed = 0;
    foreach (var item in pending?.Items ?? [])
    {
        var response = await PostTallyAsync(tally, config.TallyUrl, item.Payload ?? "");
        var status = IsFailure(response) ? "failed" : "sent";
        if (status == "sent") sent++; else failed++;
        var body = JsonSerializer.Serialize(new ResultBody(status, status == "failed" ? Trim(response, 1800) : null, Trim(response, 50000)));
        await dms.PostAsync($"{config.DmsUrl}/api/tally-connector/{item.Id}/result", new StringContent(body, Encoding.UTF8, "application/json"));
    }

    var exports = await dms.GetFromJsonAsync<ExportResponse>($"{config.DmsUrl}/api/tally-connector/exports");
    var imported = 0;
    foreach (var item in exports?.Items ?? [])
    {
        var response = await PostTallyAsync(tally, config.TallyUrl, item.Xml ?? "");
        if (IsFailure(response) && !response.Contains("<ENVELOPE", StringComparison.OrdinalIgnoreCase))
        {
            Log($"Tally export {item.Key} failed: {Trim(response, 300)}");
            continue;
        }

        var body = JsonSerializer.Serialize(new ImportBody(item.Key ?? "", response));
        var import = await dms.PostAsync($"{config.DmsUrl}/api/tally-connector/imports", new StringContent(body, Encoding.UTF8, "application/json"));
        var importText = await import.Content.ReadAsStringAsync();
        if (!import.IsSuccessStatusCode)
        {
            Log($"DMS import {item.Key} {(int)import.StatusCode}: {Trim(importText, 300)}");
            continue;
        }

        imported++;
        try
        {
            using var doc = JsonDocument.Parse(importText);
            var created = doc.RootElement.TryGetProperty("created", out var c) ? c.GetInt32() : 0;
            var updated = doc.RootElement.TryGetProperty("updated", out var u) ? u.GetInt32() : 0;
            var conflicts = doc.RootElement.TryGetProperty("conflicts", out var k) ? k.GetInt32() : 0;
            if (created + updated + conflicts > 0)
            {
                Log($"{item.Key}: created {created}, updated {updated}, conflicts {conflicts}.");
            }
        }
        catch (JsonException)
        {
        }
    }

    Log(imported == 0
        ? $"Poll sent {sent}, failed {failed}. No Tally changes."
        : $"Poll sent {sent}, failed {failed}. Checked {imported} lists.");
}

static async Task<string> PostTallyAsync(HttpClient tally, string url, string xml)
{
    using var content = new StringContent(xml, Encoding.UTF8, "text/xml");
    var response = await tally.PostAsync(url, content);
    var body = await response.Content.ReadAsStringAsync();
    if (!response.IsSuccessStatusCode)
    {
        return $"<LINEERROR>Tally HTTP {(int)response.StatusCode}</LINEERROR>{body}";
    }

    return body;
}

static bool IsFailure(string xml)
{
    if (string.IsNullOrWhiteSpace(xml)) return true;
    if (xml.Contains("<LINEERROR>", StringComparison.OrdinalIgnoreCase)) return true;
    return Tag(xml, "ERRORS") > 0 || Tag(xml, "EXCEPTIONS") > 0;
}

static int Tag(string xml, string name)
{
    var match = Regex.Match(xml, $"<{name}>\\s*(-?\\d+)\\s*</{name}>", RegexOptions.IgnoreCase);
    return match.Success ? int.Parse(match.Groups[1].Value) : 0;
}

static string Trim(string value, int max) => value.Length <= max ? value : value[..max];

static void Log(string message)
{
    var line = $"{DateTime.Now:yyyy-MM-dd HH:mm:ss} {message}";
    Console.WriteLine(line);
    try
    {
        File.AppendAllText(Path.Combine(Path.GetDirectoryName(Environment.ProcessPath) ?? AppContext.BaseDirectory, "logs", "connector.log"), line + Environment.NewLine);
    }
    catch
    {
        // The console line is enough when the log file is locked.
    }
}

sealed class ConnectorConfig
{
    [JsonPropertyName("dms_url")] public string DmsUrl { get; set; } = "http://127.0.0.1:8000";
    [JsonPropertyName("connector_token")] public string ConnectorToken { get; set; } = "";
    [JsonPropertyName("tally_url")] public string TallyUrl { get; set; } = "http://127.0.0.1:9000";
    [JsonPropertyName("company")] public string Company { get; set; } = "";
    [JsonPropertyName("poll_seconds")] public int PollSeconds { get; set; } = 8;
}

sealed class PendingResponse
{
    [JsonPropertyName("items")] public List<PendingItem>? Items { get; set; }
}

sealed class PendingItem
{
    [JsonPropertyName("id")] public int Id { get; set; }
    [JsonPropertyName("payload")] public string? Payload { get; set; }
}

sealed class ExportResponse
{
    [JsonPropertyName("items")] public List<ExportItem>? Items { get; set; }
}

sealed class ExportItem
{
    [JsonPropertyName("key")] public string? Key { get; set; }
    [JsonPropertyName("xml")] public string? Xml { get; set; }
}

sealed record ResultBody(string status, string? error, string? response);
sealed record ImportBody(string key, string xml);