<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiFinanceService
{
    protected ?string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-3.1-flash');
    }

    /**
     * Parse natural language transaction text into structured data.
     *
     * @param  array  $availableCategories  Array of category names or associative array
     *
     * @throws Exception
     */
    public function parseTransaction(string $promptText, array $availableCategories): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Google Gemini API Key belum diatur. Silakan atur GEMINI_API_KEY di file .env.');
        }

        $today = Carbon::now()->format('Y-m-d');
        $categoryListString = implode(', ', $availableCategories);

        $systemInstruction = <<<INSTRUCTION
Anda adalah asisten pencatat keuangan cerdas (Smart AI Financial Parser) Bahasa Indonesia.
Tugas Anda adalah mengekstrak teks transaksi bebas dari pengguna ke dalam format JSON yang tepat.

Gunakan format JSON berikut:
{
  "nominal": 18000,
  "tipe": "expense", // atau "income"
  "kategori": "Makan & Minum",
  "deskripsi": "Makan ayam geprek",
  "metode_pembayaran": "QRIS", // default "Cash" jika tidak disebutkan, bisa juga "QRIS", "Transfer Bank", "E-Wallet"
  "tanggal_transaksi": "{$today}" // format YYYY-MM-DD. Gunakan tanggal hari ini ({$today}) jika tidak disebutkan atau jika "hari ini", sesuaikan jika "kemarin", dsb.
}

Pilihan kategori yang tersedia di database: [{$categoryListString}].
Cocokkan 'kategori' dengan nama yang paling mendekati dari daftar kategori yang tersedia di atas.
Tipe harus salah satu dari: "income" (pemasukan/gaji/saku/bonus/terima uang) atau "expense" (pengeluaran/beli/bayar).
Nominal harus berupa angka integer tanpa titik atau koma mata uang (contoh: 18rb/18k -> 18000, 2.5jt -> 2500000).
INSTRUCTION;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => "Tolong parsing transaksi ini: \"{$promptText}\""],
                        ],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                ],
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                Log::error('Gemini API Error: '.$errorMsg);
                throw new Exception('Gagal menghubungi Gemini API: '.($response->json('error.message') ?? 'Terjadi kesalahan pada layanan AI'));
            }

            $result = $response->json();
            $rawJson = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (! $rawJson) {
                throw new Exception('AI tidak menghasilkan data JSON yang diharapkan.');
            }

            $parsedData = json_decode($rawJson, true);

            if (! is_array($parsedData) || ! isset($parsedData['nominal'])) {
                throw new Exception('Format hasil AI tidak valid.');
            }

            // Defaults fallback
            $parsedData['nominal'] = (float) $parsedData['nominal'];
            $parsedData['tipe'] = in_array(strtolower($parsedData['tipe'] ?? ''), ['income', 'expense']) ? strtolower($parsedData['tipe']) : 'expense';
            $parsedData['deskripsi'] = $parsedData['deskripsi'] ?? $promptText;
            $parsedData['metode_pembayaran'] = $parsedData['metode_pembayaran'] ?? 'Cash';
            $parsedData['tanggal_transaksi'] = ! empty($parsedData['tanggal_transaksi']) ? $parsedData['tanggal_transaksi'] : $today;

            return $parsedData;
        } catch (Exception $e) {
            Log::error('GeminiFinanceService parseTransaction exception: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate personal financial advice based on monthly aggregate summary.
     *
     * @throws Exception
     */
    public function generateFinancialAdvice(array $financialSummary): string
    {
        if (empty($this->apiKey)) {
            throw new Exception('Google Gemini API Key belum diatur. Silakan atur GEMINI_API_KEY di file .env.');
        }

        $totalIncome = number_format($financialSummary['total_income'] ?? 0, 0, ',', '.');
        $totalExpense = number_format($financialSummary['total_expense'] ?? 0, 0, ',', '.');
        $netSavings = number_format($financialSummary['net_savings'] ?? 0, 0, ',', '.');
        $topCategories = json_encode($financialSummary['top_expense_categories'] ?? [], JSON_UNESCAPED_UNICODE);
        $budgetStatus = json_encode($financialSummary['budget_status'] ?? [], JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Bertindaklah sebagai perencana keuangan pribadi (Personal Financial Advisor) yang ramah, praktis, suportif, dan to-the-point dalam Bahasa Indonesia.

Berikut adalah ringkasan keuangan pengguna untuk bulan berjalan:
- Total Pemasukan: Rp {$totalIncome}
- Total Pengeluaran: Rp {$totalExpense}
- Arus Kas Bersih (Sisa/Defisit): Rp {$netSavings}
- Kategori Pengeluaran Terbesar: {$topCategories}
- Status Pemakaian Anggaran Kategori: {$budgetStatus}

Berikan analisis ringkas dan rekomendasi penghematan/manajemen uang konkret:
- Maksimal 3-4 poin actionable.
- Format Markdown yang rapi (gunakan bolding untuk poin penting dan emoji yang sesuai).
- Hindari teori bertele-tele, langsung berikan saran praktis dan dorongan motivasi.
PROMPT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                ],
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                Log::error('Gemini API Error: '.$errorMsg);
                throw new Exception('Gagal menghubungi Gemini API: '.($response->json('error.message') ?? 'Terjadi kesalahan pada layanan AI'));
            }

            $result = $response->json();
            $advice = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (! $advice) {
                throw new Exception('AI tidak menghasilkan tanggapan nasihat.');
            }

            return $advice;
        } catch (Exception $e) {
            Log::error('GeminiFinanceService generateFinancialAdvice exception: '.$e->getMessage());
            throw $e;
        }
    }
}
