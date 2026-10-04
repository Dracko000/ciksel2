<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class iclockController extends Controller
{
    /**
     * Handshake with ZKTeco Device
     * Otorisasi device ditangani middleware AuthorizeIclockDevice, sehingga
     * SN di sini sudah pasti terdaftar dan online/last_seen_at sudah diperbarui.
     */
    public function handshake(Request $request)
    {
        $sn = $request->input('SN');

        if (!$sn) {
            return "ERROR: SN REQUIRED";
        }

        $response = "GET OPTION FROM: {$sn}\r\n" .
             "Stamp=9999\r\n" .
             "OpStamp=" . time() . "\r\n" .
             "ErrorDelay=60\r\n" .
             "Delay=30\r\n" .
             "ResLogDay=18250\r\n" .
             "ResLogDelCount=10000\r\n" .
             "ResLogCount=50000\r\n" .
             "TransTimes=00:00;14:05\r\n" .
             "TransInterval=1\r\n" .
             "TransFlag=1111000000\r\n" .
             "Realtime=1\r\n" .
             "Encrypt=0";

        return $response;
    }

    /**
     * Receive Attendance Records
     * Code Expert: Refactored to use Eloquent and better parsing
     */
    public function receiveRecords(Request $request)
    {
        $sn = $request->input('SN');
        $table = $request->input('table');
        $stamp = $request->input('Stamp');

        $device = $request->attributes->get('iclock_device');

        if (!$device) {
            return "ERROR: DEVICE NOT REGISTERED";
        }

        try {
            $content = $request->getContent();
            $lines = preg_split('/\\r\\n|\\r|\\n/', $content);
            $count = 0;

            if ($table === "OPERLOG") {
                return "OK: " . count(array_filter($lines));
            }

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;

                $data = explode("\t", $line);
                
                if (count($data) < 2) continue;

                Attendance::create([
                    'sn' => $sn,
                    'table' => $table,
                    'stamp' => $stamp,
                    'employee_id' => $data[0],
                    'timestamp' => $data[1],
                    'status1' => $this->parseOptionalInt($data[2] ?? null),
                    'status2' => $this->parseOptionalInt($data[3] ?? null),
                    'status3' => $this->parseOptionalInt($data[4] ?? null),
                    'status4' => $this->parseOptionalInt($data[5] ?? null),
                    'status5' => $this->parseOptionalInt($data[6] ?? null),
                ]);

                $count++;
            }

            return "OK: " . $count;

        } catch (Throwable $e) {
            // Security Auditor: Log errors for debugging
            DB::table('error_log')->insert([
                'data' => $e->getMessage(),
                'created_at' => now()
            ]);
            return "ERROR: PROCESSING FAILED\n";
        }
    }

    public function test(Request $request)
    {
        return "ADMS SERVER ACTIVE";
    }

    public function getrequest(Request $request)
    {
        return "OK";
    }

    private function parseOptionalInt($value)
    {
        return (isset($value) && $value !== '') ? (int)$value : null;
    }
}
