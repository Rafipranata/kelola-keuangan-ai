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
     * Mendukung single transaction (objek) maupun multiple transactions (array).
     *
     * @param  string  $promptText
     * @param  array  $availableCategories  Array of category names or associative array
     * @return array Array of transactions
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
Tugas Anda adalah mengekstrak teks transaksi dari pengguna ke dalam format JSON.
Pengguna bisa menyebutkan 1 transaksi MAUPUN LEBIH DARI 1 transaksi sekaligus (contoh: "beli bensin 100000 dan beli kopi 5000").

SELALU kembalikan dalam bentuk ARRAY OF OBJECTS (daftar transaksi) dengan struktur:
[
  {
    "nominal": 100000,
    "tipe": "expense", // atau "income"
    "kategori": "Transportasi",
    "deskripsi": "Beli bensin",
    "metode_pembayaran": "Cash", // "Cash", "QRIS", "Transfer Bank", "E-Wallet"
    "tanggal_transaksi": "{$today}" // format YYYY-MM-DD
  },
  {
    "nominal": 5000,
    "tipe": "expense",
    "kategori": "Makan & Minum",
    "deskripsi": "Beli kopi",
    "metode_pembayaran": "Cash",
    "tanggal_transaksi": "{$today}"
  }
]

Aturan:
1. Jika pengguna hanya menyebutkan 1 transaksi, tetap kembalikan dalam array berisi 1 objek [ { ... } ].
2. Pilihan kategori yang tersedia di database: [{$categoryListString}].
3. Cocokkan 'kategori' dengan nama yang paling mendekati dari daftar kategori di atas.
4. Tipe harus: "income" (pemasukan/gaji/saku/bonus/terima uang) atau "expense" (pengeluaran/beli/bayar).
5. Nominal harus berupa angka integer murni tanpa titik atau koma (contoh: 100000, 18rb/18k -> 18000, 2.5jt -> 2500000).
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

            // Jika AI mengembalikan single object { ... }, bungkus ke array [ { ... } ]
            if (is_array($parsedData) && isset($parsedData['nominal'])) {
                $items = [$parsedData];
            } elseif (is_array($parsedData) && isset($parsedData['transactions']) && is_array($parsedData['transactions'])) {
                $items = $parsedData['transactions'];
            } elseif (is_array($parsedData) && isset($parsedData['data']) && is_array($parsedData['data'])) {
                $items = $parsedData['data'];
            } elseif (is_array($parsedData)) {
                $items = $parsedData;
            } else {
                throw new Exception('Format hasil AI tidak valid.');
            }

            $normalizedItems = [];
            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['nominal'])) {
                    continue;
                }

                $nominal = (float) $item['nominal'];
                if ($nominal <= 0) {
                    continue;
                }

                $normalizedItems[] = [
                    'nominal' => $nominal,
                    'tipe' => in_array(strtolower($item['tipe'] ?? ''), ['income', 'expense']) ? strtolower($item['tipe']) : 'expense',
                    'kategori' => $item['kategori'] ?? '',
                    'deskripsi' => $item['deskripsi'] ?? $promptText,
                    'metode_pembayaran' => $item['metode_pembayaran'] ?? 'Cash',
                    'tanggal_transaksi' => ! empty($item['tanggal_transaksi']) ? $item['tanggal_transaksi'] : $today,
                ];
            }

            if (empty($normalizedItems)) {
                throw new Exception('AI tidak dapat menemukan transaksi yang valid dari teks tersebut.');
            }

            return $normalizedItems;
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
