import { ref } from 'vue';

/**
 * Normalisasi format ucapan speech recognition:
 * Mengubah format otomatis Google seperti "Rp15.000" atau "Rp 15.000"
 * kembali menjadi teks natural seperti "15 ribu" atau "15000".
 */
export function normalizeSpokenCurrency(text: string): string {
    if (!text) return '';

    return text.replace(/Rp\s*([\d\.,]+)/gi, (match, numStr) => {
        // Hapus pemisah ribuan titik atau koma
        const cleanNum = numStr.replace(/[\.,]/g, '');
        const num = parseInt(cleanNum, 10);

        if (isNaN(num)) return match;

        // Jika kelipatan juta (contoh: 2.000.000 -> 2 juta, 1.500.000 -> 1.5 juta)
        if (num >= 1000000) {
            const juta = num / 1000000;
            const formatted = Number.isInteger(juta) ? juta : juta.toFixed(1).replace('.', ',');
            return `${formatted} juta`;
        }

        // Jika kelipatan ribu (contoh: 15.000 -> 15 ribu, 250.000 -> 250 ribu)
        if (num >= 1000) {
            const ribu = num / 1000;
            const formatted = Number.isInteger(ribu) ? ribu : ribu.toFixed(1).replace('.', ',');
            return `${formatted} ribu`;
        }

        return String(num);
    });
}

export function useSpeechRecognition() {
    const isSupported = ref(
        typeof window !== 'undefined' &&
            ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window)
    );
    const isListening = ref(false);
    const speechError = ref<string | null>(null);

    let recognition: any = null;

    if (typeof window !== 'undefined') {
        const SpeechRecognitionClass =
            (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;

        if (SpeechRecognitionClass) {
            recognition = new SpeechRecognitionClass();
            recognition.continuous = false;
            recognition.interimResults = true;
            recognition.lang = 'id-ID'; // Bahasa Indonesia
        }
    }

    const startListening = (
        onResult: (transcript: string, isFinal: boolean) => void,
        onError?: (err: string) => void
    ) => {
        if (!recognition) {
            const msg =
                'Browser Anda belum mendukung input suara (Web Speech API). Silakan gunakan Google Chrome, Edge, atau Safari.';
            speechError.value = msg;
            onError?.(msg);
            return;
        }

        speechError.value = null;

        recognition.onstart = () => {
            isListening.value = true;
        };

        recognition.onresult = (event: any) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            const current = finalTranscript || interimTranscript;
            if (current) {
                // Normalisasi ucapan mata uang dari "Rp15.000" menjadi "15 ribu"
                const normalized = normalizeSpokenCurrency(current);
                onResult(normalized, Boolean(finalTranscript));
            }
        };

        recognition.onerror = (event: any) => {
            isListening.value = false;
            let errorMsg = 'Terjadi kesalahan saat merekam suara.';
            if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                errorMsg = 'Izin mikrofon ditolak. Mohon izinkan akses mikrofon di browser Anda.';
            } else if (event.error === 'no-speech') {
                errorMsg = 'Tidak ada suara yang terdeteksi. Silakan coba bicara kembali.';
            } else if (event.error === 'network') {
                errorMsg = 'Koneksi internet bermasalah saat memproses suara.';
            }
            speechError.value = errorMsg;
            onError?.(errorMsg);
        };

        recognition.onend = () => {
            isListening.value = false;
        };

        try {
            recognition.start();
        } catch (e: any) {
            // If already started, stop then restart
            try {
                recognition.stop();
            } catch (_) {}
            isListening.value = false;
        }
    };

    const stopListening = () => {
        if (recognition && isListening.value) {
            try {
                recognition.stop();
            } catch (_) {}
            isListening.value = false;
        }
    };

    const toggleListening = (
        onResult: (transcript: string, isFinal: boolean) => void,
        onError?: (err: string) => void
    ) => {
        if (isListening.value) {
            stopListening();
        } else {
            startListening(onResult, onError);
        }
    };

    return {
        isSupported,
        isListening,
        speechError,
        startListening,
        stopListening,
        toggleListening,
    };
}
