<?php

namespace App\Http\Controllers;

use App\Models\Datalist;
use App\Models\IpAddress as PrintSettings;
use App\Models\Models;
use App\Rules\ValidPackageBarcode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PrintController extends Controller
{
    private $print_settings;
    private $ip_address;

    public function index(Request $request)
    {
        $this->ip_address = '';
        $this->ip_address = $request->ip();
        $this->print_settings = '';
        $this->print_settings = PrintSettings::where('ip_address', '=', $this->ip_address)->get();

        if($this->print_settings->isEmpty())
        {
            return to_route('dns')->with('error', 'IP Address is not allowed to print this label');
        }

        try {
            $validated = $request->validate([
                'fgcode' => 'required|string',
                'qty' => 'required|numeric',
                'barcode' => ['required', 'string', new ValidPackageBarcode],
                'sessionID' => 'required|string',
                'name' => 'nullable|string',
                'reason' => 'nullable|string',
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation failed', $e->errors());
            return to_route('dns')->with('error', 'Validation Error: ' . $e->getMessage());
        }


        $exists = Datalist::where('fg_code', '=', $validated['fgcode'])
                    ->where('pckg_number', '=', $validated['barcode'])
                    ->get();

        if ($exists->isNotEmpty()) {
            if (empty($validated['name']) || empty($validated['reason'])) {
            return back()
                ->withInput()
                ->with('showReprintModal', true);
            }
            if (!preg_match('/^01;\d{6};.+$/', $validated['name'])) {
                return back()
                ->withInput()
                ->with('error', 'ID barcode format is invalid.');
            }
        }

       $exists = Models::where('fg_code', '=', $validated['fgcode'])->get();

       if ($exists->isEmpty()){
            return to_route('dns')->with('error', 'No model detected matching the FG Code provided: '. $validated['fgcode']);
       }

        $data = [
            'model' => strtoupper($exists[0]['model_name']),
            'part_number' => strtoupper($exists[0]['part_number']),
            'qty' => $validated['qty'],
            'fg_code' => strtoupper($validated['fgcode']),
            'pckg_number' => strtoupper($validated['barcode']),
            'pic' => strtoupper($validated['name']) ?? '',
            'reprinting_reason' => strtoupper($validated['reason']) ?? '',
            'SATO_IP' => $this->print_settings[0]['SATO_ip_address'],
            'H_offset' => $this->print_settings[0]['horizontal_offset'],
            'V_offset' => $this->print_settings[0]['vertical_offset'],
        ];

        $this->printSpecialLabel($data);

        Datalist::create([
            'model' => $data['model'],
            'part_number' => $data['part_number'],
            'qty' => $data['qty'],
            'fg_code' => $data['fg_code'],
            'pckg_number' => $data['pckg_number'],
            'pic' => $data['pic'] ?? '',
            'reprinting_reason' => $data['reprinting_reason'] ?? ''
        ]);

        return back()->with('success', 'Special Label Printed Successfully!');
    }

    private function printSpecialLabel($data){
        $SATO_IP = $data['SATO_IP'];
        $offsetH = $data['H_offset'];
        $offsetV = $data['V_offset'];
        $PartNo = $data['part_number'];
        $Quantity = $data['qty'];
        if(strlen($PartNo) < 12)
        {
            $PartNo2 = str_pad($data['part_number'], 12, "0", STR_PAD_LEFT);
        }else{
            $PartNo2 = $data['part_number'];
        }
        $finalBarcode2 = substr($data['pckg_number'], 0, strlen($data['pckg_number']) - 8);
        $LotNo = str_replace("-", "", strtoupper($data['pckg_number']));
        $LotNo = str_replace("/", "", $LotNo);
        if ((strlen($LotNo) <> 19) && (strlen($LotNo) <> 23)) {
            return redirect()->back()->with('error', 'Special Label Print Failed!');
        }
        $DateNeeded = substr($finalBarcode2, -6);
        $LotNo = substr($LotNo, -19);
        $DateNeeded2 = substr($DateNeeded, -2) . substr($DateNeeded, 0, 4);
        $finalBarcode = $PartNo2 . $LotNo . $DateNeeded2 . sprintf("%03d", $Quantity);

        $fp = pfsockopen($SATO_IP, 9100);

        $xQRCode = strtoupper(urldecode($finalBarcode));
        $esc = chr(27);

        $data = "";
        $data .= $esc . 'A';
        $data .= $esc . 'A3H1374V0001';
        $data .= $esc . 'H' . sprintf("%04d", $offsetH) . $esc . 'V' . sprintf("%04d", $offsetV + 10) . $esc . 'P2' . $esc . 'L0103' . $esc . 'SPart No. :';
        $data .= $esc . 'H' . sprintf("%04d", $offsetH + 120) . $esc . 'V' . sprintf("%04d", $offsetV + 10) . $esc . 'P2' . $esc . 'L0203' . $esc . 'S' . $PartNo;
        $data .= $esc . 'H' . sprintf("%04d", $offsetH) . $esc . 'V' . sprintf("%04d", $offsetV + 80) . $esc . 'P2' . $esc . 'L0103' . $esc . 'SLot :';
        $data .= $esc . 'H' . sprintf("%04d", $offsetH + 60) . $esc . 'V' . sprintf("%04d", $offsetV + 80) . $esc . 'P2' . $esc . 'L0203' . $esc . 'S' . $LotNo;
        $data .= $esc . 'H' . sprintf("%04d", $offsetH) . $esc . 'V' . sprintf("%04d", $offsetV + 150) . $esc . 'P2' . $esc . 'L0103' . $esc . 'SPacking Date :';
        $data .= $esc . 'H' . sprintf("%04d", $offsetH + 160) . $esc . 'V' . sprintf("%04d", $offsetV + 150) . $esc . 'P2' . $esc . 'L0203' . $esc . 'S' . $DateNeeded2;
        $data .= $esc . 'H' . sprintf("%04d", $offsetH) . $esc . 'V' . sprintf("%04d", $offsetV + 220) . $esc . 'P2' . $esc . 'L0103' . $esc . 'SQuantity :';
        $data .= $esc . 'H' . sprintf("%04d", $offsetH + 120) . $esc . 'V' . sprintf("%04d", $offsetV + 220) . $esc . 'P2' . $esc . 'L0203' . $esc . 'S' . $Quantity;
        //print qr code
        $data .= $esc . 'H' . sprintf("%04d", $offsetH + 310) . $esc . 'V' . sprintf("%04d", $offsetV + 150) . $esc . '2D30,L,04,0,0';
        $data .= $esc . 'DN' . sprintf("%04d", strlen($xQRCode)) . ',' . $xQRCode;
        $data .= $esc . 'Q1';
        $data .= $esc . 'Z' . $esc;
        $print_output = $data;

        fputs($fp, $print_output);
        fclose($fp);
    }
}
