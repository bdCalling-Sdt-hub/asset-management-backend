<?php
namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Imports\AssetImport;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    //import
    public function importAssets(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        try {
            Excel::import(new AssetImport, $request->file('file'));

            return response()->json(['status' => true, 'message' => 'Assets imported successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to import assets. ' . $e->getMessage()], 500);
        }
    }

    //create asset
    public function createAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id'             => 'required|string|max:255',
            'brand'                  => 'required|string',
            'range'                  => 'nullable|string',
            'product'                => 'nullable|string',
            'qr_code'                => 'nullable|string',
            'serial_number'          => 'nullable|string',
            'external_serial_number' => 'nullable|string',
            'manufacturing_date'     => 'nullable|string',
            'installation_date'      => 'nullable|string',
            'warranty_end_date'      => 'nullable|string',
            'unit_price'             => 'nullable|string',
            'current_spend'          => 'nullable|string',
            'max_spend'              => 'nullable|string',
            'fitness_product'        => 'nullable|string',
            'has_odometer'           => 'nullable|string',
            'location'               => 'nullable|string',
            'residual_price'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $asset = Asset::create([
            'organization_id'        => Auth::user()->id,
            'product_id'             => $request->product_id,
            'brand'                  => $request->brand,
            'range'                  => $request->range,
            'product'                => $request->product,
            'qr_code'                => $request->qr_code,
            'serial_number'          => $request->serial_number,
            'external_serial_number' => $request->external_serial_number,
            'manufacturing_date'     => $request->manufacturing_date,
            'installation_date'      => $request->installation_date,
            'warranty_end_date'      => $request->warranty_end_date,
            'unit_price'             => $request->unit_price,
            'current_spend'          => $request->current_spend,
            'max_spend'              => $request->max_spend,
            'fitness_product'        => $request->fitness_product,
            'has_odometer'           => $request->has_odometer,
            'location'               => $request->location,
            'residual_price'         => $request->residual_price,
        ]);

        return response()->json([
            'status'  => true,
            'message' => $asset,
        ], 200);
    }

    //asset update
    public function updateAsset(Request $request, $id)
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json(['status' => false, 'message' => 'Asset Not Found'], 200);
        }

        $validator = Validator::make($request->all(), [
            'product_id'             => 'nullable|string|max:255',
            'brand'                  => 'nullable|string',
            'range'                  => 'nullable|string',
            'product'                => 'nullable|string',
            'qr_code'                => 'nullable|string',
            'serial_number'          => 'nullable|string',
            'external_serial_number' => 'nullable|string',
            'manufacturing_date'     => 'nullable|string',
            'installation_date'      => 'nullable|string',
            'warranty_end_date'      => 'nullable|string',
            'unit_price'             => 'nullable|string',
            'current_spend'          => 'nullable|string',
            'max_spend'              => 'nullable|string',
            'fitness_product'        => 'nullable|string',
            'has_odometer'           => 'nullable|string',
            'location'               => 'nullable|string',
            'residual_price'         => 'nullable|string',
        ]);

        $validatedData = $validator->validated();

        $asset->update($validatedData);

        return response()->json([
            'status'  => true,
            'message' => 'Asset updated successfully.',
            'data'    => $asset,
        ], 200);
    }

    //asset list
    public function assetList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search');
        $sortBy  = $request->input('sort_by');

        $assetlist = Asset::with('organization:id,name');

        // Apply search filter
        if (! empty($search)) {
            $assetlist->where(function ($query) use ($search) {
                $query->where('brand', 'like', "%$search%")
                    ->orWhere('qr_code', 'like', "%$search%")
                    ->orWhere('warranty_end_date', 'like', "%$search%")
                    ->orWhere('unit_price', 'like', "%$search%")
                    ->orWhere('current_spend', 'like', "%$search%")
                    ->orWhere('max_spend', 'like', "%$search%");
            });
        }

        // Apply sorting
        if (! empty($sortBy)) {
            if ($sortBy == 'brand') {
                $assetlist->orderBy('brand', 'asc');
            } elseif ($sortBy == 'qr_code') {
                $assetlist->orderBy('qr_code', 'asc');
            } elseif ($sortBy == 'warranty_end_date') {
                $assetlist->orderBy('warranty_end_date', 'asc');
            } elseif ($sortBy == 'unit_price') {
                $assetlist->orderBy('unit_price', 'asc');
            } elseif ($sortBy == 'current_spend') {
                $assetlist->orderBy('current_spend', 'asc');
            } elseif ($sortBy == 'organization') {
                $assetlist->orderBy('max_spend', 'asc');
            }
        }

        $assets = $assetlist->paginate($perPage);

        // Map customized response with organization name
        $data = $assets->getCollection()->map(function ($asset) {
            return [
                'id'                => $asset->id,
                'name'              => $asset->brand,
                'qr_code'           => $asset->qr_code,
                'warranty_end_date' => $asset->warranty_end_date,
                'unit_price'        => $asset->unit_price,
                'current_spend'     => $asset->current_spend,
                'max_spend'         => $asset->max_spend,
                'organization'      => $asset->organization->name ?? 'N/A', // Organization or third party name
            ];
        });

        // Replace the original collection with the mapped data
        $assets->setCollection(collect($data));

        return response()->json([
            'status' => true,
            'data'   => $assets,
        ]);
    }
    //asset details
    public function assetDetails(Request $request, $id)
    {
        $asset = Asset::with([
            'organization:id,name',
            'tickets:id,asset_id,problem,cost',
        ])->find($id);

        if (! $asset) {
            return response()->json(['status' => false, 'message' => 'Asset Not Found'], 422);
        }

        $currentSpend = (float) $asset->current_spend;
        $maxSpend     = (float) $asset->max_spend;

        // Calculate percentage spent
        $percentageSpent = ($maxSpend > 0) ? round(($currentSpend / $maxSpend) * 100, 2) : 0;

        // Ensure tickets exist before looping
        $serviceCostHistory = [];
        if ($asset->tickets->isNotEmpty()) {
            foreach ($asset->tickets as $ticket) {
                $serviceCostHistory[] = [
                    'ticket_id' => $ticket->id,
                    'problem'   => $ticket->problem,
                    'cost'      => $ticket->cost,
                ];
            }
        }

        return response()->json([
            'status'               => true,
            'service_cost_history' => $serviceCostHistory ?? null,
            'asset_details'        => $asset,
            'asset_Maturity'       => [
                'id'            => $asset->id,
                'product'       => $asset->product,
                'brand'         => $asset->brand,
                'serial_number' => $asset->serial_number,
                'current_spend' => $currentSpend,
                'max_spend'     => $maxSpend,
                'percentage'    => $percentageSpent,
            ],
        ], 200);
    }

    //asset delete
    public function deleteAsset($id)
    {
        $asset = Asset::find($id);

        if (! $asset) {
            return response()->json(['status' => false, 'message' => 'Asset not found.'], 200);
        }

        $asset->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Asset deleted successfully.',
        ], 200);
    }
}
