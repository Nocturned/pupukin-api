<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filters\PriceRangeFilter;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $item = QueryBuilder::for(Item::class)
            ->with('picture', 'store', 'type', 'plant', 'plantPart')
            ->allowedFilters([
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('type', 'type.id'),
                    AllowedFilter::exact('plant', 'plant.id'),
                    AllowedFilter::exact('part', 'plantPart.id'),
                    AllowedFilter::custom('price', new PriceRangeFilter)
                ])
            ->defaultSort('created_at')
            ->allowedSorts('name', 'price', 'created_at')
            ->paginate(10)
            ->appends(request()->query());
        
        
        if ($item->isEmpty()) {
            return response()->json([
                'message' => 'Item list is empty.',
                'item'    => $item
            ], 200);
        }

        return response()->json([
            'message' => 'Item list fetched successfully.',
            'item'    => $item
        ], 200);
    }

    public function indexSort()
    {
        $item = DB::table('items')
            ->join('stores', 'items.store_id', '=', 'stores.id')
            ->select('items.*', 'stores.name as store_name', 'stores.latitude as latitude', 'stores.longitude as longitude')
            ->selectRaw('6371 * acos(cos(radians(37)) * cos(radians(stores.latitude)) * cos(radians(stores.longitude) - radians(-122)) + sin(radians(37)) * sin(radians(stores.latitude))) AS distance')
            ->orderBy('distance')
            ->get();

        if ($item->isEmpty()) {
            return response()->json([
                'message' => 'Item list is empty.',
                'item'    => $item
            ], 200);
        }

        return response()->json([
            'message' => 'Item list fetched successfully.',
            'item'    => $item
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
