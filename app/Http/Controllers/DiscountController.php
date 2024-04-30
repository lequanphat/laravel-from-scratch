<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDiscount;
use App\Http\Requests\UpdateDiscount;
use App\Models\Discount;
use App\Models\Product;
use App\Models\ProductDetail;

use App\Models\ProductDiscounts;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $data = [
            'page' => 'Discount Site',
            'discounts' => Discount::paginate(8),
        ];
        return view('admin.discounts.discountUI', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CreateDiscount $request)
    {
        //


        $DiscountData =
            [
                'title' => $request->input('title'),
               
                'is_active' => $request->input('active'),
                'start_date' => $request->input('startdate'),
                'end_date' => $request->input('enddate'),
                'percentage' => $request->input('percentage'),
                'description' => $request->input('description')
            ];
        $Discount = Discount::create($DiscountData);
        //print_r($request);
        return ['message' => 'Created discount successfully!'];
    }

    public function discount_detail()
    {
        $discount_id = request()->route('discount_id');

        $productDiscounts = new ProductDiscounts();

        //        lay tat ca cac sku co discount_id
        $sku = $productDiscounts->getSkuForDiscountId($discount_id);


        //        $product_id_productDetail = ProductDetail::where('sku', $sku)->pluck('product_id');
        $product_ids = ProductDetail::whereIn('sku', $sku)->pluck('product_id');
        $checkIdProduct = Product::whereIn('product_id', $product_ids)->pluck('product_id');

        //        if ($sku) {
        //            echo "The SKU for discount ID is: -----------------------------------$sku" . "<br>";
        ////            echo " With $sku have $product_ids" . "<br>";
        ////            echo " check  $checkIdProduct";
        //        } else {
        //            echo "No SKU found for discount ID $discount_id";
        //        }

        $data = [
            'page' => 'Discount Details And List Discount Item',
            //        ,

            'discount' => Discount::find($discount_id),
            //            'product' => Product::all(),
            'product' => ProductDetail::all(),
            'Registor' => $sku,

        ];


        return view('admin.discounts.discount_detail', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDiscount $request)
    {
        //

        $discount_find = Discount::where('discount_id', $request->input('discount_id'))->first();

        if ($discount_find) {
            $discount_find->update([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'percentage' => $request->input('percentage'),
                // 'amount' => $request->input('amount'),
                'start_date' => $request->input('startdate'),
                'end_date' => $request->input('enddate'),
                'is_active' => $request->input('active')
            ]);
            // response

        }
        return ['message' => 'update discount successfully!'];
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $discount = Discount::find($id);
        $discount->delete();

        return redirect('/admin/discounts');
        //        return "Take It";

    }

    public function deleteProductDiscountCheck(Request $request)
    {
        try {
            $productId = $request->input('product_id');
            $isChecked = $request->input('is_checked');
            $discountId = $request->input('discount_id');
            $sku = $request->input('sku');
            // Retrieve SKU directly, checking for existence
            //            $findSku = ProductDetail::where('product_id', $productId)->value('sku');
            $deletedRows = ProductDiscounts::where('sku', $sku)->where('discount_id', $discountId)->forceDelete();
            //


            if ($deletedRows > 0) {
                return response()->json(['message' => 'Checkbox delete successfully'], 200);
            } else {
                return response()->json(['error' => 'No matching discount found to delete.'], 404); // Return 404 if no matching discount found
            }
        } catch (Exception $e) {
            // Log the exception for detailed debugging
            Log::error($e);
            return response()->json(['error' => 'An error occurred while deleting the discount.'], 500);
        }
    }
    
    public function saveChanges(Request $request)
    {




        if ($request->has('checkedProducts')) {
            $checkedProducts = $request->input('checkedProducts');

            // Kiểm tra xem 'checkedProducts' có phải là một mảng không
            if (is_array($checkedProducts)) {
                $recordsToCreate = [];



                // Loop qua các sản phẩm đã chọn và chuẩn bị dữ liệu cho mỗi sản phẩm
                foreach ($checkedProducts as $product) {
                    // Kiểm tra xem mỗi phần tử có đủ thông tin cần thiết không
                    if (isset($product['sku']) && isset($product['discountId'])) {
                        $recordsToCreate[] = [
                            'sku' => $product['sku'],
                            'discount_id' => $product['discountId'],
                            // Thêm các trường khác nếu cần
                        ];
                    }
                }



                try {
                    // Start a database transaction


                    foreach ($recordsToCreate as $record) {
                        // Check if the record already exists
                        $existingRecord = ProductDiscounts::where('sku', $record['sku'])->where('discount_id', $record['discount_id'])->first();
                        if ($request->has('uncheckedProducts')) {
                            $uncheckedProducts = $request->input('uncheckedProducts');

                            // Kiểm tra xem 'uncheckedProducts' có phải là một mảng không
                            if (is_array($uncheckedProducts)) {
                                try {
                                    // Lặp qua các sản phẩm đã bỏ chọn và xóa chúng khỏi cơ sở dữ liệu
                                    foreach ($uncheckedProducts as $product) {
                                        // Tìm kiếm và xóa sản phẩm có sku và discount_id tương ứng
                                        ProductDiscounts::where('sku', $product['sku'])
                                            ->where('discount_id', $product['discountId'])
                                            ->forceDelete();
                                    }

                                    // Trả về phản hồi thành công nếu mọi thứ diễn ra đúng

                                } catch (\Exception $e) {
                                    // Xử lý ngoại lệ nếu có lỗi xảy ra

                                }
                            }
                        }
                        if (!$existingRecord) {
                            // Record does not exist, insert it
                            ProductDiscounts::create($record);
                        } else {
                            // Record already exists, you can choose to update it or skip it
                            // For example, you can update the existing record
                            $existingRecord->update($record);
                        }
                    }




                    // Commit the transaction

                    // Return success response
                    return response()->json(['success' => true, 'message' => 'Records created/updated successfully']);
                } catch (\Exception $e) {
                    // Handle the exception

                    return response()->json(['success' => false, 'message' => 'Error occurred while saving records', 'error' => $e->getMessage()]);
                }
            } else {

                // Return error message if 'checkedProducts' is not an array
                return response()->json(['success' => false, 'message' => 'Invalid input data']);
            }
        } else {
            // Trả về thông báo lỗi nếu không có dữ liệu 'checkedProducts' được gửi lên
            return response()->json(['success' => false, 'message' => 'No data received']);
        }
    }

    public function updateProductDiscount(Request $request)
    {
        try {
            $productId = $request->input('product_id');
            $isChecked = $request->input('is_checked');
            $discountId = $request->input('discount_id');
            $findSku_true = ProductDetail::where('product_id', $productId)->first();
            $sku = $request->input('sku');


            $findSku = ProductDetail::where('product_id', $productId)->first()->sku;


            $DiscountCheckBox =
                [
                    'sku' => $sku,
                    'discount_id' => $discountId,

                ];


            $Discount = ProductDiscounts::create($DiscountCheckBox);
            return response()->json(['message' => 'Checkbox change saved successfully']);
            //
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while saving the checkbox change']);
        }
    }
}
