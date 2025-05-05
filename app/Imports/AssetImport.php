<?php
namespace App\Imports;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements ToModel, WithChunkReading, WithHeadingRow
{
    public function model(array $row)
    {
        return new Asset([
            'organization_id'        => Auth::id(),
            'product_id'             => $row['id'] ?? null, // Access by header name
            'brand'                  => $row['brand'] ?? null,
            'range'                  => $row['range'] ?? null,
            'product'                => $row['product'] ?? null,
            'qr_code'                => $row['qr_code'] ?? null,
            'serial_number'          => $row['serial_number'] ?? null,
            'external_serial_number' => $row['external_serial_number'] ?? null,
            'manufacturing_date'     => isset($row['manufacturing_date']) ? $this->parseDate($row['manufacturing_date']) : null,
            'installation_date'      => isset($row['installation_date']) ? $this->parseDate($row['installation_date']) : null,
            'warranty_end_date'      => isset($row['warranty_end_date']) ? $this->parseDate($row['warranty_end_date']) : null,
            'unit_price'             => $this->parseNumeric($row['unit_price'] ?? null),
            'current_spend'          => $this->parseNumeric($row['current_spend'] ?? null),
            'max_spend'              => $this->parseNumeric($row['max_spend'] ?? null),
            'fitness_product'        => isset($row['fitness_product']) && ($row['fitness_product'] == 'true' || $row['fitness_product'] == 1),
            'has_odometer'           => isset($row['has_odometer']) && ($row['has_odometer'] == 'true' || $row['has_odometer'] == 1),
            'location'               => $row['location'] ?? null,
            'residual_price'         => $this->parseNumeric($row['residual_price'] ?? null),
        ]);
    }

    /**
     * Parse and validate a numeric value, or return null if invalid.
     *
     * @param mixed $value
     * @return float|null
     */
    private function parseNumeric($value)
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Parse the date dynamically or return null if invalid.
     *
     * @param string $date
     * @return string|null
     */
    private function parseDate($date)
    {
        $formats = ['d/m/Y', 'm/d/Y', 'Y-m-d']; // Add other formats as needed

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue to the next format
            }
        }

        // Return null if no formats match
        return null;
    }

    /**
     * Number of rows to read per chunk.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
