<?php

namespace App\Jobs;

use App\Services\Olt\OltConnectionService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRebootOnuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queueId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $queueId)
    {
        $this->queueId = $queueId;
    }

    /**
     * Execute the job.
     */
    public function handle(OltConnectionService $oltService): void
    {
        $item = DB::table('reboot_onu_queues')->where('id', $this->queueId)->first();
        if (!$item) {
            return;
        }

        // Jangan proses ulang jika sudah sukses
        if ($item->status === 'success') {
            return;
        }

        // Tandai processing
        DB::table('reboot_onu_queues')->where('id', $this->queueId)->update([
            'status' => 'processing',
            'started_at' => now(),
            'attempts' => $item->attempts + 1,
            'updated_at' => now(),
        ]);

        try {
            $indexOlt = trim($item->index_olt ?? '');
            $kodeOlt = trim($item->kode_olt ?? '');

            if (empty($indexOlt)) {
                DB::table('reboot_onu_queues')->where('id', $this->queueId)->update([
                    'status' => 'failed',
                    'response_message' => 'Index OLT kosong, lewati remote reboot.',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
                return;
            }

            $rebootResult = $oltService->rebootOnu($indexOlt, $kodeOlt);
            $isSuccess = (bool)($rebootResult['success'] ?? false);
            $message = $rebootResult['message'] ?? ($isSuccess ? 'Reboot ONU berhasil' : 'Gagal reboot ONU');

            DB::table('reboot_onu_queues')->where('id', $this->queueId)->update([
                'status' => $isSuccess ? 'success' : 'failed',
                'response_message' => $message,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            // Catat ke log registrasi jika tabel ada
            if (\Illuminate\Support\Facades\Schema::hasTable('trx_batchjob_register_log')) {
                try {
                    DB::table('trx_batchjob_register_log')->insert([
                        'kode_batchjob_register_log' => 'L-RB-' . $item->nomor_internet . '-' . rand(1000, 9999),
                        'nomor_internet' => $item->nomor_internet,
                        'status_reg' => '21',
                        'kat_log' => '21',
                        'note_schedule' => "BACKGROUND REBOOT ONU '{$indexOlt}' (" . ($isSuccess ? 'BERHASIL' : 'GAGAL') . "): {$message}",
                        'date_schedule' => now()->format('Y-m-d'),
                        'time_schedule' => now()->format('H:i:s'),
                        'date_create' => now()->format('Y-m-d H:i:s'),
                        'user_create' => $item->operator ?? 'SYSTEM_QUEUE',
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Gagal log trx_batchjob_register_log background reboot: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            Log::error("Error executing ProcessRebootOnuJob for queue ID {$this->queueId}: " . $e->getMessage());
            DB::table('reboot_onu_queues')->where('id', $this->queueId)->update([
                'status' => 'failed',
                'response_message' => 'Exception: ' . $e->getMessage(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
